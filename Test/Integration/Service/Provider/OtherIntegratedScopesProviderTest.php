<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Provider;

use Klevu\Configuration\Service\Provider\OtherIntegratedScopesProvider;
use Klevu\Configuration\Service\Provider\OtherIntegratedScopesProviderInterface;
use Klevu\Configuration\Service\Provider\Stores\Config\AuthKeysCollectionProviderInterface;
use Klevu\Configuration\Ui\DataProvider\Integration\Listing\StoresDataProvider;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigCollection;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\App\Config\Value;
use Magento\Framework\DB\Select;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OtherIntegratedScopesProviderTest extends TestCase
{
    use StoreTrait;
    use WebsiteTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
        $this->websiteFixturesPool = $this->objectManager->get(WebsiteFixturesPool::class);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->websiteFixturesPool->rollback();
        $this->storeFixturesPool->rollback();
    }

    public function testImplements_IntegratedScopesForKeyProviderInterface(): void
    {
        $this->assertInstanceOf(
            expected: OtherIntegratedScopesProviderInterface::class,
            actual: $this->instantiateOtherIntegratedScopes(),
        );
    }

    public function testPreference_ForIntegratedScopesForKeyProviderInterface(): void
    {
        $this->assertInstanceOf(
            expected: OtherIntegratedScopesProvider::class,
            actual: $this->objectManager->create(OtherIntegratedScopesProviderInterface::class),
        );
    }

    public function testGet_KeysNotPresent(): void
    {
        $provider = $this->instantiateOtherIntegratedScopes();
        $scopes = $provider->get(
            apiKey: 'w849jt',
            authKey: 'sdgfgs',
            scopeId: 1,
            scopeType: ScopeInterface::SCOPE_STORES,
        );

        $this->assertIsArray(actual: $scopes);
        $this->assertCount(expectedCount: 0, haystack: $scopes);
    }

    public function testGet_StoreScope_selfIsIgnored(): void
    {
        $this->createWebsite();
        $website1 = $this->websiteFixturesPool->get('test_website');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $store1 = $this->storeFixturesPool->get('test_store');

        $mockStore1ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-js-api-key',
        ]);
        $mockStore1ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-rest-auth-key',
        ]);

        $authKeyCollection = $this->getAuthKeyCollectionWithMockedData([
            $mockStore1ConfigJs,
            $mockStore1ConfigRest,
        ]);

        $provider = $this->instantiateOtherIntegratedScopes([
            'authKeysCollectionProvider' => $authKeyCollection,
        ]);
        $scopes = $provider->get(
            apiKey: 'klevu-js-api-key',
            authKey: 'klevu-rest-api-key',
            scopeId: (int)$store1->getId(),
            scopeType: ScopeInterface::SCOPE_STORES,
        );

        $this->assertIsArray(actual: $scopes);
        $this->assertCount(expectedCount: 0, haystack: $scopes);
    }

    public function testGet_StoreScope_StoreIntegration_SameWebsite(): void
    {
        $this->createWebsite();
        $website1 = $this->websiteFixturesPool->get('test_website');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website1->getId(),
            'key' => 'test_store_2',
        ]);
        $store1 = $this->storeFixturesPool->get('test_store');
        $store2 = $this->storeFixturesPool->get('test_store_2');

        $mockStore2ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-js-api-key',
        ]);
        $mockStore2ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-rest-auth-key',
        ]);

        $authKeyCollection = $this->getAuthKeyCollectionWithMockedData([
            $mockStore2ConfigJs,
            $mockStore2ConfigRest,
        ]);

        $provider = $this->instantiateOtherIntegratedScopes([
            'authKeysCollectionProvider' => $authKeyCollection,
        ]);

        $scopes = $provider->get(
            apiKey: 'klevu-js-api-key',
            authKey: 'klevu-rest-api-key',
            scopeId: (int)$store1->getId(),
            scopeType: ScopeInterface::SCOPE_STORES,
        );

        $this->assertIsArray(actual: $scopes);
        $this->assertCount(expectedCount: 1, haystack: $scopes);

        $this->assertContains(
            needle: 'Store: ' . $store2->getId() . ' ' . $store2->getName() . ' (' . $store2->getCode() . ')',
            haystack: $scopes,
            message: 'Haystack: ' . implode(', ', $scopes),
        );
    }

    public function testGet_StoreScope_StoreIntegration_DifferentWebsite(): void
    {
        $this->createWebsite();
        $this->createWebsite(websiteData: [
            'code' => 'klevu_test_website_2',
            'key' => 'other_site',
        ]);
        $website1 = $this->websiteFixturesPool->get('test_website');
        $website2 = $this->websiteFixturesPool->get('other_site');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website2->getId(),
            'key' => 'test_store_2',
        ]);
        $store1 = $this->storeFixturesPool->get('test_store');
        $store2 = $this->storeFixturesPool->get('test_store_2');

        $mockStore2ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-js-api-key',
        ]);
        $mockStore2ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-rest-auth-key',
        ]);

        $authKeyCollection = $this->getAuthKeyCollectionWithMockedData([
            $mockStore2ConfigJs,
            $mockStore2ConfigRest,
        ]);

        $provider = $this->instantiateOtherIntegratedScopes([
            'authKeysCollectionProvider' => $authKeyCollection,
        ]);
        $scopes = $provider->get(
            apiKey: 'klevu-js-api-key',
            authKey: 'klevu-rest-api-key',
            scopeId: (int)$store1->getId(),
            scopeType: ScopeInterface::SCOPE_STORES,
        );

        $this->assertIsArray(actual: $scopes);
        $this->assertCount(expectedCount: 1, haystack: $scopes);

        $this->assertContains(
            needle: 'Store: ' . $store2->getId() . ' ' . $store2->getName() . ' (' . $store2->getCode() . ')',
            haystack: $scopes,
            message: 'Haystack: ' . implode(', ', $scopes),
        );
    }

    public function testGet_StoreScope_WebsiteIntegration_ParentWebsite(): void
    {
        $this->createWebsite();
        $website1 = $this->websiteFixturesPool->get('test_website');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website1->getId(),
            'key' => 'test_store_2',
        ]);
        $store1 = $this->storeFixturesPool->get('test_store');
        $store2 = $this->storeFixturesPool->get('test_store_2');

        $mockStore2ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store2->getWebsiteId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-js-api-key',
        ]);
        $mockStore2ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $store2->getWebsiteId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-rest-auth-key',
        ]);

        $authKeyCollection = $this->getAuthKeyCollectionWithMockedData([
            $mockStore2ConfigJs,
            $mockStore2ConfigRest,
        ]);

        $provider = $this->instantiateOtherIntegratedScopes([
            'authKeysCollectionProvider' => $authKeyCollection,
        ]);
        $scopes = $provider->get(
            apiKey: 'klevu-js-api-key',
            authKey: 'klevu-rest-api-key',
            scopeId: (int)$store1->getId(),
            scopeType: ScopeInterface::SCOPE_STORES,
        );

        $this->assertIsArray(actual: $scopes);
        $this->assertCount(expectedCount: 1, haystack: $scopes);

        $this->assertContains(
            needle: 'Website: ' . $website1->getId() . ' ' . $website1->getName() . ' (' . $website1->getCode() . ')',
            haystack: $scopes,
            message: 'Haystack: ' . implode(', ', $scopes),
        );
    }

    public function testGet_StoreScope_WebsiteIntegration_OtherWebsite(): void
    {
        $this->createWebsite();
        $this->createWebsite(websiteData: [
            'code' => 'klevu_test_website_2',
            'key' => 'other_site',
        ]);
        $website1 = $this->websiteFixturesPool->get('test_website');
        $website2 = $this->websiteFixturesPool->get('other_site');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website2->getId(),
            'key' => 'test_store_2',
        ]);
        $store1 = $this->storeFixturesPool->get('test_store');
        $store2 = $this->storeFixturesPool->get('test_store_2');

        $mockStore2ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store2->getWebsiteId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-js-api-key',
        ]);
        $mockStore2ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $store2->getWebsiteId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-rest-auth-key',
        ]);

        $authKeyCollection = $this->getAuthKeyCollectionWithMockedData([
            $mockStore2ConfigJs,
            $mockStore2ConfigRest,
        ]);

        $provider = $this->instantiateOtherIntegratedScopes([
            'authKeysCollectionProvider' => $authKeyCollection,
        ]);
        $scopes = $provider->get(
            apiKey: 'klevu-js-api-key',
            authKey: 'klevu-rest-api-key',
            scopeId: (int)$store1->getId(),
            scopeType: ScopeInterface::SCOPE_STORES,
        );

        $this->assertIsArray(actual: $scopes);
        $this->assertCount(expectedCount: 1, haystack: $scopes);

        $this->assertContains(
            needle: 'Website: ' . $website2->getId() . ' ' . $website2->getName() . ' (' . $website2->getCode() . ')',
            haystack: $scopes,
            message: 'Haystack: ' . implode(', ', $scopes),
        );
    }

    public function testGet_StoreScope_NoMatchingIntegrationsAtAnyScopes(): void
    {
        $this->createWebsite();
        $this->createWebsite(websiteData: [
            'code' => 'klevu_test_website_2',
            'key' => 'other_site',
        ]);
        $website1 = $this->websiteFixturesPool->get('test_website');
        $website2 = $this->websiteFixturesPool->get('other_site');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website1->getId(),
            'key' => 'test_store_2',
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_3',
            'website_id' => $website2->getId(),
            'key' => 'test_store_3',
        ]);
        $store1 = $this->storeFixturesPool->get('test_store_2');
        $store2 = $this->storeFixturesPool->get('test_store_2');
        $store3 = $this->storeFixturesPool->get('test_store_3');

        $mockStore1ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => '823rh84t3h4t',
        ]);
        $mockStore1ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'sv0wgj034t',
        ]);
        $mockStore2ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => '20j390gj3g',
        ]);
        $mockStore2ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => '24t0j34g0j34t',
        ]);
        $mockWebsite1ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $website1->getId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => '3g90j924tj2',
        ]);
        $mockWebsite1ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $website1->getId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => '2f924tn4jt42t',
        ]);
        $mockStore3ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store3->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => '20j390gj3g',
        ]);
        $mockStore3ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $store3->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => '24t0j34g0j34t',
        ]);
        $mockWebsite2ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $website2->getId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => '20j390gj3g',
        ]);
        $mockWebsite2ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $website2->getId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => '24t0j34g0j34t',
        ]);

        $authKeyCollection = $this->getAuthKeyCollectionWithMockedData([
            $mockStore1ConfigJs,
            $mockStore1ConfigRest,
            $mockStore2ConfigJs,
            $mockStore2ConfigRest,
            $mockWebsite1ConfigJs,
            $mockWebsite1ConfigRest,
            $mockStore3ConfigJs,
            $mockStore3ConfigRest,
            $mockWebsite2ConfigJs,
            $mockWebsite2ConfigRest,
        ]);

        $provider = $this->instantiateOtherIntegratedScopes([
            'authKeysCollectionProvider' => $authKeyCollection,
        ]);
        $scopes = $provider->get(
            apiKey: 'klevu-js-api-key',
            authKey: 'klevu-rest-api-key',
            scopeId: (int)$store1->getId(),
            scopeType: ScopeInterface::SCOPE_STORE,
        );

        $this->assertIsArray(actual: $scopes);
        $this->assertCount(expectedCount: 0, haystack: $scopes);
    }

    public function testGet_WebsiteScope_selfIsIgnored(): void
    {
        $this->createWebsite();
        $website1 = $this->websiteFixturesPool->get('test_website');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);

        $mockStore1ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $website1->getId(),
            'scope' => ScopeInterface::SCOPE_WEBSITE,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-js-api-key',
        ]);
        $mockStore1ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $website1->getId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-rest-auth-key',
        ]);

        $authKeyCollection = $this->getAuthKeyCollectionWithMockedData([
            $mockStore1ConfigJs,
            $mockStore1ConfigRest,
        ]);

        $provider = $this->instantiateOtherIntegratedScopes([
            'authKeysCollectionProvider' => $authKeyCollection,
        ]);
        $scopes = $provider->get(
            apiKey: 'klevu-js-api-key',
            authKey: 'klevu-rest-api-key',
            scopeId: (int)$website1->getId(),
            scopeType: ScopeInterface::SCOPE_WEBSITE,
        );

        $this->assertIsArray(actual: $scopes);
        $this->assertCount(expectedCount: 0, haystack: $scopes);
    }

    public function testGet_WebsiteScope_DifferentWebsiteIntegration(): void
    {
        $this->createWebsite();
        $this->createWebsite(websiteData: [
            'code' => 'klevu_test_website_2',
            'key' => 'other_site',
        ]);
        $website1 = $this->websiteFixturesPool->get('test_website');
        $website2 = $this->websiteFixturesPool->get('other_site');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website2->getId(),
            'key' => 'test_store_2',
        ]);
        $store2 = $this->storeFixturesPool->get('test_store_2');

        $mockStore2ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store2->getWebsiteId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-js-api-key',
        ]);
        $mockStore2ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $store2->getWebsiteId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-rest-auth-key',
        ]);

        $authKeyCollection = $this->getAuthKeyCollectionWithMockedData([
            $mockStore2ConfigJs,
            $mockStore2ConfigRest,
        ]);

        $provider = $this->instantiateOtherIntegratedScopes([
            'authKeysCollectionProvider' => $authKeyCollection,
        ]);
        $scopes = $provider->get(
            apiKey: 'klevu-js-api-key',
            authKey: 'klevu-rest-api-key',
            scopeId: (int)$website1->getId(),
            scopeType: ScopeInterface::SCOPE_WEBSITES,
        );

        $this->assertIsArray(actual: $scopes);
        $this->assertCount(expectedCount: 1, haystack: $scopes);

        $this->assertContains(
            needle: 'Website: ' . $website2->getId() . ' ' . $website2->getName() . ' (' . $website2->getCode() . ')',
            haystack: $scopes,
            message: 'Haystack: ' . implode(', ', $scopes),
        );
    }

    public function testGet_WebsiteScope_ChildStoreIntegration(): void
    {
        $this->createWebsite();
        $website1 = $this->websiteFixturesPool->get('test_website');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website1->getId(),
            'key' => 'test_store_2',
        ]);
        $store2 = $this->storeFixturesPool->get('test_store_2');

        $mockStore2ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-js-api-key',
        ]);
        $mockStore2ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-rest-auth-key',
        ]);

        $authKeyCollection = $this->getAuthKeyCollectionWithMockedData([
            $mockStore2ConfigJs,
            $mockStore2ConfigRest,
        ]);

        $provider = $this->instantiateOtherIntegratedScopes([
            'authKeysCollectionProvider' => $authKeyCollection,
        ]);
        $scopes = $provider->get(
            apiKey: 'klevu-js-api-key',
            authKey: 'klevu-rest-api-key',
            scopeId: (int)$website1->getId(),
            scopeType: ScopeInterface::SCOPE_WEBSITES,
        );

        $this->assertIsArray(actual: $scopes);
        $this->assertCount(expectedCount: 1, haystack: $scopes);

        $this->assertContains(
            needle: 'Store: ' . $store2->getId() . ' ' . $store2->getName() . ' (' . $store2->getCode() . ')',
            haystack: $scopes,
            message: 'Haystack: ' . implode(', ', $scopes),
        );
    }

    public function testGet_WebsiteScope_StoreOfOtherWebsiteIntegration(): void
    {
        $this->createWebsite();
        $this->createWebsite(websiteData: [
            'code' => 'klevu_test_website_2',
            'key' => 'other_site',
        ]);
        $website1 = $this->websiteFixturesPool->get('test_website');
        $website2 = $this->websiteFixturesPool->get('other_site');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website2->getId(),
            'key' => 'test_store_2',
        ]);
        $store2 = $this->storeFixturesPool->get('test_store_2');

        $mockStore2ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-js-api-key',
        ]);
        $mockStore2ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-rest-auth-key',
        ]);

        $authKeyCollection = $this->getAuthKeyCollectionWithMockedData([
            $mockStore2ConfigJs,
            $mockStore2ConfigRest,
        ]);

        $provider = $this->instantiateOtherIntegratedScopes([
            'authKeysCollectionProvider' => $authKeyCollection,
        ]);
        $scopes = $provider->get(
            apiKey: 'klevu-js-api-key',
            authKey: 'klevu-rest-api-key',
            scopeId: (int)$website1->getId(),
            scopeType: ScopeInterface::SCOPE_WEBSITES,
        );

        $this->assertIsArray(actual: $scopes);
        $this->assertCount(expectedCount: 1, haystack: $scopes);

        $this->assertContains(
            needle: 'Store: ' . $store2->getId() . ' ' . $store2->getName() . ' (' . $store2->getCode() . ')',
            haystack: $scopes,
            message: 'Haystack: ' . implode(', ', $scopes),
        );
    }

    public function testGet_WebsiteScope_NoMatchingIntegrationsAtAnyScopes(): void
    {
        $this->createWebsite();
        $this->createWebsite(websiteData: [
            'code' => 'klevu_test_website_2',
            'key' => 'other_site',
        ]);
        $website1 = $this->websiteFixturesPool->get('test_website');
        $website2 = $this->websiteFixturesPool->get('other_site');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website1->getId(),
            'key' => 'test_store_2',
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_3',
            'website_id' => $website2->getId(),
            'key' => 'test_store_3',
        ]);
        $store1 = $this->storeFixturesPool->get('test_store_2');
        $store2 = $this->storeFixturesPool->get('test_store_2');
        $store3 = $this->storeFixturesPool->get('test_store_3');

        $mockStore1ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => '823rh84t3h4t',
        ]);
        $mockStore1ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'sv0wgj034t',
        ]);
        $mockStore2ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => '20j390gj3g',
        ]);
        $mockStore2ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => '24t0j34g0j34t',
        ]);
        $mockWebsite1ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $website1->getId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => '3g90j924tj2',
        ]);
        $mockWebsite1ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $website1->getId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => '2f924tn4jt42t',
        ]);
        $mockStore3ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store3->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => '20j390gj3g',
        ]);
        $mockStore3ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $store3->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => '24t0j34g0j34t',
        ]);
        $mockWebsite2ConfigJs = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $website2->getId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => '20j390gj3g',
        ]);
        $mockWebsite2ConfigRest = $this->getMockConfigValue(data: [
            'config_id' => 12348,
            'scope_id' => $website2->getId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => '24t0j34g0j34t',
        ]);

        $authKeyCollection = $this->getAuthKeyCollectionWithMockedData([
            $mockStore1ConfigJs,
            $mockStore1ConfigRest,
            $mockStore2ConfigJs,
            $mockStore2ConfigRest,
            $mockWebsite1ConfigJs,
            $mockWebsite1ConfigRest,
            $mockStore3ConfigJs,
            $mockStore3ConfigRest,
            $mockWebsite2ConfigJs,
            $mockWebsite2ConfigRest,
        ]);

        $provider = $this->instantiateOtherIntegratedScopes([
            'authKeysCollectionProvider' => $authKeyCollection,
        ]);
        $scopes = $provider->get(
            apiKey: 'klevu-js-api-key',
            authKey: 'klevu-rest-api-key',
            scopeId: (int)$website1->getId(),
            scopeType: ScopeInterface::SCOPE_WEBSITES,
        );

        $this->assertIsArray(actual: $scopes);
        $this->assertCount(expectedCount: 0, haystack: $scopes);
    }

    /**
     * @param mixed[] $data
     *
     * @return Value|(Value&MockObject)|MockObject
     */
    private function getMockConfigValue(array $data): Value|MockObject
    {
        $mockConfigBuilder = $this->getMockBuilder(Value::class);
        /** @see vendor/magento/framework/App/Config/Value.php:11 */
        $mockConfigBuilder->addMethods(['getScopeId', 'getScope', 'getPath', 'getValue']);
        $mockConfigBuilder->onlyMethods(['getId']);
        $mockConfig = $mockConfigBuilder->disableOriginalConstructor()->getMock();

        $mockConfig->method('getId')
            ->willReturn($data['config_id'] ?? null);
        $mockConfig->method('getScopeId')
            ->willReturn($data['scope_id'] ?? null);
        $mockConfig->method('getScope')
            ->willReturn($data['scope'] ?? null);
        $mockConfig->method('getPath')
            ->willReturn($data['path'] ?? null);
        $mockConfig->method('getValue')
            ->willReturn($data['value'] ?? null);

        return $mockConfig;
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return OtherIntegratedScopesProvider
     */
    private function instantiateOtherIntegratedScopes(?array $arguments = []): OtherIntegratedScopesProvider
    {
        return $this->objectManager->create(
            type: OtherIntegratedScopesProvider::class,
            arguments: $arguments,
        );
    }

    /**
     * @param array<MockObject|Value> $mockConfigValues
     *
     * @return AuthKeysCollectionProviderInterface
     */
    private function getAuthKeyCollectionWithMockedData(array $mockConfigValues): AuthKeysCollectionProviderInterface
    {
        $mockSelect = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollection = $this->getMockBuilder(ConfigCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollection->expects($this->once())
            ->method('getSelect')
            ->wilLReturn($mockSelect);
        $mockCollection->expects($this->once())
            ->method('addPathFilter')
            ->with(StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS);
        $mockCollection->expects($this->once())
            ->method('getItems')
            ->willReturn($mockConfigValues);

        $mockCollectionFactory = $this->getMockBuilder(ConfigCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockCollection);

        return $this->objectManager->create(AuthKeysCollectionProviderInterface::class, [
            'configCollectionFactory' => $mockCollectionFactory,
        ]);
    }
}
