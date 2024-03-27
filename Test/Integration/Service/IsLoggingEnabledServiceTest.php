<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service;

use Klevu\Configuration\Service\IsLoggingEnabledService as IsLoggingEnabledServiceVirtualType;
use Klevu\Configuration\Service\Provider\StoreScopeProviderInterface;
use Klevu\Logger\Service\IsLoggingEnabledService;
use Klevu\LoggerApi\Service\IsLoggingEnabledServiceInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\IsLoggingEnabledService
 */
class IsLoggingEnabledServiceTest extends TestCase
{
    use StoreTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;
    /**
     * @var StoreScopeProviderInterface|null
     */
    private ?StoreScopeProviderInterface $storeScopeProvider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->storeFixturesPool = $this->objectManager->create(StoreFixturesPool::class);
        $this->storeScopeProvider = $this->objectManager->get(StoreScopeProviderInterface::class);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->storeFixturesPool->rollback();
    }

    public function testImplements_LoggingEnabledServiceInterface(): void
    {
        $isLoggingEnabledService = $this->instantiateLoggingEnabled();
        $this->assertInstanceOf(IsLoggingEnabledServiceInterface::class, $isLoggingEnabledService);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_configuration/developer/log_level_configuration 500
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/log_level_configuration 200
     * @return void
     */
    public function testExecute_ReturnsTrue_IfLogLevelIsMoreThenMinLevel_ForStore(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());
        $currentStore = $this->storeScopeProvider->getCurrentStore();

        $isLoggingEnabledService = $this->instantiateLoggingEnabled();

        $this->assertTrue($isLoggingEnabledService->execute(400, $currentStore));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_configuration/developer/log_level_configuration 500
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/log_level_configuration 400
     * @return void
     */
    public function testExecute_ReturnsTrue_IfLogLevelIsEqualToMinLevel_ForStore(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());
        $currentStore = $this->storeScopeProvider->getCurrentStore();
        $isLoggingEnabledService = $this->instantiateLoggingEnabled();

        $this->assertTrue($isLoggingEnabledService->execute(400, $currentStore));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store general/single_store_mode/enabled 1
     * @magentoConfigFixture default/klevu_configuration/developer/log_level_configuration 400
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/log_level_configuration 400
     * @return void
     */
    public function testExecute_ReturnsTrue_IfLogLevelIsEqualToMinLevel_WhenSSMIsEnabled_ForStore(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());
        $currentStore = $this->storeScopeProvider->getCurrentStore();
        $isLoggingEnabledService = $this->instantiateLoggingEnabled();

        $this->assertTrue($isLoggingEnabledService->execute(400, $currentStore));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_configuration/developer/log_level_configuration 100
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/log_level_configuration 400
     */
    public function testExecute_ReturnsFalse_IfLogLevelIsLessThanMinLevel_ForStore(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());
        $currentStore = $this->storeScopeProvider->getCurrentStore();
        $isLoggingEnabledService = $this->instantiateLoggingEnabled();

        $this->assertFalse($isLoggingEnabledService->execute(200, $currentStore));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     */
    public function testExecute_ReturnsTrue_IfLogLevelIsNotSet(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());
        $currentStore = $this->storeScopeProvider->getCurrentStore();
        $isLoggingEnabledService = $this->instantiateLoggingEnabled();
        $this->assertTrue($isLoggingEnabledService->execute(400, $currentStore));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_configuration/developer/log_level_configuration 200
     */
    public function testExecute_ReturnsTrue_IfLogLevelIsNotSet_ForStore(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());
        $currentStore = $this->storeScopeProvider->getCurrentStore();
        $isLoggingEnabledService = $this->instantiateLoggingEnabled();
        $this->assertTrue($isLoggingEnabledService->execute(400, $currentStore));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_configuration/developer/log_level_configuration 200
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/log_level_configuration 200
     * @return void
     */
    public function testExecute_ReturnsTrue_ConfigPathIsNotSet(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());
        $currentStore = $this->storeScopeProvider->getCurrentStore();

        $isLoggingEnabledService = $this->instantiateLoggingEnabled([
            'minLogLevelConfigPath' => '',
        ]);

        $this->assertTrue($isLoggingEnabledService->execute(200, $currentStore));
    }

    /**
     * @param mixed[]|null $params
     *
     * @return IsLoggingEnabledService
     */
    private function instantiateLoggingEnabled(?array $params = []): IsLoggingEnabledService
    {
        return $this->objectManager->create(IsLoggingEnabledServiceVirtualType::class, $params);// @phpstan-ignore-line
    }
}
