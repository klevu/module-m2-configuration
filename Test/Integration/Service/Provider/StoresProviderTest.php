<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Provider;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Configuration\Service\Provider\StoresProvider;
use Klevu\Configuration\Service\Provider\StoresProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers StoresProvider
 * @runTestsInSeparateProcesses
 */
class StoresProviderTest extends TestCase
{
    use SetAuthKeysTrait;
    use StoreTrait;

    /**
     * @var string|null
     */
    private ?string $implementationFqcn = null;
    /**
     * @var string|null
     */
    private ?string $interfaceFqcn = null;
    /**
     * @var mixed[]|null
     */
    private ?array $constructorArgumentDefaults = null;
    /**
     * @var string|null
     */
    private ?string $implementationForVirtualType = null;
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

        $this->implementationFqcn = StoresProvider::class;
        $this->interfaceFqcn = StoresProviderInterface::class;
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

    /**
     * @magentoAppIsolation enabled
     */
    public function testGet_ReturnsEmptyArray_WhenNoStoresWithKey(): void
    {
        $provider = $this->instantiateTestObject();
        $result = $provider->get('klevu-test-key');

        $this->assertEmpty($result);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGet_ReturnsArrayOfStores_WithRequestedApiKey(): void
    {
        $this->createStore([
            'key' => 'test_store_1',
            'code' => 'klevu_test_store_1',
        ]);
        $storeFixture1 = $this->storeFixturesPool->get('test_store_1');
        $scopeProvider1 = $this->objectManager->create(ScopeProviderInterface::class);
        $scopeProvider1->setCurrentScope(scope: $storeFixture1->get());
        $this->setAuthKeys(
            scopeProvider: $scopeProvider1,
            jsApiKey: 'klevu-js-api-key-1',
            restAuthKey: 'klevu-rest-key-1',
        );

        $this->createStore([
            'key' => 'test_store_2',
            'code' => 'klevu_test_store_2',
        ]);
        $storeFixture2 = $this->storeFixturesPool->get('test_store_2');
        $scopeProvider2 = $this->objectManager->create(ScopeProviderInterface::class);
        $scopeProvider2->setCurrentScope(scope: $storeFixture2->get());
        $this->setAuthKeys(
            scopeProvider: $scopeProvider2,
            jsApiKey: 'klevu-js-api-key-2',
            restAuthKey: 'klevu-rest-key-2',
            removeApiKeys: false,
        );

        $this->createStore([
            'key' => 'test_store_3',
            'code' => 'klevu_test_store_3',
        ]);
        $storeFixture3 = $this->storeFixturesPool->get('test_store_3');
        $scopeProvider3 = $this->objectManager->create(ScopeProviderInterface::class);
        $scopeProvider3->setCurrentScope(scope: $storeFixture3->get());
        $this->setAuthKeys(
            scopeProvider: $scopeProvider3,
            jsApiKey: 'klevu-js-api-key-1',
            restAuthKey: 'klevu-rest-key-1',
            removeApiKeys: false,
        );

        $provider = $this->instantiateTestObject();
        $result = $provider->get('klevu-js-api-key-1');
        $this->assertArrayHasKey(key: $storeFixture1->getId(), array: $result);
        $filteredResult1 = array_filter(
            array: $result,
            callback: static fn (StoreInterface $store): bool => (
                (int)$store->getId() === (int)$storeFixture1->getId()
            ),
        );
        $this->assertCount(expectedCount: 1, haystack: $filteredResult1);

        $this->assertArrayNotHasKey(key: $storeFixture2->getId(), array: $result);
        $filteredResult2 = array_filter(
            array: $result,
            callback: static fn (StoreInterface $store): bool => (
                (int)$store->getId() === (int)$storeFixture2->getId()
            ),
        );
        $this->assertCount(expectedCount: 0, haystack: $filteredResult2);

        $this->assertArrayHasKey(key: $storeFixture3->getId(), array: $result);
        $filteredResult3 = array_filter(
            array: $result,
            callback: static fn (StoreInterface $store): bool => (
                (int)$store->getId() === (int)$storeFixture3->getId()
            ),
        );
        $this->assertCount(expectedCount: 1, haystack: $filteredResult3);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGet_ReturnsArrayOfStores_WithRequestedApiKey_InSingleStoreMode(): void
    {
        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 1,
        );
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $defaultStore = $storeManager->getDefaultStoreView();
        $scopeProvider = $this->objectManager->create(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope(scope: $defaultStore);
        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-api-key',
            restAuthKey: 'klevu-rest-key',
        );

        $provider = $this->instantiateTestObject();
        $result = $provider->get('klevu-js-api-key');

        $this->removeAuthKeys();

        $this->assertArrayHasKey(key: $defaultStore->getId(), array: $result);
        $filteredResult = array_filter(
            array: $result,
            callback: static fn (StoreInterface $store): bool => (
                (int)$store->getId() === (int)$defaultStore->getId()
            ),
        );
        $this->assertCount(expectedCount: 1, haystack: $filteredResult);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetAllIntegratedStores(): void
    {
        $this->createStore(
            storeData: [
                'key' => 'klevu_test_storesprovider_1',
                'code' => 'klevu_test_storesprovider_1',
                'name' => 'Klevu Test: Stores Provider (1)',
                'enabled' => true,
            ],
        );
        $storeFixture1 = $this->storeFixturesPool->get('klevu_test_storesprovider_1');
        $scopeProvider1 = $this->objectManager->create(ScopeProviderInterface::class);
        $scopeProvider1->setCurrentScope(scope: $storeFixture1->get());
        $this->setAuthKeys(
            scopeProvider: $scopeProvider1,
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
            removeApiKeys: false,
        );

        $this->createStore(
            storeData: [
                'key' => 'klevu_test_storesprovider_2',
                'code' => 'klevu_test_storesprovider_2',
                'name' => 'Klevu Test: Stores Provider (2)',
                'is_active' => false,
            ],
        );
        $storeFixture2 = $this->storeFixturesPool->get('klevu_test_storesprovider_2');
        $scopeProvider2 = $this->objectManager->create(ScopeProviderInterface::class);
        $scopeProvider2->setCurrentScope(scope: $storeFixture2->get());
        $this->setAuthKeys(
            scopeProvider: $scopeProvider2,
            jsApiKey: 'klevu-9876543210',
            restAuthKey: 'ABCDE9876543210',
            removeApiKeys: false,
        );

        $this->createStore(
            storeData: [
                'key' => 'klevu_test_storesprovider_3',
                'code' => 'klevu_test_storesprovider_3',
                'name' => 'Klevu Test: Stores Provider (3)',
                'is_active' => true,
            ],
        );

        /** @var StoresProviderInterface $storesProvider */
        $storesProvider = $this->instantiateTestObject();

        $result = $storesProvider->getAllIntegratedStores();

        $this->assertArrayHasKey(
            key: 'klevu-1234567890',
            array: $result,
        );
        $this->assertIsArray($result['klevu-1234567890']);
        $this->assertCount(
            expectedCount: 1,
            haystack: $result['klevu-1234567890'],
        );
        $this->assertArrayHasKey(
            key: 0,
            array: $result['klevu-1234567890'],
        );
        $this->assertInstanceOf(
            expected: StoreInterface::class,
            actual: $result['klevu-1234567890'][0],
        );
        $this->assertSame(
            expected: 'klevu_test_storesprovider_1',
            actual: $result['klevu-1234567890'][0]->getCode(),
        );

        $this->assertArrayHasKey(
            key: 'klevu-9876543210',
            array: $result,
        );
        $this->assertIsArray($result['klevu-9876543210']);
        $this->assertCount(
            expectedCount: 1,
            haystack: $result['klevu-9876543210'],
        );
        $this->assertArrayHasKey(
            key: 0,
            array: $result['klevu-9876543210'],
        );
        $this->assertInstanceOf(
            expected: StoreInterface::class,
            actual: $result['klevu-9876543210'][0],
        );
        $this->assertSame(
            expected: 'klevu_test_storesprovider_2',
            actual: $result['klevu-9876543210'][0]->getCode(),
        );

        $storeFixture3 = $this->storeFixturesPool->get('klevu_test_storesprovider_3');
        $scopeProvider3 = $this->objectManager->create(ScopeProviderInterface::class);
        $scopeProvider3->setCurrentScope(scope: $storeFixture3->get());
        $this->setAuthKeys(
            scopeProvider: $scopeProvider3,
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
            removeApiKeys: false,
        );

        $resultAfterStoreFixture3 = $storesProvider->getAllIntegratedStores();

        $this->assertSame(
            expected: $result,
            actual: $resultAfterStoreFixture3,
        );

        $storesProvider->cleanCache();
        $resultAfterCleanCache = $storesProvider->getAllIntegratedStores();

        $this->assertArrayHasKey(
            key: 'klevu-1234567890',
            array: $result,
        );
        $this->assertIsArray($resultAfterCleanCache['klevu-1234567890']);
        $this->assertCount(
            expectedCount: 2,
            haystack: $resultAfterCleanCache['klevu-1234567890'],
        );
        $this->assertArrayHasKey(
            key: 0,
            array: $resultAfterCleanCache['klevu-1234567890'],
        );
        $this->assertInstanceOf(
            expected: StoreInterface::class,
            actual: $resultAfterCleanCache['klevu-1234567890'][0],
        );
        $this->assertSame(
            expected: 'klevu_test_storesprovider_1',
            actual: $resultAfterCleanCache['klevu-1234567890'][0]->getCode(),
        );
        $this->assertArrayHasKey(
            key: 1,
            array: $resultAfterCleanCache['klevu-1234567890'],
        );
        $this->assertInstanceOf(
            expected: StoreInterface::class,
            actual: $resultAfterCleanCache['klevu-1234567890'][1],
        );
        $this->assertSame(
            expected: 'klevu_test_storesprovider_3',
            actual: $resultAfterCleanCache['klevu-1234567890'][1]->getCode(),
        );

        $this->assertArrayHasKey(
            key: 'klevu-9876543210',
            array: $resultAfterCleanCache,
        );
        $this->assertIsArray($resultAfterCleanCache['klevu-9876543210']);
        $this->assertCount(
            expectedCount: 1,
            haystack: $resultAfterCleanCache['klevu-9876543210'],
        );
        $this->assertArrayHasKey(
            key: 0,
            array: $resultAfterCleanCache['klevu-9876543210'],
        );
        $this->assertInstanceOf(
            expected: StoreInterface::class,
            actual: $resultAfterCleanCache['klevu-9876543210'][0],
        );
        $this->assertSame(
            expected: 'klevu_test_storesprovider_2',
            actual: $resultAfterCleanCache['klevu-9876543210'][0]->getCode(),
        );
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return object
     * @throws \LogicException
     *
     * @todo Reinstate object instantiation and interface traits. Removed as causing serialization of Closure error
     *  in phpunit Standard input code
     */
    private function instantiateTestObject(
        ?array $arguments = null,
    ): object {
        if (!$this->implementationFqcn) {
            throw new \LogicException('Cannot instantiate test object: no implementationFqcn defined');
        }
        if (null === $arguments) {
            $arguments = $this->constructorArgumentDefaults;
        }

        return (null === $arguments)
            ? $this->objectManager->get($this->implementationFqcn)
            : $this->objectManager->create($this->implementationFqcn, $arguments);
    }
}
