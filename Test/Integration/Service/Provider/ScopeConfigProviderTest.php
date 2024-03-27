<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider;

use Klevu\Configuration\Service\Provider\ScopeConfigProvider;
use Klevu\Configuration\Service\Provider\ScopeConfigProviderInterface;
use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers ScopeConfigProvider
 * @method ScopeConfigProviderInterface instantiateTestObject(?array $arguments = null)
 * @method ScopeConfigProviderInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class ScopeConfigProviderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = ScopeConfigProvider::class;
        $this->interfaceFqcn = ScopeConfigProviderInterface::class;
        $this->constructorArgumentDefaults = [
            'path' => '',
        ];
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
     * @magentoAppArea frontend
     */
    public function testGet_ReturnsEmptyString_ValueNotSet_NoReturnType(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject([
            'path' => 'klevu_test/some/path',
        ]);
        $value = $provider->get();

        $this->assertNull(actual: $value);
    }

    /**
     * @magentoAppArea frontend
     */
    public function testGet_ReturnsNull_ValueNotSet_Returns(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject([
            'path' => 'klevu_test/some/path',
            'returnType' => ScopeConfigProvider::TYPE_STRING,
        ]);
        $value = $provider->get();

        $this->assertNull(actual: $value);
    }

    /**
     * @magentoAppArea frontend
     */
    public function testGet_ReturnsNull_ValueNotSet_ReturnTypeInteger(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject([
            'path' => 'klevu_test/some/path',
            'returnType' => ScopeConfigProvider::TYPE_INTEGER,
        ]);
        $value = $provider->get();

        $this->assertNull(actual: $value);
    }

    /**
     * @magentoAppArea frontend
     */
    public function testGet_ReturnsNull_ValueNotSet_ReturnTypeBoolean(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject([
            'path' => 'klevu_test/some/path',
            'returnType' => ScopeConfigProvider::TYPE_BOOLEAN,
        ]);
        $value = $provider->get();

        $this->assertNull(actual: $value);
    }

    /**
     * @magentoAppArea frontend
     */
    public function testGet_ReturnsNull_ValueNotSet_ReturnTypeFloat(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject([
            'path' => 'klevu_test/some/path',
            'returnType' => ScopeConfigProvider::TYPE_FLOAT,
        ]);
        $value = $provider->get();

        $this->assertNull(actual: $value);
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/js_api_key 12345
     */
    public function testGet_ReturnsString_WhenReturnTypeString(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject([
            'path' => 'klevu_configuration/auth_keys/js_api_key',
            'returnType' => ScopeConfigProvider::TYPE_STRING,
        ]);
        $value = $provider->get();

        $this->assertSame(expected: '12345', actual: $value);
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture klevu_test_store_1_store klevu_test/some/path 123456
     */
    public function testGet_ReturnsInt_WhenReturnTypeInt(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject([
            'path' => 'klevu_test/some/path',
            'returnType' => ScopeConfigProvider::TYPE_INTEGER,
        ]);
        $value = $provider->get();

        $this->assertSame(expected: 123456, actual: $value);
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture klevu_test_store_1_store klevu_test/some/path 1234.56
     */
    public function testGet_ReturnsFloat_WhenReturnTypeFloat(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject([
            'path' => 'klevu_test/some/path',
            'returnType' => ScopeConfigProvider::TYPE_FLOAT,
        ]);
        $value = $provider->get();

        $this->assertSame(expected: 1234.56, actual: $value);
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture klevu_test_store_1_store klevu_test/some/path 124
     */
    public function testGet_ReturnsTrue_WhenReturnTypeBool_ValueTruthy(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject([
            'path' => 'klevu_test/some/path',
            'returnType' => ScopeConfigProvider::TYPE_BOOLEAN,
        ]);
        $value = $provider->get();

        $this->assertTrue(condition: $value);
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture klevu_test_store_1_store klevu_test/some/path false
     */
    public function testGet_ReturnsFalse_WhenReturnTypeBool_ValueStringFalse(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject([
            'path' => 'klevu_test/some/path',
            'returnType' => ScopeConfigProvider::TYPE_BOOLEAN,
        ]);
        $value = $provider->get();

        $this->assertFalse(condition: $value);
    }
}
