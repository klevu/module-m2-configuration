<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Provider;

use Klevu\Configuration\Service\Provider\ApiKeyProviderInterface;
use Klevu\Configuration\Service\Provider\ApiKeysProvider;
use Klevu\Configuration\Service\Provider\ApiKeysProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Klevu\Configuration\Service\Provider\ApiKeysProvider::class
 * @method ApiKeysProviderInterface instantiateTestObject(?array $arguments = null)
 * @method ApiKeysProviderInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class ApiKeysProviderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;
    use TestInterfacePreferenceTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = ApiKeysProvider::class;
        $this->interfaceFqcn = ApiKeysProviderInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->storeFixturesPool->rollback();
    }

    public function testGet_ReturnsEmptyArray_WhenEmptyNull(): void
    {
        $provider = $this->instantiateTestObject();
        $apiKeys = $provider->get(null);

        $this->assertEmpty(actual: $apiKeys);
    }

    public function testGet_ReturnsEmptyArray_WhenNoStoresAreIntegrated(): void
    {
        $this->createStore([
            'code' => 'klevu_test_store_without_apikey',
        ]);
        $store = $this->storeFixturesPool->get('test_store');

        $provider = $this->instantiateTestObject();
        $apiKeys = $provider->get([
            (int)$store->getId(),
        ]);

        $this->assertEmpty(actual: $apiKeys);
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key-1
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key-1
     * @magentoConfigFixture klevu_test_store_2_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key-2
     * @magentoConfigFixture klevu_test_store_2_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key-2
     */
    public function testGet_ReturnsArrayOfKeysForRequestedStores(): void
    {
        $this->createStore([
            'code' => 'klevu_test_store_1',
            'key' => 'test_store_1',
        ]);
        $storeFixture1 = $this->storeFixturesPool->get('test_store_1');
        $this->createStore([
            'code' => 'klevu_test_store_2',
            'key' => 'test_store_2',
        ]);
        $storeFixture2 = $this->storeFixturesPool->get('test_store_2');

        $provider = $this->instantiateTestObject();
        $apiKeys = $provider->get([
            (int)$storeFixture1->getId(),
            (int)$storeFixture2->getId(),
        ]);

        $this->assertCount(expectedCount: 2, haystack: $apiKeys);
        $this->assertContains(needle: 'klevu-js-api-key-1', haystack: $apiKeys);
        $this->assertContains(needle: 'klevu-js-api-key-2', haystack: $apiKeys);
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key
     * @magentoConfigFixture klevu_test_store_2_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_2_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key
     */
    public function testGet_ReturnsArrayOfMergedKeysForRequestedStores(): void
    {
        $this->createStore([
            'code' => 'klevu_test_store_1',
            'key' => 'test_store_1',
        ]);
        $storeFixture1 = $this->storeFixturesPool->get('test_store_1');
        $this->createStore([
            'code' => 'klevu_test_store_2',
            'key' => 'test_store_2',
        ]);
        $storeFixture2 = $this->storeFixturesPool->get('test_store_2');

        $provider = $this->instantiateTestObject();
        $apiKeys = $provider->get([
            (int)$storeFixture1->getId(),
            (int)$storeFixture2->getId(),
        ]);

        $this->assertCount(expectedCount: 1, haystack: $apiKeys);
        $this->assertContains(needle: 'klevu-js-api-key', haystack: $apiKeys);
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key
     * @magentoConfigFixture klevu_test_store_2_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_2_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key
     */
    public function testGet_ReturnsArrayOfMergedKeys_WhenEmptyArrayPassed(): void
    {
        $this->createStore([
            'code' => 'klevu_test_store_1',
            'key' => 'test_store_1',
        ]);
        $this->createStore([
            'code' => 'klevu_test_store_2',
            'key' => 'test_store_2',
        ]);

        $provider = $this->instantiateTestObject();
        $apiKeys = $provider->get([]);

        $this->assertCount(expectedCount: 1, haystack: $apiKeys);
        $this->assertContains(needle: 'klevu-js-api-key', haystack: $apiKeys);
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key
     * @magentoConfigFixture klevu_test_store_2_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_2_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key
     */
    public function testGet_ReturnsEmptyArray_WhenNullPassed(): void
    {
        $this->createStore([
            'code' => 'klevu_test_store_1',
            'key' => 'test_store_1',
        ]);
        $this->createStore([
            'code' => 'klevu_test_store_2',
            'key' => 'test_store_2',
        ]);

        $provider = $this->instantiateTestObject();
        $apiKeys = $provider->get();

        $this->assertEmpty(actual: $apiKeys);
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key
     */
    public function testGet_LogsNoSuchEntityException_WhenRetrievingApiKeys(): void
    {
        $exceptionMessage = 'No Such Entity Exception Message';

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'Method: {method} - Error: {message}',
                [
                    'method' => 'Klevu\Configuration\Service\Provider\ApiKeysProvider::get',
                    'message' => $exceptionMessage,
                ],
            );
        $mockApiKeyProvider = $this->getMockBuilder(ApiKeyProviderInterface::class)
            ->getMock();
        $mockApiKeyProvider->expects($this->once())
            ->method('get')
            ->willThrowException(new NoSuchEntityException(__($exceptionMessage)));

        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $provider = $this->instantiateTestObject([
            'logger' => $mockLogger,
            'apiKeyProvider' => $mockApiKeyProvider,
        ]);
        $provider->get([
            (int)$store->getId(),
        ]);
    }
}
