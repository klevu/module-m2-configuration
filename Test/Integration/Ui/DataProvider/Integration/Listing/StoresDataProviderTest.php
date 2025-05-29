<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Ui\DataProvider\Integration\Listing;

use Klevu\Configuration\Service\Provider\Stores\Config\AuthKeysCollectionProviderInterface;
use Klevu\Configuration\Ui\DataProvider\Integration\Listing\StoresDataProvider;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigCollection;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Ui\DataProvider\Integration\Listing\StoresDataProvider
 * @runTestsInSeparateProcesses
 */
class StoresDataProviderTest extends TestCase
{
    use StoreTrait;
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
        $this->storeFixturesPool->rollback();
        $this->websiteFixturesPool->rollback();
    }

    public function testImplements_DataProviderInterface(): void
    {
        $this->assertInstanceOf(
            expected: DataProviderInterface::class,
            actual: $this->instantiateDataProvider(),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testGetData_ReturnsDataForAllStores(): void
    {
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->reinitStores();
        $existingStores = $storeManager->getStores();

        $this->createWebsite();
        $this->createWebsite(websiteData: [
            'code' => 'klevu_test_website_2',
            'key' => 'other_site',
        ]);
        $website1 = $this->websiteFixturesPool->get('test_website');
        $website2 = $this->websiteFixturesPool->get('other_site');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website1->getId(),
            'key' => 'test_store_2',
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_3',
            'website_id' => $website2->getId(),
            'key' => 'test_store_3',
        ]);
        $store1 = $this->storeFixturesPool->get('test_store');
        $store2 = $this->storeFixturesPool->get('test_store_2');
        $store3 = $this->storeFixturesPool->get('test_store_3');

        $mockStore1ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12345,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-123456789',
        ]);
        $mockStore1ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22345,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-1234567890',
        ]);

        $mockStore2ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12346,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-987654321',
        ]);
        $mockStore2ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22346,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-9876543210',
        ]);

        $mockStore3ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store3->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => null,
        ]);
        $mockStore3ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22347,
            'scope_id' => $store3->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => null,
        ]);

        $mockSelect = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollection = $this->getMockBuilder(ConfigCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollection->expects($this->once())
            ->method('getSelect')
            ->wilLReturn($mockSelect);
        $mockCollection->expects($this->once())
            ->method('addPathFilter')
            ->with(StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS);
        $mockCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([
                $mockStore1ConfigApiKey,
                $mockStore2ConfigApiKey,
                $mockStore3ConfigApiKey,
                $mockStore1ConfigAuthKey,
                $mockStore2ConfigAuthKey,
                $mockStore3ConfigAuthKey,
            ]);

        $mockCollectionFactory = $this->getMockBuilder(ConfigCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockCollection);
        $authKeyCollection = $this->objectManager->create(AuthKeysCollectionProviderInterface::class, [
            'configCollectionFactory' => $mockCollectionFactory,
        ]);

        $dataProvider = $this->instantiateDataProvider([
            'authKeysCollectionProvider' => $authKeyCollection,
        ]);
        $storeList = $dataProvider->getData();

        $this->assertCount(expectedCount: 2, haystack: $storeList, message: 'Store List Result');
        $this->assertArrayHasKey(key: 'totalRecords', array: $storeList, message: 'Array Has Key "totalRecords"');
        $this->assertArrayHasKey(key: 'items', array: $storeList, message: 'Array Has Key "items"');

        $expectedStoresCount = 3 + count($existingStores);
        $this->assertSame(
            expected: $expectedStoresCount,
            actual: $storeList['totalRecords'],
            message: 'totalRecords has expected number',
        );
        $this->assertCount(
            expectedCount: $expectedStoresCount,
            haystack: $storeList['items'],
            message: 'items has expected number',
        );
        $items = $storeList['items'];

        // STORE 1
        $filteredResultStore1 = array_filter($items, static function (array $item) use ($store1) {
            return (int)$store1->getId() === (int)$item['store_id'];
        });
        $this->assertCount(expectedCount: 1, haystack: $filteredResultStore1);
        $keys = array_keys($filteredResultStore1);
        $resultStore1 = $filteredResultStore1[$keys[0]];

        $this->assertArrayHasKey(key: 'website_id', array: $resultStore1);
        $this->assertSame(expected: $website1->getId(), actual: $resultStore1['website_id']);
        $this->assertArrayHasKey(key: 'website', array: $resultStore1);
        $this->assertSame(
            expected: $website1->getId() . ': ' . $website1->getname() . ' (' . $website1->getCode() . ') ',
            actual: $resultStore1['website'],
        );
        $this->assertArrayHasKey(key: 'store', array: $resultStore1);
        $this->assertSame(
            expected: $store1->getId() . ': ' . $store1->getName() . ' (' . $store1->getCode() . ')',
            actual: $resultStore1['store'],
        );
        $this->assertArrayHasKey(key: 'integration_message', array: $resultStore1);
        $this->assertInstanceOf(expected: Phrase::class, actual: $resultStore1['integration_message']);
        $this->assertSame(
            expected: 'Integrated at Store Scope (klevu-123456789)',
            actual: $resultStore1['integration_message']->render(),
        );
        $this->assertArrayHasKey(key: 'store_integrated', array: $resultStore1);
        $this->assertTrue(condition: $resultStore1['store_integrated'], message: 'Store Integrated');

        $this->assertArrayHasKey(key: 'website_integrated', array: $resultStore1);
        $this->assertFalse(condition: $resultStore1['website_integrated'], message: 'Website Integrated');

        // STORE 2
        $filteredResultStore2 = array_filter($items, static function (array $item) use ($store2) {
            return (int)$store2->getId() === (int)$item['store_id'];
        });
        $this->assertCount(expectedCount: 1, haystack: $filteredResultStore2);
        $keys = array_keys($filteredResultStore2);
        $resultStore2 = $filteredResultStore2[$keys[0]];

        $this->assertArrayHasKey(key: 'website_id', array: $resultStore2);
        $this->assertSame(expected: $website1->getId(), actual: $resultStore2['website_id']);
        $this->assertArrayHasKey(key: 'website', array: $resultStore2);
        $this->assertSame(
            expected: $website1->getId() . ': ' . $website1->getname() . ' (' . $website1->getCode() . ') ',
            actual: $resultStore2['website'],
        );
        $this->assertArrayHasKey(key: 'store', array: $resultStore2);
        $this->assertSame(
            expected: $store2->getId() . ': ' . $store2->getName() . ' (' . $store2->getCode() . ')',
            actual: $resultStore2['store'],
        );
        $this->assertArrayHasKey(key: 'integration_message', array: $resultStore2);
        $this->assertInstanceOf(expected: Phrase::class, actual: $resultStore2['integration_message']);
        $this->assertSame(
            expected: 'Integrated at Store Scope (klevu-987654321)',
            actual: $resultStore2['integration_message']->render(),
        );

        $this->assertArrayHasKey(key: 'store_integrated', array: $resultStore2);
        $this->assertTrue(condition: $resultStore2['store_integrated'], message: 'Store Integrated');

        $this->assertArrayHasKey(key: 'website_integrated', array: $resultStore2);
        $this->assertFalse(condition: $resultStore2['website_integrated'], message: 'Website Integrated');

        // STORE 3
        $filteredResultStore3 = array_filter($items, static function (array $item) use ($store3) {
            return (int)$store3->getId() === (int)$item['store_id'];
        });
        $this->assertCount(expectedCount: 1, haystack: $filteredResultStore3);
        $keys = array_keys($filteredResultStore3);
        $resultStore3 = $filteredResultStore3[$keys[0]];

        $this->assertArrayHasKey(key: 'website_id', array: $resultStore3);
        $this->assertSame(expected: $website2->getId(), actual: $resultStore3['website_id']);
        $this->assertArrayHasKey(key: 'website', array: $resultStore3);
        $this->assertSame(
            expected: $website2->getId() . ': ' . $website2->getname() . ' (' . $website2->getCode() . ') ',
            actual: $resultStore3['website'],
        );
        $this->assertArrayHasKey(key: 'store', array: $resultStore3);
        $this->assertSame(
            expected: $store3->getId() . ': ' . $store3->getName() . ' (' . $store3->getCode() . ')',
            actual: $resultStore3['store'],
        );
        $this->assertArrayHasKey(key: 'integration_message', array: $resultStore3);
        $this->assertInstanceOf(expected: Phrase::class, actual: $resultStore3['integration_message']);
        $this->assertSame(
            expected: 'Not Integrated',
            actual: $resultStore3['integration_message']->render(),
        );

        $this->assertArrayHasKey(key: 'store_integrated', array: $resultStore3);
        $this->assertFalse(condition: $resultStore3['store_integrated'], message: 'Store Integrated');

        $this->assertArrayHasKey(key: 'website_integrated', array: $resultStore3);
        $this->assertFalse(condition: $resultStore3['website_integrated'], message: 'Website Integrated');
    }

    public function testGetData_ReturnsDataCurrentWebsite(): void
    {
        $this->createWebsite();
        $this->createWebsite(websiteData: [
            'code' => 'klevu_test_website_2',
            'key' => 'other_site',
        ]);
        $website1 = $this->websiteFixturesPool->get('test_website');
        $website2 = $this->websiteFixturesPool->get('other_site');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website1->getId(),
            'key' => 'test_store_2',
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_3',
            'website_id' => $website2->getId(),
            'key' => 'test_store_3',
        ]);
        $store1 = $this->storeFixturesPool->get('test_store');
        $store2 = $this->storeFixturesPool->get('test_store_2');
        $store3 = $this->storeFixturesPool->get('test_store_3');

        $mockStore1ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12345,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-123456789',
        ]);
        $mockStore1ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22345,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-1234567890',
        ]);

        $mockStore2ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12346,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-987654321',
        ]);
        $mockStore2ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22346,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-9876543210',
        ]);

        $mockStore3ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store3->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => null,
        ]);
        $mockStore3ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22347,
            'scope_id' => $store3->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => null,
        ]);

        $mockSelect = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollection = $this->getMockBuilder(ConfigCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollection->expects($this->once())
            ->method('getSelect')
            ->wilLReturn($mockSelect);
        $mockCollection->expects($this->once())
            ->method('addPathFilter')
            ->with(StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS);
        $mockCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([
                $mockStore1ConfigApiKey,
                $mockStore2ConfigApiKey,
                $mockStore3ConfigApiKey,
                $mockStore1ConfigAuthKey,
                $mockStore2ConfigAuthKey,
                $mockStore3ConfigAuthKey,
            ]);

        $mockCollectionFactory = $this->getMockBuilder(ConfigCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockCollection);
        $authKeyCollection = $this->objectManager->create(AuthKeysCollectionProviderInterface::class, [
            'configCollectionFactory' => $mockCollectionFactory,
        ]);

        $mockRequest = $this->getMockBuilder(RequestInterface::class)->getMock();
        $mockRequest->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'website' => (string)$website2->getId(),
            ]);

        $dataProvider = $this->instantiateDataProvider([
            'authKeysCollectionProvider' => $authKeyCollection,
            'request' => $mockRequest,
        ]);
        $storeList = $dataProvider->getData();

        $this->assertCount(expectedCount: 2, haystack: $storeList, message: 'Store List Result');
        $this->assertArrayHasKey(key: 'totalRecords', array: $storeList, message: 'Array Has Key "totalRecords"');
        $this->assertArrayHasKey(key: 'items', array: $storeList, message: 'Array Has Key "items"');

        $this->assertSame(expected: 1, actual: $storeList['totalRecords'], message: 'totalRecords has expected number');
        $this->assertCount(expectedCount: 1, haystack: $storeList['items'], message: 'items has expected number');
        $items = $storeList['items'];

        // STORE 3
        $filteredResultStore3 = array_filter($items, static function (array $item) use ($store3) {
            return (int)$store3->getId() === (int)$item['store_id'];
        });
        $this->assertCount(expectedCount: 1, haystack: $filteredResultStore3);
        $keys = array_keys($filteredResultStore3);
        $resultStore3 = $filteredResultStore3[$keys[0]];

        $this->assertArrayHasKey(key: 'website_id', array: $resultStore3);
        $this->assertSame(expected: $website2->getId(), actual: $resultStore3['website_id']);
        $this->assertArrayHasKey(key: 'website', array: $resultStore3);
        $this->assertSame(
            expected: $website2->getId() . ': ' . $website2->getname() . ' (' . $website2->getCode() . ') ',
            actual: $resultStore3['website'],
        );
        $this->assertArrayHasKey(key: 'store', array: $resultStore3);
        $this->assertSame(
            expected: $store3->getId() . ': ' . $store3->getName() . ' (' . $store3->getCode() . ')',
            actual: $resultStore3['store'],
        );
        $this->assertArrayHasKey(key: 'integration_message', array: $resultStore3);
        $this->assertInstanceOf(expected: Phrase::class, actual: $resultStore3['integration_message']);
        $this->assertSame(
            expected: 'Not Integrated',
            actual: $resultStore3['integration_message']->render(),
        );

        $this->assertArrayHasKey(key: 'store_integrated', array: $resultStore3);
        $this->assertFalse(condition: $resultStore3['store_integrated'], message: 'Store Integrated');

        $this->assertArrayHasKey(key: 'website_integrated', array: $resultStore3);
        $this->assertFalse(condition: $resultStore3['website_integrated'], message: 'Website Integrated');
    }

    public function testGetData_ReturnsDataCurrentStore(): void
    {
        $this->createWebsite();
        $this->createWebsite(websiteData: [
            'code' => 'klevu_test_website_2',
            'key' => 'other_site',
        ]);
        $website1 = $this->websiteFixturesPool->get('test_website');
        $website2 = $this->websiteFixturesPool->get('other_site');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website1->getId(),
            'key' => 'test_store_2',
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_3',
            'website_id' => $website2->getId(),
            'key' => 'test_store_3',
        ]);
        $store1 = $this->storeFixturesPool->get('test_store');
        $store2 = $this->storeFixturesPool->get('test_store_2');
        $store3 = $this->storeFixturesPool->get('test_store_3');

        $mockStore1ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12345,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-123456789',
        ]);
        $mockStore1ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22345,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-1234567890',
        ]);

        $mockStore2ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12346,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-987654321',
        ]);
        $mockStore2ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22346,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-9876543210',
        ]);

        $mockStore3ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store3->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => null,
        ]);
        $mockStore3ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22347,
            'scope_id' => $store3->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => null,
        ]);

        $mockSelect = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollection = $this->getMockBuilder(ConfigCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollection->expects($this->once())
            ->method('getSelect')
            ->wilLReturn($mockSelect);
        $mockCollection->expects($this->once())
            ->method('addPathFilter')
            ->with(StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS);
        $mockCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([
                $mockStore1ConfigApiKey,
                $mockStore2ConfigApiKey,
                $mockStore3ConfigApiKey,
                $mockStore1ConfigAuthKey,
                $mockStore2ConfigAuthKey,
                $mockStore3ConfigAuthKey,
            ]);

        $mockCollectionFactory = $this->getMockBuilder(ConfigCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockCollection);
        $authKeyCollection = $this->objectManager->create(AuthKeysCollectionProviderInterface::class, [
            'configCollectionFactory' => $mockCollectionFactory,
        ]);

        $mockRequest = $this->getMockBuilder(RequestInterface::class)->getMock();
        $mockRequest->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'store' => (string)$store1->getId(),
            ]);

        $dataProvider = $this->instantiateDataProvider([
            'authKeysCollectionProvider' => $authKeyCollection,
            'request' => $mockRequest,
        ]);
        $storeList = $dataProvider->getData();

        $this->assertCount(expectedCount: 2, haystack: $storeList, message: 'Store List Result');
        $this->assertArrayHasKey(key: 'totalRecords', array: $storeList, message: 'Array Has Key "totalRecords"');
        $this->assertArrayHasKey(key: 'items', array: $storeList, message: 'Array Has Key "items"');

        $this->assertSame(expected: 1, actual: $storeList['totalRecords'], message: 'totalRecords has expected number');
        $this->assertCount(expectedCount: 1, haystack: $storeList['items'], message: 'items has expected number');
        $items = $storeList['items'];

        // STORE 1
        $filteredResultStore1 = array_filter($items, static function (array $item) use ($store1) {
            return (int)$store1->getId() === (int)$item['store_id'];
        });
        $this->assertCount(expectedCount: 1, haystack: $filteredResultStore1);
        $keys = array_keys($filteredResultStore1);
        $resultStore1 = $filteredResultStore1[$keys[0]];

        $this->assertArrayHasKey(key: 'website_id', array: $resultStore1);
        $this->assertSame(expected: $website1->getId(), actual: $resultStore1['website_id']);
        $this->assertArrayHasKey(key: 'website', array: $resultStore1);
        $this->assertSame(
            expected: $website1->getId() . ': ' . $website1->getname() . ' (' . $website1->getCode() . ') ',
            actual: $resultStore1['website'],
        );
        $this->assertArrayHasKey(key: 'store', array: $resultStore1);
        $this->assertSame(
            expected: $store1->getId() . ': ' . $store1->getName() . ' (' . $store1->getCode() . ')',
            actual: $resultStore1['store'],
        );
        $this->assertArrayHasKey(key: 'integration_message', array: $resultStore1);
        $this->assertInstanceOf(expected: Phrase::class, actual: $resultStore1['integration_message']);
        $this->assertSame(
            expected: 'Integrated at Store Scope (klevu-123456789)',
            actual: $resultStore1['integration_message']->render(),
        );

        $this->assertArrayHasKey(key: 'store_integrated', array: $resultStore1);
        $this->assertTrue(condition: $resultStore1['store_integrated'], message: 'Store Integrated');

        $this->assertArrayHasKey(key: 'website_integrated', array: $resultStore1);
        $this->assertFalse(condition: $resultStore1['website_integrated'], message: 'Website Integrated');
    }

    public function testGetData_ReturnsDataCurrentStore_KeySetAtWebsiteLevel(): void
    {
        $this->createWebsite();
        $this->createWebsite(websiteData: [
            'code' => 'klevu_test_website_2',
            'key' => 'other_site',
        ]);
        $website1 = $this->websiteFixturesPool->get('test_website');
        $website2 = $this->websiteFixturesPool->get('other_site');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website1->getId(),
            'key' => 'test_store_2',
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_3',
            'website_id' => $website2->getId(),
            'key' => 'test_store_3',
        ]);
        $store1 = $this->storeFixturesPool->get('test_store');
        $store2 = $this->storeFixturesPool->get('test_store_2');
        $store3 = $this->storeFixturesPool->get('test_store_3');

        $mockStore1ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12345,
            'scope_id' => $store1->getWebsiteId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-123456789',
        ]);
        $mockStore1ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22345,
            'scope_id' => $store1->getWebsiteId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-1234567890',
        ]);

        $mockStore2ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12346,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-987654321',
        ]);
        $mockStore2ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22346,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-9876543210',
        ]);

        $mockStore3ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store3->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => null,
        ]);
        $mockStore3ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22347,
            'scope_id' => $store3->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => null,
        ]);

        $mockSelect = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollection = $this->getMockBuilder(ConfigCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollection->expects($this->once())
            ->method('getSelect')
            ->wilLReturn($mockSelect);
        $mockCollection->expects($this->once())
            ->method('addPathFilter')
            ->with(StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS);
        $mockCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([
                $mockStore1ConfigApiKey,
                $mockStore2ConfigApiKey,
                $mockStore3ConfigApiKey,
                $mockStore1ConfigAuthKey,
                $mockStore2ConfigAuthKey,
                $mockStore3ConfigAuthKey,
            ]);

        $mockCollectionFactory = $this->getMockBuilder(ConfigCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockCollection);
        $authKeyCollection = $this->objectManager->create(AuthKeysCollectionProviderInterface::class, [
            'configCollectionFactory' => $mockCollectionFactory,
        ]);

        $mockRequest = $this->getMockBuilder(RequestInterface::class)->getMock();
        $mockRequest->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'store' => (string)$store1->getId(),
            ]);

        $dataProvider = $this->instantiateDataProvider([
            'authKeysCollectionProvider' => $authKeyCollection,
            'request' => $mockRequest,
        ]);
        $storeList = $dataProvider->getData();

        $this->assertCount(expectedCount: 2, haystack: $storeList, message: 'Store List Result');
        $this->assertArrayHasKey(key: 'totalRecords', array: $storeList, message: 'Array Has Key "totalRecords"');
        $this->assertArrayHasKey(key: 'items', array: $storeList, message: 'Array Has Key "items"');

        $this->assertSame(expected: 1, actual: $storeList['totalRecords'], message: 'totalRecords has expected number');
        $this->assertCount(expectedCount: 1, haystack: $storeList['items'], message: 'items has expected number');
        $items = $storeList['items'];

        // STORE 1
        $filteredResultStore1 = array_filter($items, static function (array $item) use ($store1) {
            return (int)$store1->getId() === (int)$item['store_id'];
        });
        $this->assertCount(expectedCount: 1, haystack: $filteredResultStore1);
        $keys = array_keys($filteredResultStore1);
        $resultStore1 = $filteredResultStore1[$keys[0]];

        $this->assertArrayHasKey(key: 'website_id', array: $resultStore1);
        $this->assertSame(expected: $website1->getId(), actual: $resultStore1['website_id']);
        $this->assertArrayHasKey(key: 'website', array: $resultStore1);
        $this->assertSame(
            expected: $website1->getId() . ': ' . $website1->getname() . ' (' . $website1->getCode() . ') ',
            actual: $resultStore1['website'],
        );
        $this->assertArrayHasKey(key: 'store', array: $resultStore1);
        $this->assertSame(
            expected: $store1->getId() . ': ' . $store1->getName() . ' (' . $store1->getCode() . ')',
            actual: $resultStore1['store'],
        );
        $this->assertArrayHasKey(key: 'integration_message', array: $resultStore1);
        $this->assertInstanceOf(expected: Phrase::class, actual: $resultStore1['integration_message']);
        $this->assertSame(
            expected: 'Integrated at Website Scope (klevu-123456789)',
            actual: $resultStore1['integration_message']->render(),
        );

        $this->assertArrayHasKey(key: 'store_integrated', array: $resultStore1);
        $this->assertFalse(condition: $resultStore1['store_integrated'], message: 'Store Integrated');

        $this->assertArrayHasKey(key: 'website_integrated', array: $resultStore1);
        $this->assertTrue(condition: $resultStore1['website_integrated'], message: 'Website Integrated');
    }

    public function testGetData_ReturnsDataCurrentWebsite_KeySetAtWebsiteLevel(): void
    {
        $this->createWebsite();
        $this->createWebsite(websiteData: [
            'code' => 'klevu_test_website_2',
            'key' => 'other_site',
        ]);
        $website1 = $this->websiteFixturesPool->get('test_website');
        $website2 = $this->websiteFixturesPool->get('other_site');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website1->getId(),
            'key' => 'test_store_2',
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_3',
            'website_id' => $website2->getId(),
            'key' => 'test_store_3',
        ]);
        $store1 = $this->storeFixturesPool->get('test_store');
        $store2 = $this->storeFixturesPool->get('test_store_2');
        $store3 = $this->storeFixturesPool->get('test_store_3');

        $mockStore1ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12345,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-123456789',
        ]);
        $mockStore1ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22345,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-1234567890',
        ]);

        $mockStore2ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12346,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-987654321',
        ]);
        $mockStore2ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22346,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-9876543210',
        ]);

        $mockStore3ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store3->getWebsiteId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-5647382910',
        ]);
        $mockStore3ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22347,
            'scope_id' => $store3->getWebsiteId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-56473829101',
        ]);

        $mockSelect = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollection = $this->getMockBuilder(ConfigCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollection->expects($this->once())
            ->method('getSelect')
            ->wilLReturn($mockSelect);
        $mockCollection->expects($this->once())
            ->method('addPathFilter')
            ->with(StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS);
        $mockCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([
                $mockStore1ConfigApiKey,
                $mockStore2ConfigApiKey,
                $mockStore3ConfigApiKey,
                $mockStore1ConfigAuthKey,
                $mockStore2ConfigAuthKey,
                $mockStore3ConfigAuthKey,
            ]);

        $mockCollectionFactory = $this->getMockBuilder(ConfigCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockCollection);
        $authKeyCollection = $this->objectManager->create(AuthKeysCollectionProviderInterface::class, [
            'configCollectionFactory' => $mockCollectionFactory,
        ]);

        $mockRequest = $this->getMockBuilder(RequestInterface::class)->getMock();
        $mockRequest->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'website' => (string)$website2->getId(),
            ]);

        $dataProvider = $this->instantiateDataProvider([
            'authKeysCollectionProvider' => $authKeyCollection,
            'request' => $mockRequest,
        ]);
        $storeList = $dataProvider->getData();

        $this->assertCount(expectedCount: 2, haystack: $storeList, message: 'Store List Result');
        $this->assertArrayHasKey(key: 'totalRecords', array: $storeList, message: 'Array Has Key "totalRecords"');
        $this->assertArrayHasKey(key: 'items', array: $storeList, message: 'Array Has Key "items"');

        $this->assertSame(expected: 1, actual: $storeList['totalRecords'], message: 'totalRecords has expected number');
        $this->assertCount(expectedCount: 1, haystack: $storeList['items'], message: 'items has expected number');
        $items = $storeList['items'];

        // STORE 3
        $filteredResultStore3 = array_filter($items, static function (array $item) use ($store3) {
            return (int)$store3->getId() === (int)$item['store_id'];
        });
        $this->assertCount(expectedCount: 1, haystack: $filteredResultStore3);
        $keys = array_keys($filteredResultStore3);
        $resultStore3 = $filteredResultStore3[$keys[0]];

        $this->assertArrayHasKey(key: 'website_id', array: $resultStore3);
        $this->assertSame(expected: $website2->getId(), actual: $resultStore3['website_id']);
        $this->assertArrayHasKey(key: 'website', array: $resultStore3);
        $this->assertSame(
            expected: $website2->getId() . ': ' . $website2->getname() . ' (' . $website2->getCode() . ') ',
            actual: $resultStore3['website'],
        );
        $this->assertArrayHasKey(key: 'store', array: $resultStore3);
        $this->assertSame(
            expected: $store3->getId() . ': ' . $store3->getName() . ' (' . $store3->getCode() . ')',
            actual: $resultStore3['store'],
        );
        $this->assertArrayHasKey(key: 'integration_message', array: $resultStore3);
        $this->assertInstanceOf(expected: Phrase::class, actual: $resultStore3['integration_message']);
        $this->assertSame(
            expected: 'Integrated at Website Scope (klevu-5647382910)',
            actual: $resultStore3['integration_message']->render(),
        );

        $this->assertArrayHasKey(key: 'store_integrated', array: $resultStore3);
        $this->assertFalse(condition: $resultStore3['store_integrated'], message: 'Store Integrated');

        $this->assertArrayHasKey(key: 'website_integrated', array: $resultStore3);
        $this->assertTrue(condition: $resultStore3['website_integrated'], message: 'Website Integrated');
    }

    public function testGetData_ReturnsNotIntegrated_WhenOneKeyIsMissing(): void
    {
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->reinitStores();
        $existingStores = $storeManager->getStores();

        $this->createWebsite();
        $this->createWebsite(websiteData: [
            'code' => 'klevu_test_website_2',
            'key' => 'other_site',
        ]);
        $website1 = $this->websiteFixturesPool->get('test_website');
        $website2 = $this->websiteFixturesPool->get('other_site');

        $this->createStore(storeData: [
            'website_id' => $website1->getId(),
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_2',
            'website_id' => $website1->getId(),
            'key' => 'test_store_2',
        ]);
        $this->createStore(storeData: [
            'code' => 'klevu_test_store_3',
            'website_id' => $website2->getId(),
            'key' => 'test_store_3',
        ]);
        $store1 = $this->storeFixturesPool->get('test_store');
        $store2 = $this->storeFixturesPool->get('test_store_2');
        $store3 = $this->storeFixturesPool->get('test_store_3');

        $mockStore1ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12345,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => null,
        ]);
        $mockStore1ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22345,
            'scope_id' => $store1->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-1234567890',
        ]);

        $mockStore2ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12346,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => null,
        ]);
        $mockStore2ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22346,
            'scope_id' => $store2->getId(),
            'scope' => ScopeInterface::SCOPE_STORES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => 'klevu-9876543210',
        ]);

        $mockStore3ConfigApiKey = $this->getMockConfigValue(data: [
            'config_id' => 12347,
            'scope_id' => $store3->getWebsiteId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/js_api_key',
            'value' => 'klevu-5647382910',
        ]);
        $mockStore3ConfigAuthKey = $this->getMockConfigValue(data: [
            'config_id' => 22347,
            'scope_id' => $store3->getWebsiteId(),
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'path' => StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS . '/rest_auth_key',
            'value' => null,
        ]);

        $mockSelect = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollection = $this->getMockBuilder(ConfigCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollection->expects($this->once())
            ->method('getSelect')
            ->wilLReturn($mockSelect);
        $mockCollection->expects($this->once())
            ->method('addPathFilter')
            ->with(StoresDataProvider::XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS);
        $mockCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([
                $mockStore1ConfigApiKey,
                $mockStore2ConfigApiKey,
                $mockStore3ConfigApiKey,
                $mockStore1ConfigAuthKey,
                $mockStore2ConfigAuthKey,
                $mockStore3ConfigAuthKey,
            ]);

        $mockCollectionFactory = $this->getMockBuilder(ConfigCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockCollection);
        $authKeyCollection = $this->objectManager->create(AuthKeysCollectionProviderInterface::class, [
            'configCollectionFactory' => $mockCollectionFactory,
        ]);

        $dataProvider = $this->instantiateDataProvider([
            'authKeysCollectionProvider' => $authKeyCollection,
        ]);
        $storeList = $dataProvider->getData();

        $this->assertCount(expectedCount: 2, haystack: $storeList, message: 'Store List Result');
        $this->assertArrayHasKey(key: 'totalRecords', array: $storeList, message: 'Array Has Key "totalRecords"');
        $this->assertArrayHasKey(key: 'items', array: $storeList, message: 'Array Has Key "items"');

        $expectedStoresCount = 3 + count($existingStores);
        $this->assertSame(
            expected: $expectedStoresCount,
            actual: $storeList['totalRecords'],
            message: 'totalRecords has expected number',
        );
        $this->assertCount(
            expectedCount: $expectedStoresCount,
            haystack: $storeList['items'],
            message: 'items has expected number',
        );
        $items = $storeList['items'];

        // STORE 1
        $filteredResultStore1 = array_filter($items, static function (array $item) use ($store1) {
            return (int)$store1->getId() === (int)$item['store_id'];
        });
        $this->assertCount(expectedCount: 1, haystack: $filteredResultStore1);
        $keys = array_keys($filteredResultStore1);
        $resultStore1 = $filteredResultStore1[$keys[0]];

        $this->assertArrayHasKey(key: 'website_id', array: $resultStore1);
        $this->assertSame(expected: $website1->getId(), actual: $resultStore1['website_id']);
        $this->assertArrayHasKey(key: 'website', array: $resultStore1);
        $this->assertSame(
            expected: $website1->getId() . ': ' . $website1->getname() . ' (' . $website1->getCode() . ') ',
            actual: $resultStore1['website'],
        );
        $this->assertArrayHasKey(key: 'store', array: $resultStore1);
        $this->assertSame(
            expected: $store1->getId() . ': ' . $store1->getName() . ' (' . $store1->getCode() . ')',
            actual: $resultStore1['store'],
        );
        $this->assertArrayHasKey(key: 'integration_message', array: $resultStore1);
        $this->assertInstanceOf(expected: Phrase::class, actual: $resultStore1['integration_message']);
        $this->assertSame(
            expected: 'Not Integrated',
            actual: $resultStore1['integration_message']->render(),
        );

        $this->assertArrayHasKey(key: 'store_integrated', array: $resultStore1);
        $this->assertFalse(condition: $resultStore1['store_integrated'], message: 'Store Integrated');

        $this->assertArrayHasKey(key: 'website_integrated', array: $resultStore1);
        $this->assertFalse(condition: $resultStore1['website_integrated'], message: 'Website Integrated');

        // STORE 2
        $filteredResultStore2 = array_filter($items, static function (array $item) use ($store2) {
            return (int)$store2->getId() === (int)$item['store_id'];
        });
        $this->assertCount(expectedCount: 1, haystack: $filteredResultStore2);
        $keys = array_keys($filteredResultStore2);
        $resultStore2 = $filteredResultStore2[$keys[0]];

        $this->assertArrayHasKey(key: 'website_id', array: $resultStore2);
        $this->assertSame(expected: $website1->getId(), actual: $resultStore2['website_id']);
        $this->assertArrayHasKey(key: 'website', array: $resultStore2);
        $this->assertSame(
            expected: $website1->getId() . ': ' . $website1->getname() . ' (' . $website1->getCode() . ') ',
            actual: $resultStore2['website'],
        );
        $this->assertArrayHasKey(key: 'store', array: $resultStore2);
        $this->assertSame(
            expected: $store2->getId() . ': ' . $store2->getName() . ' (' . $store2->getCode() . ')',
            actual: $resultStore2['store'],
        );
        $this->assertArrayHasKey(key: 'integration_message', array: $resultStore2);
        $this->assertInstanceOf(expected: Phrase::class, actual: $resultStore2['integration_message']);
        $this->assertSame(
            expected: 'Not Integrated',
            actual: $resultStore2['integration_message']->render(),
        );

        $this->assertArrayHasKey(key: 'store_integrated', array: $resultStore2);
        $this->assertFalse(condition: $resultStore2['store_integrated'], message: 'Store Integrated');

        $this->assertArrayHasKey(key: 'website_integrated', array: $resultStore2);
        $this->assertFalse(condition: $resultStore2['website_integrated'], message: 'Website Integrated');

        // STORE 3
        $filteredResultStore3 = array_filter($items, static function (array $item) use ($store3) {
            return (int)$store3->getId() === (int)$item['store_id'];
        });
        $this->assertCount(expectedCount: 1, haystack: $filteredResultStore3);
        $keys = array_keys($filteredResultStore3);
        $resultStore3 = $filteredResultStore3[$keys[0]];

        $this->assertArrayHasKey(key: 'website_id', array: $resultStore3);
        $this->assertSame(expected: $website2->getId(), actual: $resultStore3['website_id']);
        $this->assertArrayHasKey(key: 'website', array: $resultStore3);
        $this->assertSame(
            expected: $website2->getId() . ': ' . $website2->getname() . ' (' . $website2->getCode() . ') ',
            actual: $resultStore3['website'],
        );
        $this->assertArrayHasKey(key: 'store', array: $resultStore3);
        $this->assertSame(
            expected: $store3->getId() . ': ' . $store3->getName() . ' (' . $store3->getCode() . ')',
            actual: $resultStore3['store'],
        );
        $this->assertArrayHasKey(key: 'integration_message', array: $resultStore3);
        $this->assertInstanceOf(expected: Phrase::class, actual: $resultStore3['integration_message']);
        $this->assertSame(
            expected: 'Not Integrated',
            actual: $resultStore3['integration_message']->render(),
        );

        $this->assertArrayHasKey(key: 'store_integrated', array: $resultStore3);
        $this->assertFalse(condition: $resultStore3['store_integrated'], message: 'Store Integrated');

        $this->assertArrayHasKey(key: 'website_integrated', array: $resultStore3);
        $this->assertFalse(condition: $resultStore3['website_integrated'], message: 'Website Integrated');
    }

    /**
     * @param mixed[] $data
     *
     * @return Value|(Value&MockObject)|MockObject
     */
    private function getMockConfigValue(array $data): Value|MockObject
    {
        $mockConfigBuilder = $this->getMockBuilder(Value::class);
        /** @see vendor/magento/framework/App/Config/Value.php:11 */
        $mockConfigBuilder->addMethods(['getScopeId', 'getScope', 'getPath', 'getValue']);
        $mockConfigBuilder->onlyMethods(['getId']);
        $mockConfig = $mockConfigBuilder->disableOriginalConstructor()->getMock();

        $mockConfig->method('getId')
            ->willReturn($data['config_id'] ?? null);
        $mockConfig->method('getScopeId')
            ->willReturn($data['scope_id'] ?? null);
        $mockConfig->method('getScope')
            ->willReturn($data['scope'] ?? null);
        $mockConfig->method('getPath')
            ->willReturn($data['path'] ?? null);
        $mockConfig->method('getValue')
            ->willReturn($data['value'] ?? null);

        return $mockConfig;
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return StoresDataProvider
     */
    private function instantiateDataProvider(?array $arguments = []): StoresDataProvider
    {
        if (!isset($arguments['name'])) {
            $arguments['name'] = 'klevu_integration_store_listing_data_source';
        }
        if (!isset($arguments['primaryFieldName'])) {
            $arguments['primaryFieldName'] = 'config_id';
        }
        if (!isset($arguments['requestFieldName'])) {
            $arguments['requestFieldName'] = 'id';
        }

        return $this->objectManager->create(
            type: StoresDataProvider::class,
            arguments: $arguments,
        );
    }
}
