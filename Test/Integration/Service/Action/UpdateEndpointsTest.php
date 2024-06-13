<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Action;

use Klevu\Configuration\Service\Action\UpdateEndpoints;
use Klevu\Configuration\Service\Action\UpdateEndpointsInterface;
use Klevu\PhpSDK\Model\Account;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\Configuration\Service\Action\UpdateEndpoints
 */
class UpdateEndpointsTest extends TestCase
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

    public function testImplements_SaveApiKeysActionInterface(): void
    {
        $this->assertInstanceOf(
            expected: UpdateEndpointsInterface::class,
            actual: $this->instantiateUpdateEndpointsAction(),
        );
    }

    public function testPreference_ForSaveApiKeysActionInterface(): void
    {
        $this->assertInstanceOf(
            expected: UpdateEndpoints::class,
            actual: $this->objectManager->get(type: UpdateEndpointsInterface::class),
        );
    }

    public function testExecute_ThrowsException_InvalidEndpoint(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store')->get();

        $mockAccount = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccount->expects($this->once())
            ->method('getIndexingUrl')
            ->willReturn('test@exa)(*£^$&%&*^mple.com');
        $mockAccount->expects($this->once())
            ->method('getSearchUrl')
            ->willReturn('search.url');
        $mockAccount->expects($this->once())
            ->method('getSmartCategoryMerchandisingUrl')
            ->willReturn('smart-category-merchandising.url');
        $mockAccount->expects($this->once())
            ->method('getAnalyticsUrl')
            ->willReturn('analytics.url');
        $mockAccount->expects($this->once())
            ->method('getJsUrl')
            ->willReturn('js.url');
        $mockAccount->expects($this->once())
            ->method('getTiersUrl')
            ->willReturn('tiers.url');

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $mockLogger->expects($this->once())->method('error');
        $mockLogger->expects($this->never())->method('notice');
        $mockLogger->expects($this->never())->method('info');
        $mockLogger->expects($this->never())->method('critical');

        $action = $this->instantiateUpdateEndpointsAction([
            'logger' => $mockLogger,
        ]);
        $action->execute($mockAccount, (int)$store->getId(), ScopeInterface::SCOPE_STORES);

        $scopeConfig = $this->objectManager->create(ScopeConfigInterface::class);
        $indexingEndpoint = $scopeConfig->getValue(
            UpdateEndpoints::CONFIG_XML_PATH_URL_INDEXING,
            (int)$store->getId(),
            ScopeInterface::SCOPE_STORES,
        );
        $this->assertNull(actual: $indexingEndpoint);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecute_SavesEndpoints(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store')->get();

        $mockAccount = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccount->expects($this->once())
            ->method('getIndexingUrl')
            ->willReturn('indexing.url');
        $mockAccount->expects($this->once())
            ->method('getSearchUrl')
            ->willReturn('search.url');
        $mockAccount->expects($this->once())
            ->method('getSmartCategoryMerchandisingUrl')
            ->willReturn('smart-category-merchandising.url');
        $mockAccount->expects($this->once())
            ->method('getAnalyticsUrl')
            ->willReturn('analytics.url');
        $mockAccount->expects($this->once())
            ->method('getJsUrl')
            ->willReturn('js.url');
        $mockAccount->expects($this->once())
            ->method('getTiersUrl')
            ->willReturn('tiers.url');

        $action = $this->instantiateUpdateEndpointsAction();
        $action->execute($mockAccount, (int)$store->getId(), ScopeInterface::SCOPE_STORES);

        $scopeConfig = $this->objectManager->create(ScopeConfigInterface::class);
        $indexingEndpoint = $scopeConfig->getValue(
            UpdateEndpoints::CONFIG_XML_PATH_URL_INDEXING,
            ScopeInterface::SCOPE_STORES,
            (int)$store->getId(),
        );
        $this->assertSame(expected: 'indexing.url', actual: $indexingEndpoint);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecute_SavesEndpoints_WhenSingleStoreModeEnabled(): void
    {
        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 1,
        );
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $store = $storeManager->getDefaultStoreView();

        $mockAccount = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccount->expects($this->once())
            ->method('getIndexingUrl')
            ->willReturn('indexing.url');
        $mockAccount->expects($this->once())
            ->method('getSearchUrl')
            ->willReturn('search.url');
        $mockAccount->expects($this->once())
            ->method('getSmartCategoryMerchandisingUrl')
            ->willReturn('smart-category-merchandising.url');
        $mockAccount->expects($this->once())
            ->method('getAnalyticsUrl')
            ->willReturn('analytics.url');
        $mockAccount->expects($this->once())
            ->method('getJsUrl')
            ->willReturn('js.url');
        $mockAccount->expects($this->once())
            ->method('getTiersUrl')
            ->willReturn('tiers.url');

        $action = $this->instantiateUpdateEndpointsAction();
        $action->execute($mockAccount, (int)$store->getId(), ScopeInterface::SCOPE_STORES);

        $scopeConfig = $this->objectManager->create(ScopeConfigInterface::class);
        $indexingEndpoint = $scopeConfig->getValue(
            UpdateEndpoints::CONFIG_XML_PATH_URL_INDEXING,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            null,
        );
        $this->assertSame(expected: 'indexing.url', actual: $indexingEndpoint);
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return UpdateEndpoints
     */
    private function instantiateUpdateEndpointsAction(?array $arguments = []): UpdateEndpoints
    {
        return $this->objectManager->create(
            type: UpdateEndpoints::class,
            arguments: $arguments,
        );
    }
}
