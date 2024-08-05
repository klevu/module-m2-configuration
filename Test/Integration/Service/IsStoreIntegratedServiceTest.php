<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service;

use Klevu\Configuration\Service\IsStoreIntegratedService;
use Klevu\Configuration\Service\IsStoreIntegratedServiceInterface;
use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\Configuration\Service\IsStoreIntegratedService
 */
class IsStoreIntegratedServiceTest extends TestCase
{
    use ObjectInstantiationTrait;
    use SetAuthKeysTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;
    use TestInterfacePreferenceTrait;
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
        parent::setUp();

        $this->implementationFqcn = IsStoreIntegratedService::class;
        $this->interfaceFqcn = IsStoreIntegratedServiceInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
        $this->websiteFixturesPool = $this->objectManager->get(WebsiteFixturesPool::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->storeFixturesPool->rollback();
        $this->websiteFixturesPool->rollback();
    }

    public function testExecute_ReturnsFalse_AtGlobalScope(): void
    {
        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertFalse($service->execute());
    }

    public function testExecute_ReturnsFalse_AtWebsiteScope(): void
    {
        $this->createWebsite();
        $websiteFixture = $this->websiteFixturesPool->get('test_website');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope(scope: $websiteFixture->get());

        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertFalse(condition: $service->execute());
    }

    public function testExecute_ReturnsFalse_AtStoreScope_NotIntegrated(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope(scope: $storeFixture->get());

        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertFalse(condition: $service->execute());
    }

    public function testExecute_ReturnsFalse_AtStoreScope_OnlyJsApiKey(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope(scope: $storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-key',
        );

        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertFalse(condition: $service->execute());
    }

    public function testExecute_ReturnsFalse_AtStoreScope_OnlyRestAuthKey(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope(scope: $storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            restAuthKey: 'klevu-rest-key',
        );

        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertFalse(condition: $service->execute());
    }

    public function testExecute_ReturnsTrue_AtStoreScope_WhenIntegrated(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope(scope: $storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-key',
            restAuthKey: 'klevu-rest-key',
        );

        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertTrue(condition: $service->execute());
    }

    public function testExecute_ReturnsTrue_AtStoreScope_WhenIntegratedAtWebsiteScope(): void
    {
        $this->createWebsite();
        $websiteFixture = $this->websiteFixturesPool->get('test_website');
        $this->createStore([
            'website_id' => $websiteFixture->getId(),
        ]);
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope(scope: $websiteFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider, // set at website scope
            jsApiKey: 'klevu-js-key',
            restAuthKey: 'klevu-rest-key',
        );

        $scopeProvider->setCurrentScope(scope: $storeFixture->get()); // scope is store

        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertFalse(condition: $service->execute()); // @TODO change to true when channels are available
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecute_ReturnsTrue_AtStoreScope_WhenIntegrated_SingleStoreMode(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope(scope: $storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-key',
            restAuthKey: 'klevu-rest-key',
        );

        ConfigFixture::setForStore(
            path: 'general/single_store_mode/enabled',
            value: 1,
            storeCode: $storeFixture->getCode(),
        );

        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertTrue(condition: $service->execute());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecute_ReturnsTrue_AtWebsiteScope_WhenIntegrated_SingleStoreMode(): void
    {
        $this->markTestSkipped('Skipped until website integration is possible');
        $this->createStore(); // @phpstan-ignore-line - see test skipped
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $store = $storeFixture->get();
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $website = $storeManager->getWebsite($store->getWebsiteId());
        $scopeProvider->setCurrentScope(scope: $website);

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-key',
            restAuthKey: 'klevu-rest-key',
        );

        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 0,
        );

        ConfigFixture::setForStore(
            path: 'general/single_store_mode/enabled',
            value: 1,
            storeCode: $store->getCode(),
        );

        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertTrue(condition: $service->execute());
    }
}
