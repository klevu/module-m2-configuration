<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Provider;

use Klevu\Configuration\Service\Provider\StoreScopeProvider;
use Klevu\Configuration\Service\Provider\StoreScopeProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\SetAreaTrait;
use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\Configuration\Service\Provider\StoreScopeProvider
 * @magentoAppIsolation enabled
 * @runTestsInSeparateProcesses
 */
class StoreScopeProviderTest extends TestCase
{
    use SetAreaTrait;
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
        $this->storeFixturesPool = $this->objectManager->create(StoreFixturesPool::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        $this->storeFixturesPool->rollback();
    }

    public function testImplements_StoreScopeProviderInterface(): void
    {
        $storeScopeProvider = $this->instantiateStoreScopeProvider();
        $this->assertInstanceOf(StoreScopeProviderInterface::class, $storeScopeProvider);
    }

    public function testPreferenceFor_StoreScopeProviderInterface(): void
    {
        $storeScopeProvider = $this->objectManager->create(StoreScopeProviderInterface::class);
        $this->assertInstanceOf(StoreScopeProvider::class, $storeScopeProvider);
    }

    public function testSetCurrentStoreByCode_SetsStore_ForValidStoreCode(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $storeScopeProvider = $this->instantiateStoreScopeProvider();
        $storeScopeProvider->setCurrentStoreByCode($store->getCode());
        $currentStore = $storeScopeProvider->getCurrentStore();

        $this->assertSame($store->getCode(), $currentStore->getCode());
    }

    public function testSetCurrentStoreByCode_throwsException_ForInvalidStoreCode(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $storeScopeProvider = $this->instantiateStoreScopeProvider();
        $storeScopeProvider->setCurrentStoreByCode('defawieufbwiueult');
    }

    public function testSetCurrentStoreById_SetsStore_ForValidStoreId(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $storeScopeProvider = $this->instantiateStoreScopeProvider();
        $storeScopeProvider->setCurrentStoreById($store->getId());
        $currentStore = $storeScopeProvider->getCurrentStore();

        $this->assertSame($store->getCode(), $currentStore->getCode());
    }

    public function testSetCurrentStoreByCode_throwsException_ForInvalidStoreId(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $storeScopeProvider = $this->instantiateStoreScopeProvider();
        $storeScopeProvider->setCurrentStoreById(90924857209);
    }

    /**
     * @magentoAppArea frontend
     */
    public function testGetCurrentStore_ReturnsStore_ForFrontend(): void
    {
        $this->setArea(Area::AREA_FRONTEND);

        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $storeRepository = $this->objectManager->create(StoreRepositoryInterface::class);
        $store = $storeRepository->getById($store->getId());

        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $storeScopeProvider = $this->instantiateStoreScopeProvider();
        $currentStore = $storeScopeProvider->getCurrentStore();

        $this->assertSame($store->getId(), $currentStore->getId());
    }

    /**
     * @magentoAppArea frontend
     */
    public function testSetCurrentStore_OverridesStoreManager(): void
    {
        $this->setArea(Area::AREA_FRONTEND);

        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore('default');

        $storeScopeProvider = $this->instantiateStoreScopeProvider();
        $storeScopeProvider->setCurrentStoreById($store->getId());
        $currentStore = $storeScopeProvider->getCurrentStore();

        $this->assertNotNull($store->getId());
        $this->assertSame((int)$store->getId(), (int)$currentStore->getId());
    }

    /**
     * @magentoAppArea crontab
     */
    public function testGetCurrentStore_ReturnsAdminStore_WhenAppAreaIsCronTab(): void
    {
        $this->setArea(Area::AREA_CRONTAB);

        $storeScopeProvider = $this->instantiateStoreScopeProvider();
        $currentStore = $storeScopeProvider->getCurrentStore();

        $this->assertNull($currentStore);
    }

    /**
     * @magentoAppArea global
     */
    public function testGetCurrentStore_ReturnsAdminStore_WhenAppAreaIsGlobal(): void
    {
        $storeScopeProvider = $this->instantiateStoreScopeProvider();
        $currentStore = $storeScopeProvider->getCurrentStore();

        $this->assertNull($currentStore);
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testGetCurrentStore_ReturnsNull_WhenAppAreaIsAdmin_StoreParamMissing(): void
    {
        $this->setArea(Area::AREA_ADMINHTML);

        $storeScopeProvider = $this->instantiateStoreScopeProvider();
        $currentStore = $storeScopeProvider->getCurrentStore();

        $this->assertNull($currentStore);
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testGetCurrentStore_ReturnsStoreInParams_WhenAppAreaIsAdmin(): void
    {
        $this->setArea(Area::AREA_ADMINHTML);

        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $mockRequest = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->expects($this->once())
            ->method('getParams')
            ->willReturn(['store' => $store->getId()]);

        $storeScopeProvider = $this->instantiateStoreScopeProvider([
            'request' => $mockRequest,
        ]);
        $currentStore = $storeScopeProvider->getCurrentStore();

        $this->assertNotNull((int)$store->getId());
        $this->assertSame((int)$store->getId(), (int)$currentStore->getId());
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testGetCurrentStore_ReturnsStoreInParams_WhenAppAreaIsAdmin_SingleStoreMode(): void
    {
        $this->setArea(Area::AREA_ADMINHTML);

        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 1,
        );

        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $defaultStore = $storeManager->getDefaultStoreView();

        $mockRequest = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->expects($this->never())
            ->method('getParams');

        $storeScopeProvider = $this->instantiateStoreScopeProvider([
            'request' => $mockRequest,
        ]);
        $currentStore = $storeScopeProvider->getCurrentStore();

        $this->assertSame((int)$defaultStore->getId(), (int)$currentStore->getId());
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testGetCurrentStore_ReturnsNull_WhenAppAreaIsAdmin_InvalidStoreParam(): void
    {
        $this->setArea(Area::AREA_ADMINHTML);

        $mockRequest = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->expects($this->once())
            ->method('getParams')
            ->willReturn(['store' => 948594854]);

        $storeScopeProvider = $this->instantiateStoreScopeProvider([
            'request' => $mockRequest,
        ]);
        $currentStore = $storeScopeProvider->getCurrentStore();

        $this->assertNull($currentStore);
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testUnsetCurrentStore_SetsNull(): void
    {
        $this->setArea(Area::AREA_ADMINHTML);

        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $storeScopeProvider = $this->instantiateStoreScopeProvider();
        $storeScopeProvider->setCurrentStoreById($store->getId());
        $storeScopeProvider->unsetCurrentStore();
        $currentStore = $storeScopeProvider->getCurrentStore();

        $this->assertNull($currentStore);
    }

    /**
     * @param mixed[]|null $params
     *
     * @return StoreScopeProviderInterface
     */
    private function instantiateStoreScopeProvider(?array $params = []): StoreScopeProviderInterface
    {
        return $this->objectManager->create(StoreScopeProvider::class, $params);
    }
}
