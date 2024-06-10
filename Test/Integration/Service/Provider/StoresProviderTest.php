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
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers StoresProvider
 * @method StoresProviderInterface instantiateTestObject(?array $arguments = null)
 * @method StoresProviderInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class StoresProviderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use SetAuthKeysTrait;
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
        $this->assertArrayHasKey(key: $defaultStore->getId(), array: $result);
        $filteredResult = array_filter(
            array: $result,
            callback: static fn (StoreInterface $store): bool => (
                (int)$store->getId() === (int)$defaultStore->getId()
            ),
        );
        $this->assertCount(expectedCount: 1, haystack: $filteredResult);
    }
}
