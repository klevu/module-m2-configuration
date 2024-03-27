<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Provider;

use Klevu\Configuration\Service\Provider\WebsiteScopeProvider;
use Klevu\Configuration\Service\Provider\WebsiteScopeProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\Provider\WebsiteScopeProvider
 * @magentoAppIsolation enabled
 */
class WebsiteScopeProviderTest extends TestCase
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

    public function testImplements_WebsiteScopeProviderInterface(): void
    {
        $this->assertInstanceOf(
            expected: WebsiteScopeProviderInterface::class,
            actual: $this->instantiateWebsiteScopeProvider(),
        );
    }

    public function testPreferenceFor_WebsiteScopeProviderInterface(): void
    {
        $this->assertInstanceOf(
            expected: WebsiteScopeProvider::class,
            actual: $this->objectManager->create(WebsiteScopeProviderInterface::class),
        );
    }

    public function testSetCurrentWebsiteByCode_SetsWebsite_ForValidWebsiteCode(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');

        $websiteScopeProvider = $this->instantiateWebsiteScopeProvider();
        $websiteScopeProvider->setCurrentWebsiteByCode($website->getCode());
        $currentWebsite = $websiteScopeProvider->getCurrentWebsite();

        $this->assertSame($website->getCode(), $currentWebsite->getCode());
    }

    public function testSetCurrentWebsiteByCode_throwsException_ForInvalidWebsiteCode(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $websiteScopeProvider = $this->instantiateWebsiteScopeProvider();
        $websiteScopeProvider->setCurrentWebsiteByCode('defawieufbwiueult');
    }

    public function testSetCurrentWebsiteById_SetsWebsite_ForValidWebsiteId(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');

        $websiteScopeProvider = $this->instantiateWebsiteScopeProvider();
        $websiteScopeProvider->setCurrentWebsiteById($website->getId());
        $currentWebsite = $websiteScopeProvider->getCurrentWebsite();

        $this->assertSame($website->getCode(), $currentWebsite->getCode());
    }

    public function testSetCurrentWebsiteByCode_throwsException_ForInvalidWebsiteId(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $websiteScopeProvider = $this->instantiateWebsiteScopeProvider();
        $websiteScopeProvider->setCurrentWebsiteById(90924857209);
    }

    /**
     * @magentoAppArea frontend
     */
    public function testGetCurrentWebsite_ReturnsWebsite_ForFrontend(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');
        $this->createStore([
            'website_id' => $website->getId(),
        ]);
        $store = $this->storeFixturesPool->get('test_store');

        $websiteRepository = $this->objectManager->create(WebsiteRepositoryInterface::class);
        $website = $websiteRepository->getById($website->getId());

        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store->getId());

        $websiteScopeProvider = $this->instantiateWebsiteScopeProvider();
        $currentWebsite = $websiteScopeProvider->getCurrentWebsite();

        $this->assertSame($website->getId(), $currentWebsite->getId());
    }

    /**
     * @magentoAppArea frontend
     */
    public function testSetCurrentWebsite_OverridesStoreManager(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');
        $this->createStore([
            'website_id' => $website->getId(),
        ]);
        $store = $this->storeFixturesPool->get('test_store');

        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store->getCode());

        $websiteScopeProvider = $this->instantiateWebsiteScopeProvider();
        $websiteScopeProvider->setCurrentWebsiteById($website->getId());
        $currentWebsite = $websiteScopeProvider->getCurrentWebsite();

        $this->assertNotNull($website->getId());
        $this->assertSame((int)$website->getId(), (int)$currentWebsite->getId());
    }

    /**
     * @magentoAppArea crontab
     */
    public function testGetCurrentWebsite_ReturnsAdminWebsite_WhenAppAreaIsCronTab(): void
    {
        $websiteScopeProvider = $this->instantiateWebsiteScopeProvider();
        $currentWebsite = $websiteScopeProvider->getCurrentWebsite();

        $this->assertNull($currentWebsite);
    }

    /**
     * @magentoAppArea global
     */
    public function testGetCurrentWebsite_ReturnsAdminWebsite_WhenAppAreaIsGlobal(): void
    {
        $websiteScopeProvider = $this->instantiateWebsiteScopeProvider();
        $currentWebsite = $websiteScopeProvider->getCurrentWebsite();

        $this->assertNull($currentWebsite);
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testGetCurrentWebsite_ReturnsNull_WhenAppAreaIsAdmin_WebsiteParamMissing(): void
    {
        $websiteScopeProvider = $this->instantiateWebsiteScopeProvider();
        $currentWebsite = $websiteScopeProvider->getCurrentWebsite();

        $this->assertNull($currentWebsite);
    }

    /**
     * @magentoAppArea webapi_rest
     */
    public function testGetCurrentWebsite_ReturnsNull_WhenAppAreaIsWebApiRest_WebsiteParamMissing(): void
    {
        $websiteScopeProvider = $this->instantiateWebsiteScopeProvider();
        $currentWebsite = $websiteScopeProvider->getCurrentWebsite();

        $this->assertNull($currentWebsite);
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testGetCurrentWebsite_ReturnsWebsiteInParams_WhenAppAreaIsAdmin(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');

        $mockRequest = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->expects($this->once())
            ->method('getParams')
            ->willReturn(['website' => $website->getId()]);

        $websiteScopeProvider = $this->instantiateWebsiteScopeProvider([
            'request' => $mockRequest,
        ]);
        $currentWebsite = $websiteScopeProvider->getCurrentWebsite();

        $this->assertNotNull((int)$website->getId());
        $this->assertSame((int)$website->getId(), (int)$currentWebsite->getId());
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testGetCurrentWebsite_ReturnsNull_WhenAppAreaIsAdmin_InvalidWebsiteParam(): void
    {
        $mockRequest = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->expects($this->once())
            ->method('getParams')
            ->willReturn(['website' => 948594854]);

        $websiteScopeProvider = $this->instantiateWebsiteScopeProvider([
            'request' => $mockRequest,
        ]);
        $currentWebsite = $websiteScopeProvider->getCurrentWebsite();

        $this->assertNull($currentWebsite);
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testUnsetCurrentWebsite_SetsNull(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');

        $websiteScopeProvider = $this->instantiateWebsiteScopeProvider();
        $websiteScopeProvider->setCurrentWebsite($website->get());
        $websiteScopeProvider->unsetCurrentWebsite();
        $currentStore = $websiteScopeProvider->getCurrentWebsite();

        $this->assertNull($currentStore);
    }

    /**
     * @param mixed[]|null $params
     *
     * @return WebsiteScopeProvider
     */
    private function instantiateWebsiteScopeProvider(?array $params = []): WebsiteScopeProvider
    {
        return $this->objectManager->create(WebsiteScopeProvider::class, $params);
    }
}
