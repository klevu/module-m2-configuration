<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Provider;

use Klevu\Configuration\Service\Provider\ApiKeyProvider;
use Klevu\Configuration\Service\Provider\ApiKeyProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\CurrentScopeTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\Provider\ApiKeyProvider
 * @magentoAppIsolation enabled
 */
class ApiKeyProviderTest extends TestCase
{
    use CurrentScopeTrait;
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
        $apiKeyProvider = $this->instantiateApiKeyProvider();

        $this->assertInstanceOf(ApiKeyProviderInterface::class, $apiKeyProvider);
    }

    public function testPreference_ForAuthKeysInterface(): void
    {
        $apiKeyProvider = $this->objectManager->create(ApiKeyProviderInterface::class);

        $this->assertInstanceOf(ApiKeyProvider::class, $apiKeyProvider);
    }

    public function testGet_throwsException_ForNonexistentStoreId(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $mockStore = $this->getMockBuilder(StoreInterface::class)
            ->getMock();
        $mockStore->method('getId')
            ->willReturn(8269826578);
        $currentScope = $this->createCurrentScope($mockStore);

        $apiKeyProvider = $this->instantiateApiKeyProvider();
        $apiKeyProvider->get($currentScope);
    }

    public function testGet_ReturnsNull_WhenStoreConfigNotSet(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store')->get();
        $currentScope = $this->createCurrentScope($store);

        $apiKeyProvider = $this->instantiateApiKeyProvider();
        $apiKey = $apiKeyProvider->get($currentScope);

        $this->assertNull($apiKey);
    }

    /**
     * @magentoConfigFixture default/klevu_configuration/auth_keys/js_api_key not-this-one
     * @magentoConfigFixture klevu_test_website_1_website klevu_configuration/auth_keys/js_api_key site-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/js_api_key store-js-api-key
     */
    public function testGet_ReturnsJsApiKey_ForStore(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store')->get();
        $currentScope = $this->createCurrentScope($store);

        $apiKeyProvider = $this->instantiateApiKeyProvider();
        $apiKey = $apiKeyProvider->get($currentScope);

        $this->assertSame('store-js-api-key', $apiKey);
    }

    public function testGet_throwsException_ForNonexistentWebsiteId(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $mockWebsite = $this->getMockBuilder(WebsiteInterface::class)
            ->getMock();
        $mockWebsite->method('getId')
            ->willReturn(8269826578);
        $currentScope = $this->createCurrentScope($mockWebsite);

        $apiKeyProvider = $this->instantiateApiKeyProvider();
        $apiKeyProvider->get($currentScope);
    }

    public function testGet_ReturnsNull_WhenWebsiteConfigNotSet(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website')->get();
        $currentScope = $this->createCurrentScope($website);

        $apiKeyProvider = $this->instantiateApiKeyProvider();
        $apiKey = $apiKeyProvider->get($currentScope);

        $this->assertNull($apiKey);
    }

    /**
     * @magentoConfigFixture default/klevu_configuration/auth_keys/js_api_key not-this-one
     * @magentoConfigFixture klevu_test_website_1_website klevu_configuration/auth_keys/js_api_key site-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/js_api_key store-js-api-key
     */
    public function testGet_ReturnsJsApiKey_ForWebsite(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website')->get();
        $currentScope = $this->createCurrentScope($website);

        $apiKeyProvider = $this->instantiateApiKeyProvider();
        $apiKey = $apiKeyProvider->get($currentScope);

        $this->assertSame('site-js-api-key', $apiKey);
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return ApiKeyProvider
     */
    private function instantiateApiKeyProvider(?array $arguments = []): ApiKeyProvider
    {
        return $this->objectManager->create(
            type: ApiKeyProvider::class,
            arguments: $arguments,
        );
    }
}
