<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service;

use Klevu\Configuration\Service\Action\UpdateEndpoints;
use Klevu\Configuration\Service\Provider\ApiKeyProvider;
use Klevu\Configuration\Service\Provider\AuthKeyProvider;
use Klevu\Configuration\Service\RemoveApiKeysService;
use Klevu\Configuration\Service\RemoveApiKeysServiceInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\RemoveApiKeysService
 */
class RemoveApiKeyServiceTest extends TestCase
{
    use StoreTrait;

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
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->storeFixturesPool->rollback();
    }

    public function testImplements_CheckApiKeysServiceInterface(): void
    {
        $this->assertInstanceOf(
            expected: RemoveApiKeysServiceInterface::class,
            actual: $this->instantiateRemoveApiKeysService(),
        );
    }

    public function testPreference_ForIntegrateApiKeysServiceInterface(): void
    {
        $this->assertInstanceOf(
            expected: RemoveApiKeysService::class,
            actual: $this->objectManager->create(RemoveApiKeysServiceInterface::class),
        );
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/url_analytics analytics.url
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/url_cat_nav catnav.url
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/url_indexing indexing.url
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/url_js js.url
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/url_search search.url
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/url_tiers tiers.url
     */
    public function testExecute_RemovesAuthKeysAndEndpoints(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get(key: 'test_store');
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);

        $initialConfigValues = [
            ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY => 'klevu-js-api-key',
            AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY => 'klevu-rest-auth-key',
            UpdateEndpoints::CONFIG_XML_PATH_URL_ANALYTICS => 'analytics.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_CAT_NAV => 'catnav.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_INDEXING => 'indexing.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_JS => 'js.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_SEARCH => 'search.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_TIERS => 'tiers.url',
        ];
        foreach ($initialConfigValues as $path => $value) {
            $configValue = $scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORES,
                $store->getId(),
            );
            $this->assertSame(expected: $value, actual: $configValue);
        }

        $mockEventManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMock();
        $mockEventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'klevu_remove_api_keys_after',
                [
                    'apiKey' => 'klevu-js-api-key',
                ],
            );

        $service = $this->instantiateRemoveApiKeysService([
            'eventManager' => $mockEventManager,
        ]);
        $service->execute(
            scopeId: (int)$store->getId(),
            scopeType: ScopeInterface::SCOPE_STORES,
        );

        $paths = [
            ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY => null,
            AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_ANALYTICS => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_CAT_NAV => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_INDEXING => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_JS => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_SEARCH => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_TIERS => 'tiers.klevu.com', // default setting
        ];
        foreach ($paths as $path => $value) {
            $configValue = $scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORES,
                $store->getId(),
            );
            $this->assertSame(expected: $value, actual: $configValue);
        }
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return RemoveApiKeysService
     */
    private function instantiateRemoveApiKeysService(?array $arguments = []): RemoveApiKeysService
    {
        return $this->objectManager->create(
            type: RemoveApiKeysService::class,
            arguments: $arguments,
        );
    }
}
