<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Ui\Component\Listing\Column;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Configuration\Ui\Component\Listing\Column\IntegrateKlevuAccount;
use Klevu\Configuration\Ui\DataProvider\Integration\Listing\StoresDataProvider;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers IntegrateKlevuAccount
 * @method IntegrateKlevuAccount instantiateTestObject(?array $arguments = null)
 * @method IntegrateKlevuAccount instantiateTestObjectFromInterface(?array $arguments = null)
 *
 */
class IntegrateKlevuAccountTest extends TestCase
{
    use ObjectInstantiationTrait;
    use SetAuthKeysTrait;
    use StoreTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = IntegrateKlevuAccount::class;
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
