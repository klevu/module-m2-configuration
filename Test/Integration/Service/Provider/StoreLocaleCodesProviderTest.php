<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Provider;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Configuration\Service\Provider\StoreLocaleCodesProvider;
use Klevu\Configuration\Service\Provider\StoreLocaleCodesProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers StoreLocaleCodesProvider
 * @runTestsInSeparateProcesses
 */
class StoreLocaleCodesProviderTest extends TestCase
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

        $this->implementationFqcn = StoreLocaleCodesProvider::class;
        $this->interfaceFqcn = StoreLocaleCodesProviderInterface::class;
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
     * @magentoConfigFixture default/general/locale/code en_US
     * @magentoConfigFixture klevu_test_store_storelocale_store general/locale/code en_GB
     */
    public function testGetByStore_ReturnsString_WithLocalCodeForRequestedStore(): void
    {
        $this->createStore(
            storeData: [
                'code' => 'klevu_test_store_storelocale',
                'name' => 'Klevu Test Store StoreLocaleCodesProvider: ' . __METHOD__,
                'is_active' => true,
                'key' => 'klevu_test_store_storelocale',
            ],
        );

        ConfigFixture::setGlobal(
            path: 'general/locale/code',
            value: 'en_US',
        );
        ConfigFixture::setForStore(
            path: 'general/locale/code',
            value: 'en_GB',
            storeCode: 'klevu_test_store_storelocale',
        );

        $storeFixture = $this->storeFixturesPool->get('klevu_test_store_storelocale');

        $provider = $this->instantiateTestObject();
        $result = $provider->getByStore($storeFixture->get());

        $this->assertSame(
            expected: 'en-GB-klevu_test_store_storelocale',
            actual: $result,
        );
    }

    public function testGet_ReturnsArray_ContainingAllLocalCodes(): void
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

        ConfigFixture::setGlobal(
            path: 'general/locale/code',
            value: 'en_US',
        );
        ConfigFixture::setForStore(
            path: 'general/locale/code',
            value: 'en_GB',
            storeCode: $storeFixture1->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'general/locale/code',
            value: 'fr_FR',
            storeCode: $storeFixture2->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'general/locale/code',
            value: 'de_DE',
            storeCode: $storeFixture3->getCode(),
        );

        $provider = $this->instantiateTestObject();
        $result = $provider->get('klevu-js-api-key-1');

        $this->assertArrayHasKey(key: $storeFixture1->getId(), array: $result);
        $this->assertSame(
            expected: 'en-GB-klevu_test_store_1',
            actual: $result[$storeFixture1->getId()],
        );

        $this->assertArrayNotHasKey(key: $storeFixture2->getId(), array: $result);

        $this->assertArrayHasKey(key: $storeFixture3->getId(), array: $result);
        $this->assertSame(
            expected: 'de-DE-klevu_test_store_3',
            actual: $result[$storeFixture3->getId()],
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
