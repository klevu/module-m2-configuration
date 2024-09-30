<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Provider;

use Klevu\Configuration\Exception\ApiKeyNotFoundException;
use Klevu\Configuration\Service\Provider\AuthKeyProvider;
use Klevu\Configuration\Service\Provider\AuthKeyProviderInterface;
use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\CurrentScopeTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\Provider\AuthKeyProvider
 * @magentoAppIsolation enabled
 */
class AuthKeyProviderTest extends TestCase
{
    use CurrentScopeTrait;
    use SetAuthKeysTrait;
    use StoreTrait;
    use WebsiteTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeFixturesPool = $this->objectManager->create(StoreFixturesPool::class);
        $this->websiteFixturesPool = $this->objectManager->create(WebsiteFixturesPool::class);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->storeFixturesPool->rollback();
        $this->websiteFixturesPool->rollback();
    }

    public function testImplements_AuthKeysProviderInterface(): void
    {
        $this->assertInstanceOf(
            expected: AuthKeyProviderInterface::class,
            actual: $this->instantiateAuthKeyProvider(),
        );
    }

    public function testPreference_ForAuthKeysInterface(): void
    {
        $this->assertInstanceOf(
            expected: AuthKeyProvider::class,
            actual: $this->objectManager->create(AuthKeyProviderInterface::class),
        );
    }

    public function testGet_throwsException_ForNonexistentStoreId(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $mockStore = $this->getMockBuilder(StoreInterface::class)
            ->getMock();
        $mockStore->method('getId')
            ->willReturn(8269826578);

        $currentScope = $this->createCurrentScope($mockStore);

        $authKeyProvider = $this->instantiateAuthKeyProvider();
        $authKeyProvider->get($currentScope);
    }

    public function testGet_ReturnsNull_WhenStoreConfigNotSet(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store')->get();

        $currentScope = $this->createCurrentScope($store);

        $apiKeyProvider = $this->instantiateAuthKeyProvider();
        $apiKey = $apiKeyProvider->get($currentScope);

        $this->assertNull($apiKey);
    }

    /**
     * @magentoConfigFixture default/klevu_configuration/auth_keys/rest_auth_key not-this-one
     * @magentoConfigFixture klevu_test_website_1_website klevu_configuration/auth_keys/rest_auth_key site-rest-auth-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/rest_auth_key store-rest-auth-key
     */
    public function testGet_ReturnsRestAuthKey_ForStore(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store')->get();

        $currentScope = $this->createCurrentScope($store);

        $authKeyProvider = $this->instantiateAuthKeyProvider();
        $authKey = $authKeyProvider->get($currentScope);

        $this->assertSame(expected: 'store-rest-auth-key', actual: $authKey);
    }

    public function testGet_throwsException_ForNonexistentWebsiteId(): void
    {
        $this->expectException(LocalizedException::class);
        $mockWebsite = $this->getMockBuilder(WebsiteInterface::class)
            ->getMock();
        $mockWebsite->method('getId')
            ->willReturn(8269826578);

        $currentScope = $this->createCurrentScope($mockWebsite);

        $authKeyProvider = $this->instantiateAuthKeyProvider();
        $authKeyProvider->get($currentScope);
    }

    public function testGet_ReturnsNull_WhenWebsiteConfigNotSet(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website')->get();

        $currentScope = $this->createCurrentScope($website);

        $authKeyProvider = $this->instantiateAuthKeyProvider();
        $authKey = $authKeyProvider->get($currentScope);

        $this->assertNull($authKey);
    }

    /**
     * @magentoConfigFixture default/klevu_configuration/auth_keys/rest_auth_key not-this-one
     * @magentoConfigFixture klevu_test_website_1_website klevu_configuration/auth_keys/rest_auth_key site-rest-auth-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/rest_auth_key store-rest-auth-key
     */
    public function testGet_ReturnsRestAuthKey_ForWebsite(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website')->get();

        $currentScope = $this->createCurrentScope($website);

        $authKeyProvider = $this->instantiateAuthKeyProvider();
        $apiKey = $authKeyProvider->get($currentScope);

        $this->assertSame(expected: 'site-rest-auth-key', actual: $apiKey);
    }

    public function testGetForApiKey_ReturnsNull_WhenAuthKeyNotSet(): void
    {
        $jsApiKey = 'klevu-js-api-key';

        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: $jsApiKey,
        );
        $authKeyProvider = $this->instantiateAuthKeyProvider();
        $authKey = $authKeyProvider->getForApiKey('klevu-js-api-key');

        $this->assertNull(actual: $authKey);
    }

    public function testGetForApiKey_ThrowsException_WhenApiNotSet(): void
    {
        $this->expectException(ApiKeyNotFoundException::class);
        $this->expectExceptionMessage('Requested API Key not found (klevu-js-api-key)');

        $authKeyProvider = $this->instantiateAuthKeyProvider();
        $authKeyProvider->getForApiKey('klevu-js-api-key');
    }

    public function testGetForApiKey_ReturnsAuthKey_WhenBothKeysSet(): void
    {
        $jsApiKey = 'klevu-js-api-key';
        $restAuthKey = 'klevu-rest-auth-key';

        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: $jsApiKey,
            restAuthKey: $restAuthKey,
        );

        $authKeyProvider = $this->instantiateAuthKeyProvider();
        $authKey = $authKeyProvider->getForApiKey($jsApiKey);

        $this->assertSame(expected: $restAuthKey, actual: $authKey);
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return AuthKeyProvider
     */
    private function instantiateAuthKeyProvider(?array $arguments = []): AuthKeyProvider
    {
        return $this->objectManager->create(
            type: AuthKeyProvider::class,
            arguments: $arguments,
        );
    }
}
