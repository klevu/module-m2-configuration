<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Provider;

use Klevu\Configuration\Service\Provider\ScopeProvider;
use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Configuration\Service\Provider\StoreScopeProviderInterface;
use Klevu\Configuration\Service\Provider\WebsiteScopeProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ScopeProviderTest extends TestCase
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

    public function testImplements_ScopeProviderInterface(): void
    {
        $this->assertInstanceOf(
            expected: ScopeProviderInterface::class,
            actual: $this->instantiateScopeProvider(),
        );
    }

    public function testPreferenceFor_StoreScopeProviderInterface(): void
    {
        $storeScopeProvider = $this->objectManager->create(ScopeProviderInterface::class);
        $this->assertInstanceOf(ScopeProvider::class, $storeScopeProvider);
    }

    /**
     * @dataProvider invalidScopeType_DataProvider
     */
    public function testSetCurrentScopeById_ThrowsException_InvalidScopeType(string $invalidScopeType): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                "Invalid scopeType provided. Expected one of %s, Received %s",
                implode(', ', [ScopeInterface::SCOPE_WEBSITES, ScopeInterface::SCOPE_STORES]),
                $invalidScopeType,
            ),
        );

        $provider = $this->instantiateScopeProvider();
        $provider->setCurrentScopeById(1, $invalidScopeType);
    }

    /**
     * @dataProvider invalidScopeType_DataProvider
     */
    public function testSetCurrentScopeByCode_ThrowsException_InvalidScopeType(string $invalidScopeType): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                "Invalid scopeType provided. Expected one of %s, Received %s",
                implode(', ', [ScopeInterface::SCOPE_WEBSITES, ScopeInterface::SCOPE_STORES]),
                $invalidScopeType,
            ),
        );

        $provider = $this->instantiateScopeProvider();
        $provider->setCurrentScopeByCode('default', $invalidScopeType);
    }

    /**
     * @return string[][]
     */
    public function invalidScopeType_DataProvider(): array
    {
        return [
            ['global'],
            [ScopeConfigInterface::SCOPE_TYPE_DEFAULT],
            [ScopeInterface::SCOPE_GROUP],
            [ScopeInterface::SCOPE_GROUPS],
            ['1'],
        ];
    }

    /**
     * @magentoAppArea global
     */
    public function testGetCurrentScope_ReturnsDefault_AppAreaGlobal_WhenNotSet(): void
    {
        $provider = $this->instantiateScopeProvider();
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeConfigInterface::SCOPE_TYPE_DEFAULT, actual: $currentScope->getScopeType());
        $this->assertNull(actual: $currentScope->getScopeId());
        $this->assertNull(actual: $currentScope->getScopeObject());
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testGetCurrentScope_ReturnsDefault_AppAreaAdmin_WhenNotSet(): void
    {
        $provider = $this->instantiateScopeProvider();
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeConfigInterface::SCOPE_TYPE_DEFAULT, actual: $currentScope->getScopeType());
        $this->assertNull(actual: $currentScope->getScopeId());
        $this->assertNull(actual: $currentScope->getScopeObject());
    }

    /**
     * @magentoAppArea webapi_rest
     */
    public function testGetCurrentScope_ReturnsDefault_AppAreaRest_WhenNotSet(): void
    {
        $provider = $this->instantiateScopeProvider();
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeConfigInterface::SCOPE_TYPE_DEFAULT, actual: $currentScope->getScopeType());
        $this->assertNull(actual: $currentScope->getScopeId());
        $this->assertNull(actual: $currentScope->getScopeObject());
    }

    /**
     * @magentoAppArea global
     */
    public function testGetCurrentScope_ReturnsAdminStore_AppAreaGlobal_WhenWebsiteScopeSet(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');

        $provider = $this->instantiateScopeProvider();
        $provider->setCurrentScope($website->get());
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeInterface::SCOPE_WEBSITES, actual: $currentScope->getScopeType());
        $this->assertSame(expected: $website->getId(), actual: $currentScope->getScopeId());
        $this->assertSame(expected: $website->get(), actual: $currentScope->getScopeObject());
    }

    /**
     * @magentoAppArea crontab
     */
    public function testGetCurrentScope_ReturnsAdminStore_AppAreaCron_WhenWebsiteScopeSet(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');

        $provider = $this->instantiateScopeProvider();
        $provider->setCurrentScope($website->get());
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeInterface::SCOPE_WEBSITES, actual: $currentScope->getScopeType());
        $this->assertSame(expected: $website->getId(), actual: $currentScope->getScopeId());
        $this->assertSame(expected: $website->get(), actual: $currentScope->getScopeObject());
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testGetCurrentScope_ReturnsWebsite_AppAreaAdmin_WhenWebsiteScopeSet(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');

        $provider = $this->instantiateScopeProvider();
        $provider->setCurrentScope($website->get());
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeInterface::SCOPE_WEBSITES, actual: $currentScope->getScopeType());
        $this->assertSame(expected: $website->getId(), actual: $currentScope->getScopeId());
        $this->assertSame(expected: $website->get(), actual: $currentScope->getScopeObject());
    }

    /**
     * @magentoAppArea webapi_rest
     */
    public function testGetCurrentScope_ReturnsWebsite_AppAreaRest_WhenWebsiteScopeSet(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');

        $provider = $this->instantiateScopeProvider();
        $provider->setCurrentScope($website->get());
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeInterface::SCOPE_WEBSITES, actual: $currentScope->getScopeType());
        $this->assertSame(expected: $website->getId(), actual: $currentScope->getScopeId());
        $this->assertSame(expected: $website->get(), actual: $currentScope->getScopeObject());
    }

    /**
     * @magentoAppArea global
     */
    public function testGetCurrentScope_ReturnsAdminStore_AppAreaGlobal_WhenStoreScopeSet(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');

        $provider = $this->instantiateScopeProvider();
        $provider->setCurrentScope($storeFixture->get());
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeInterface::SCOPE_STORES, actual: $currentScope->getScopeType());
        $this->assertSame(expected: $storeFixture->getId(), actual: $currentScope->getScopeId());
        $this->assertSame(expected: $storeFixture->get(), actual: $currentScope->getScopeObject());
    }

    /**
     * @magentoAppArea crontab
     */
    public function testGetCurrentScope_ReturnsAdminStore_AppAreaCron_WhenStoreScopeSet(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');

        $provider = $this->instantiateScopeProvider();
        $provider->setCurrentScope($storeFixture->get());
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeInterface::SCOPE_STORES, actual: $currentScope->getScopeType());
        $this->assertSame(expected: $storeFixture->getId(), actual: $currentScope->getScopeId());
        $this->assertSame(expected: $storeFixture->get(), actual: $currentScope->getScopeObject());
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testGetCurrentScope_ReturnsWebsite_AppAreaAdmin_WhenStoreScopeSet(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');

        $provider = $this->instantiateScopeProvider();
        $provider->setCurrentScope($storeFixture->get());
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeInterface::SCOPE_STORES, actual: $currentScope->getScopeType());
        $this->assertSame(expected: $storeFixture->getId(), actual: $currentScope->getScopeId());
        $this->assertSame(expected: $storeFixture->get(), actual: $currentScope->getScopeObject());
    }

    /**
     * @magentoAppArea webapi_rest
     */
    public function testGetCurrentScope_ReturnsWebsite_AppAreaRest_WhenStoreScopeSet(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');

        $provider = $this->instantiateScopeProvider();
        $provider->setCurrentScope($storeFixture->get());
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeInterface::SCOPE_STORES, actual: $currentScope->getScopeType());
        $this->assertSame(expected: $storeFixture->getId(), actual: $currentScope->getScopeId());
        $this->assertSame(expected: $storeFixture->get(), actual: $currentScope->getScopeObject());
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testGetCurrentScope_ReturnsWebsite_AppAreaAdmin_WhenStoreScopeSet_ThenWebsiteScopeSet(): void
    {
        $this->createWebsite();
        $websiteFixture = $this->websiteFixturesPool->get('test_website');
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');

        $provider = $this->instantiateScopeProvider();

        $provider->setCurrentScope($storeFixture->get());
        $provider->setCurrentScope($websiteFixture->get());
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeInterface::SCOPE_WEBSITES, actual: $currentScope->getScopeType());
        $this->assertSame(expected: $websiteFixture->getId(), actual: $currentScope->getScopeId());
        $this->assertSame(expected: $websiteFixture->get(), actual: $currentScope->getScopeObject());

        $storeScopeProvider = $this->objectManager->get(StoreScopeProviderInterface::class);
        $this->assertNull($storeScopeProvider->getCurrentStore());
    }

    /**
     * @magentoAppArea webapi_rest
     */
    public function testGetCurrentScope_ReturnsStore_AppAreaRest_WhenWebsiteScopeSet_ThenStoreScopeSet(): void
    {
        $this->createWebsite();
        $websiteFixture = $this->websiteFixturesPool->get('test_website');
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');

        $provider = $this->instantiateScopeProvider();

        $provider->setCurrentScope($websiteFixture->get());
        $provider->setCurrentScope($storeFixture->get());
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeInterface::SCOPE_STORES, actual: $currentScope->getScopeType());
        $this->assertSame(expected: $storeFixture->getId(), actual: $currentScope->getScopeId());
        $this->assertSame(expected: $storeFixture->get(), actual: $currentScope->getScopeObject());

        $websiteScopeProvider = $this->objectManager->get(WebsiteScopeProviderInterface::class);
        $this->assertNull($websiteScopeProvider->getCurrentWebsite());
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testUnsetCurrentScope_ReturnsNull_AppAreaAdmin_ForWebsiteAndStoreScopeProviders(): void
    {
        $this->createWebsite();
        $websiteFixture = $this->websiteFixturesPool->get('test_website');
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');

        $provider = $this->instantiateScopeProvider();

        $provider->setCurrentScope($websiteFixture->get());
        $provider->setCurrentScope($storeFixture->get());
        $provider->unsetCurrentScope();
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeConfigInterface::SCOPE_TYPE_DEFAULT, actual: $currentScope->getScopeType());
        $this->assertNull(actual: $currentScope->getScopeId());
        $this->assertNull(actual: $currentScope->getScopeObject());

        $websiteScopeProvider = $this->objectManager->get(WebsiteScopeProviderInterface::class);
        $this->assertNull($websiteScopeProvider->getCurrentWebsite());

        $storeScopeProvider = $this->objectManager->get(StoreScopeProviderInterface::class);
        $this->assertNull($storeScopeProvider->getCurrentStore());

        $currentScope = $provider->getCurrentScope();
        $this->assertSame(
            expected: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            actual: $currentScope->getScopeType(),
        );
    }

    /**
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     * @magentoConfigFixture default_store general/single_store_mode/enabled 1
     */
    public function testGetCurrentScope_ReturnsDefault_InSingleStoreMode(): void
    {
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore('default');

        $provider = $this->instantiateScopeProvider();
        $currentScope = $provider->getCurrentScope();

        $this->assertSame(expected: ScopeConfigInterface::SCOPE_TYPE_DEFAULT, actual: $currentScope->getScopeType());
        $this->assertNull(actual: $currentScope->getScopeId());
    }

    /**
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     * @magentoConfigFixture default_store general/single_store_mode/enabled 1
     */
    public function testSetCurrentScopeByCode_ForStore_InSingleStoreMode(): void
    {
        $provider = $this->instantiateScopeProvider();
        $provider->setCurrentScopeByCode('default', ScopeInterface::SCOPE_STORES);

        $currentScope = $provider->getCurrentScope();
        $this->assertSame(expected: ScopeInterface::SCOPE_STORES, actual: $currentScope->getScopeType());
        $this->assertSame(expected: 1, actual: $currentScope->getScopeId());
    }

    /**
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     * @magentoConfigFixture default_store general/single_store_mode/enabled 1
     */
    public function testSetCurrentScopeByCode_ForWebsite_InSingleStoreMode(): void
    {
        $provider = $this->instantiateScopeProvider();
        $provider->setCurrentScopeByCode('base', ScopeInterface::SCOPE_WEBSITES);

        $currentScope = $provider->getCurrentScope();
        $this->assertSame(expected: ScopeInterface::SCOPE_WEBSITES, actual: $currentScope->getScopeType());
        $this->assertSame(expected: 1, actual: $currentScope->getScopeId());
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return ScopeProvider
     */
    private function instantiateScopeProvider(?array $arguments = []): ScopeProvider
    {
        return $this->objectManager->create(
            type: ScopeProvider::class,
            arguments: $arguments,
        );
    }
}
