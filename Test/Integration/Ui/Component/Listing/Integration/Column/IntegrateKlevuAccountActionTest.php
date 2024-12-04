<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Ui\Component\Listing\Integration\Column;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Configuration\Ui\Component\Listing\Integration\Column\IntegrateKlevuAccountAction;
use Klevu\Configuration\Ui\DataProvider\Integration\Listing\StoresDataProvider;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers IntegrateKlevuAccountAction
 * @method IntegrateKlevuAccountAction instantiateTestObject(?array $arguments = null)
 * @method IntegrateKlevuAccountAction instantiateTestObjectFromInterface(?array $arguments = null)
 */
class IntegrateKlevuAccountActionTest extends TestCase
{
    use ObjectInstantiationTrait;
    use SetAuthKeysTrait;
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
        parent::setUp();

        $this->implementationFqcn = IntegrateKlevuAccountAction::class;
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
    public function testPrepareDataSource_ForNoIntegration_DoesNotIncludeWebsiteIntegrationLink(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');

        $result = $this->filterDataByStore(
            result: $this->getColumnData(),
            store: $storeFixture->get(),
        );

        $this->assertArrayHasKey(key: 'integration_message', array: $result);
        $this->assertInstanceOf(expected: Phrase::class, actual: $result['integration_message']);
        $this->assertSame(
            expected: 'Not Integrated',
            actual: $result['integration_message']->render(),
        );

        $this->assertArrayHasKey(key: 'klevu_integration_store_listing', array: $result);
        $links = $result['klevu_integration_store_listing'];

        $this->assertArrayHasKey(key: 'integrate_store', array: $links);
        $this->assertArrayHasKey(key: 'label', array: $links['integrate_store']);
        $this->assertInstanceOf(expected: Phrase::class, actual: $links['integrate_store']['label']);
        $this->assertSame(expected: 'Integrate Store', actual: $links['integrate_store']['label']->render());

        $this->assertArrayNotHasKey(key: 'remove_store', array: $links);
        $this->assertArrayNotHasKey(key: 'integrate_website', array: $links);//@TODO change when channels are available
        $this->assertArrayNotHasKey(key: 'remove_website', array: $links);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testPrepareDataSource_ForStoreIntegration_DoesNotIncludeWebsiteIntegrationLink(): void
    {
        $jsApiKey = 'klevu-js-key';
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: $jsApiKey,
            restAuthKey: 'klevu-rest-key',
        );

        $result = $this->filterDataByStore(
            result: $this->getColumnData(),
            store: $storeFixture->get(),
        );

        $this->assertArrayHasKey(key: 'integration_message', array: $result);
        $this->assertInstanceOf(expected: Phrase::class, actual: $result['integration_message']);
        $this->assertSame(
            expected: sprintf('Integrated at Store Scope (%s)', $jsApiKey),
            actual: $result['integration_message']->render(),
        );

        $this->assertArrayHasKey(key: 'klevu_integration_store_listing', array: $result);
        $links = $result['klevu_integration_store_listing'];

        $this->assertArrayHasKey(key: 'integrate_store', array: $links);
        $this->assertArrayHasKey(key: 'label', array: $links['integrate_store']);
        $this->assertInstanceOf(expected: Phrase::class, actual: $links['integrate_store']['label']);
        $this->assertSame(expected: 'Edit Store Keys', actual: $links['integrate_store']['label']->render());

        $this->assertArrayHasKey(key: 'remove_store', array: $links);
        $this->assertArrayHasKey(key: 'label', array: $links['remove_store']);
        $this->assertInstanceOf(expected: Phrase::class, actual: $links['remove_store']['label']);
        $this->assertSame(expected: 'Remove Store Keys', actual: $links['remove_store']['label']->render());

        $this->assertArrayNotHasKey(key: 'integrate_website', array: $links);//@TODO change when channels are available
        $this->assertArrayNotHasKey(key: 'remove_website', array: $links);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     */
    public function testPrepareDataSource_ForStoreIntegration_WhenSingleStoreModeEnabled(): void
    {
        $jsApiKey = 'klevu-js-key';

        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $store = $storeManager->getDefaultStoreView();

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store);

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: $jsApiKey,
            restAuthKey: 'klevu-rest-key',
            singleStoreMode: true,
        );

        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 1,
        );

        $result = $this->getColumnData();
        $this->assertArrayHasKey(key: 'data', array: $result);
        $this->assertArrayHasKey(key: 'items', array: $result['data']);
        $items = $result['data']['items'];
        $this->assertCount(expectedCount: 1, haystack: $items);
        $item = array_shift($items);

        $this->assertArrayHasKey(key: 'integration_message', array: $item);
        $this->assertInstanceOf(expected: Phrase::class, actual: $item['integration_message']);
        $this->assertSame(
            expected: sprintf('Integrated (%s)', $jsApiKey),
            actual: $item['integration_message']->render(),
        );

        $this->assertArrayHasKey(key: 'klevu_integration_store_listing', array: $item);
        $links = $item['klevu_integration_store_listing'];

        $this->assertArrayHasKey(key: 'integrate_store', array: $links);
        $this->assertArrayHasKey(key: 'label', array: $links['integrate_store']);
        $this->assertInstanceOf(expected: Phrase::class, actual: $links['integrate_store']['label']);
        $this->assertSame(expected: 'Edit Keys', actual: $links['integrate_store']['label']->render());

        $this->assertArrayHasKey(key: 'remove_store', array: $links);
        $this->assertArrayHasKey(key: 'label', array: $links['remove_store']);
        $this->assertInstanceOf(expected: Phrase::class, actual: $links['remove_store']['label']);
        $this->assertSame(expected: 'Remove Keys', actual: $links['remove_store']['label']->render());

        $this->assertArrayNotHasKey(key: 'integrate_website', array: $links);//@TODO change when channels are available
        $this->assertArrayNotHasKey(key: 'remove_website', array: $links);
    }

    public function testPrepareDataSource_ForWebsiteIntegration_DoesNotIncludeWebsiteIntegrationLink(): void
    {
        // @TODO add test when channels are available
    }

    /**
     * @return mixed[]
     */
    private function getColumnData(): array
    {
        $storesDataProvider = $this->objectManager->create(StoresDataProvider::class, [
            'name' => 'klevu_integration_store_listing',
            'primaryFieldName' => 'entity_id',
            'requestFieldName' => 'id',
        ]);
        $data = $storesDataProvider->getData();
        $dataSource = [
            'data' => [
                'items' => $data['items'] ?? [],
            ],
        ];

        $column = $this->instantiateTestObject();
        $column->setData(key: 'name', value: 'klevu_integration_store_listing');

        return $column->prepareDataSource($dataSource);
    }

    /**
     * @param mixed[] $result
     * @param StoreInterface $store
     *
     * @return mixed[]
     */
    private function filterDataByStore(array $result, StoreInterface $store): array
    {
        $this->assertArrayHasKey(key: 'data', array: $result);
        $this->assertArrayHasKey(key: 'items', array: $result['data']);
        $items = $result['data']['items'];
        $storeResultArray = array_filter(
            array: $items,
            callback: static fn (array $item): bool => ((int)$item['store_id'] === (int)$store->getId()),
        );

        return array_shift($storeResultArray);
    }
}
