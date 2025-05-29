<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Action;

use Klevu\Configuration\Service\Action\RemoveEndpoints;
use Klevu\Configuration\Service\Action\RemoveEndpointsInterface;
use Klevu\Configuration\Service\Action\UpdateEndpoints;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\Writer;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\Action\RemoveEndpoints
 * @runTestsInSeparateProcesses
 */
class RemoveEndpointsTest extends TestCase
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
        $this->objectManager = ObjectManager::getInstance();
        $this->storeFixturesPool = $this->objectManager->create(StoreFixturesPool::class);
        $this->websiteFixturesPool = $this->objectManager->create(WebsiteFixturesPool::class);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->storeFixturesPool->rollback();
        $this->websiteFixturesPool->rollback();
    }

    public function testImplements_RemoveEndpointsActionInterface(): void
    {
        $this->assertInstanceOf(
            expected: RemoveEndpointsInterface::class,
            actual: $this->instantiateRemoveEndpointsAction(),
        );
    }

    public function testPreference_RemoveEndpointsActionInterface(): void
    {
        $this->assertInstanceOf(
            expected: RemoveEndpoints::class,
            actual: $this->objectManager->get(type: RemoveEndpointsInterface::class),
        );
    }

    /**
     * @magentoDbIsolation disabled
     */
    public function testExecute_removesEndpoints_StoreScope(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store')->get();

        $scopeWriter = $this->objectManager->get(Writer::class);
        $endpoints = [
            UpdateEndpoints::CONFIG_XML_PATH_URL_ANALYTICS => 'analytics.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_CAT_NAV => 'smart-category-merchandising.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_INDEXING => 'indexing.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_JS => 'js.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_SEARCH => 'search.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_TIERS => 'tiers.url',
        ];
        foreach ($endpoints as $path => $value) {
            $scopeWriter->save(
                $path,
                $value,
                ScopeInterface::SCOPE_STORES,
                (int)$store->getId(),
            );
        }

        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();
        $analyticsUrl = $scopeConfig->getValue(
            UpdateEndpoints::CONFIG_XML_PATH_URL_ANALYTICS,
            ScopeInterface::SCOPE_STORES,
            (int)$store->getId(),
        );
        $this->assertSame(expected: 'analytics.url', actual: $analyticsUrl, message: 'URL is set before Removal');

        $action = $this->instantiateRemoveEndpointsAction();
        $action->execute(
            scope: (int)$store->getId(),
            scopeType: ScopeInterface::SCOPE_STORES,
        );

        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();
        $analyticsEndpoint = $scopeConfig->getValue(
            UpdateEndpoints::CONFIG_XML_PATH_URL_ANALYTICS,
            ScopeInterface::SCOPE_STORES,
            (int)$store->getId(),
        );
        $this->assertNull(actual: $analyticsEndpoint, message: 'URL is removed');
    }

    /**
     * @magentoDbIsolation disabled
     */
    public function testExecute_removesEndpoints_WebsiteScope(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store')->get();

        $scopeWriter = $this->objectManager->get(Writer::class);
        $endpoints = [
            UpdateEndpoints::CONFIG_XML_PATH_URL_ANALYTICS => 'analytics.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_CAT_NAV => 'smart-category-merchandising.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_INDEXING => 'indexing.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_JS => 'js.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_SEARCH => 'search.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_TIERS => 'tiers.url',
        ];
        foreach ($endpoints as $path => $value) {
            $scopeWriter->save(
                $path,
                $value,
                ScopeInterface::SCOPE_WEBSITES,
                (int)$store->getWebsiteId(),
            );
        }

        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();
        $analyticsUrl = $scopeConfig->getValue(
            UpdateEndpoints::CONFIG_XML_PATH_URL_ANALYTICS,
            ScopeInterface::SCOPE_WEBSITES,
            (int)$store->getWebsiteId(),
        );
        $this->assertSame(expected: 'analytics.url', actual: $analyticsUrl, message: 'URL is set before Removal');

        $action = $this->instantiateRemoveEndpointsAction();
        $action->execute(
            scope: (int)$store->getWebsiteId(),
            scopeType: ScopeInterface::SCOPE_WEBSITES,
        );

        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();
        $analyticsEndpoint = $scopeConfig->getValue(
            UpdateEndpoints::CONFIG_XML_PATH_URL_ANALYTICS,
            ScopeInterface::SCOPE_WEBSITES,
            (int)$store->getWebsiteId(),
        );
        $this->assertNull(actual: $analyticsEndpoint, message: 'URL is removed');
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return RemoveEndpoints
     */
    private function instantiateRemoveEndpointsAction(?array $arguments = []): RemoveEndpoints
    {
        return $this->objectManager->create(
            type: RemoveEndpoints::class,
            arguments: $arguments,
        );
    }
}
