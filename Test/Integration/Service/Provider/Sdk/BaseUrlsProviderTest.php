<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Configuration\Test\Integration\Service\Provider\Sdk;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Configuration\Service\Provider\Sdk\BaseUrlsProvider;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\Writer as ConfigWriter;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\App\ReinitableConfig;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\Provider\Sdk\BaseUrlsProvider
 * @method BaseUrlsProvider instantiateTestObject(array $args = [])
 */
class BaseUrlsProviderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use TestImplementsInterfaceTrait;
    use TestInterfacePreferenceTrait;
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
        parent::setUp();

        $this->objectManager = ObjectManager::getInstance();

        $this->implementationFqcn = BaseUrlsProvider::class;
        $this->interfaceFqcn = BaseUrlsProviderInterface::class;

        $this->storeFixturesPool = $this->objectManager->create(StoreFixturesPool::class);
        $this->websiteFixturesPool = $this->objectManager->create(WebsiteFixturesPool::class);
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

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testGetUrls_WithoutConfigOverrides(): void
    {
        $this->deleteExistingUrlsFromConfig();

        /** @var ScopeProviderInterface $scopeProvider */
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);

        $baseUrlsProvider = $this->instantiateTestObject();

        // Default Scope
        $scopeProvider->unsetCurrentScope();

        $this->assertSame('api.ksearchnet.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats.ksearchnet.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        $this->assertSame('box.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing.ksearchnet.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing.ksearchnet.com/v2', $baseUrlsProvider->getV2IndexingUrl());
        $this->assertSame('js.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSearchUrl());
        $this->assertSame('tiers.klevu.com', $baseUrlsProvider->getTiersUrl());

        // Website Scope
        $website = $storeManager->getWebsite('base');
        $scopeProvider->setCurrentScope($website);

        $this->assertSame('api.ksearchnet.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats.ksearchnet.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        $this->assertSame('box.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing.ksearchnet.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing.ksearchnet.com/v2', $baseUrlsProvider->getV2IndexingUrl());
        $this->assertSame('js.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSearchUrl());
        $this->assertSame('tiers.klevu.com', $baseUrlsProvider->getTiersUrl());

        // Store Scope
        $store = $storeManager->getStore('default');
        $scopeProvider->setCurrentScope($store);

        $this->assertSame('api.ksearchnet.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats.ksearchnet.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        $this->assertSame('box.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing.ksearchnet.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing.ksearchnet.com/v2', $baseUrlsProvider->getV2IndexingUrl());
        $this->assertSame('js.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSearchUrl());
        $this->assertSame('tiers.klevu.com', $baseUrlsProvider->getTiersUrl());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testGetUrls_WithFallbackProvider(): void
    {
        $this->deleteExistingUrlsFromConfig();

        $mockBaseUrlsProvider = $this->getMockBuilder(BaseUrlsProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlFixtures = [
            'getApiUrl' => 'api-custom.klevu.com',
            'getAnalyticsUrl' => 'stats-custom.klevu.com',
            'getSmartCategoryMerchandisingUrl' => 'cn-custom.klevu.com',
            'getMerchantCenterUrl' => 'box-custom.klevu.com',
            'getIndexingUrl' => 'indexing-custom.klevu.com',
            'getJsUrl' => 'js-custom.klevu.com',
            'getSearchUrl' => 'cs-custom.klevu.com',
            'getTiersUrl' => 'tiers-custom.klevu.com',
        ];
        foreach ($urlFixtures as $methodName => $returnValue) {
            $mockBaseUrlsProvider->method($methodName)
                ->willReturn($returnValue);
        }

        /** @var ScopeProviderInterface $scopeProvider */
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);

        $baseUrlsProvider = $this->instantiateTestObject([
            'fallbackBaseUrlsProvider' => $mockBaseUrlsProvider,
        ]);

        // Default Scope
        $scopeProvider->unsetCurrentScope();
        $this->assertSame('api-custom.klevu.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats-custom.klevu.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame('cn-custom.klevu.com', $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        // KMC URL hardcoded to config.xml
        $this->assertSame('box.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing-custom.klevu.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing-custom.klevu.com/v2', $baseUrlsProvider->getV2IndexingUrl());
        $this->assertSame('js-custom.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame('cs-custom.klevu.com', $baseUrlsProvider->getSearchUrl());
        // Tiers URL hardcoded to config.xml
        $this->assertSame('tiers.klevu.com', $baseUrlsProvider->getTiersUrl());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testGetUrls_SetAtDefaultScope(): void
    {
        $this->deleteExistingUrlsFromConfig();

        /** @var ConfigWriter $configWriter */
        $configWriter = $this->objectManager->get(ConfigWriter::class);
        $urlFixtures = [
            BaseUrlsProvider::CONFIG_XML_PATH_URL_API => 'api-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_ANALYTICS => 'stats-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_CAT_NAV => 'cn-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_HOSTNAME => 'box-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_INDEXING => 'indexing-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_JS => 'js-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_SEARCH => 'cs-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_TIERS => 'tiers-custom.klevu.com',
        ];
        foreach ($urlFixtures as $configPath => $configValue) {
            $configWriter->save(
                path: $configPath,
                value: $configValue,
                scope: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                scopeId: 0,
            );
        }

        /** @var ReinitableConfig $reinitableConfig */
        $reinitableConfig = $this->objectManager->get(ReinitableConfig::class);
        $reinitableConfig->reinit();

        /** @var ScopeProviderInterface $scopeProvider */
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);

        $baseUrlsProvider = $this->instantiateTestObject();

        // Default Scope
        $scopeProvider->unsetCurrentScope();

        $this->assertSame('api-custom.klevu.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats-custom.klevu.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame('cn-custom.klevu.com', $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        $this->assertSame('box-custom.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing-custom.klevu.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing-custom.klevu.com/v2', $baseUrlsProvider->getv2IndexingUrl());
        $this->assertSame('js-custom.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame('cs-custom.klevu.com', $baseUrlsProvider->getSearchUrl());
        $this->assertSame('tiers-custom.klevu.com', $baseUrlsProvider->getTiersUrl());

        // Website Scope
        $website = $storeManager->getWebsite('base');
        $scopeProvider->setCurrentScope($website);

        $this->assertSame('api-custom.klevu.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats-custom.klevu.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame('cn-custom.klevu.com', $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        $this->assertSame('box-custom.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing-custom.klevu.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing-custom.klevu.com/v2', $baseUrlsProvider->getV2IndexingUrl());
        $this->assertSame('js-custom.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame('cs-custom.klevu.com', $baseUrlsProvider->getSearchUrl());
        $this->assertSame('tiers-custom.klevu.com', $baseUrlsProvider->getTiersUrl());

        // Store Scope
        $store = $storeManager->getStore('default');
        $scopeProvider->setCurrentScope($store);

        $this->assertSame('api-custom.klevu.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats-custom.klevu.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame('cn-custom.klevu.com', $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        $this->assertSame('box-custom.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing-custom.klevu.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing-custom.klevu.com/v2', $baseUrlsProvider->getV2IndexingUrl());
        $this->assertSame('js-custom.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame('cs-custom.klevu.com', $baseUrlsProvider->getSearchUrl());
        $this->assertSame('tiers-custom.klevu.com', $baseUrlsProvider->getTiersUrl());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testGetUrls_SetAtWebsiteScope(): void
    {
        $this->createWebsite([
            'code' => 'klevu_config_test_website_1',
            'key' => 'test_website_1',
        ]);
        $website = $this->websiteFixturesPool->get('test_website_1');
        $this->createStore([
            'code' => 'klevu_config_test_store_1',
            'key' => 'test_store_1',
            'website_id' => $website->getId(),
        ]);

        $this->deleteExistingUrlsFromConfig();

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $website = $storeManager->getWebsite('base');

        /** @var ConfigWriter $configWriter */
        $configWriter = $this->objectManager->get(ConfigWriter::class);
        $urlFixtures = [
            BaseUrlsProvider::CONFIG_XML_PATH_URL_API => 'api-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_ANALYTICS => 'stats-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_CAT_NAV => 'cn-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_HOSTNAME => 'box-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_INDEXING => 'indexing-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_JS => 'js-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_SEARCH => 'cs-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_TIERS => 'tiers-custom.klevu.com',
        ];
        foreach ($urlFixtures as $configPath => $configValue) {
            $configWriter->save(
                path: $configPath,
                value: $configValue,
                scope: ScopeInterface::SCOPE_WEBSITES,
                scopeId: $website->getId(),
            );
        }

        /** @var ReinitableConfig $reinitableConfig */
        $reinitableConfig = $this->objectManager->get(ReinitableConfig::class);
        $reinitableConfig->reinit();

        /** @var ScopeProviderInterface $scopeProvider */
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);

        $baseUrlsProvider = $this->instantiateTestObject();

        // Default Scope
        $scopeProvider->unsetCurrentScope();

        $this->assertSame('api.ksearchnet.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats.ksearchnet.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        $this->assertSame('box.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing.ksearchnet.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing.ksearchnet.com/v2', $baseUrlsProvider->getV2IndexingUrl());
        $this->assertSame('js.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSearchUrl());
        $this->assertSame('tiers.klevu.com', $baseUrlsProvider->getTiersUrl());

        // Website Scope
        $website = $storeManager->getWebsite('base');
        $scopeProvider->setCurrentScope($website);

        $this->assertSame('api-custom.klevu.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats-custom.klevu.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame('cn-custom.klevu.com', $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        $this->assertSame('box-custom.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing-custom.klevu.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing-custom.klevu.com/v2', $baseUrlsProvider->getV2IndexingUrl());
        $this->assertSame('js-custom.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame('cs-custom.klevu.com', $baseUrlsProvider->getSearchUrl());
        $this->assertSame('tiers-custom.klevu.com', $baseUrlsProvider->getTiersUrl());

        // Store Scope (within website)
        $store = $storeManager->getStore('default');
        $scopeProvider->setCurrentScope($store);

        $this->assertSame('api-custom.klevu.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats-custom.klevu.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame('cn-custom.klevu.com', $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        $this->assertSame('box-custom.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing-custom.klevu.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing-custom.klevu.com/v2', $baseUrlsProvider->getV2IndexingUrl());
        $this->assertSame('js-custom.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame('cs-custom.klevu.com', $baseUrlsProvider->getSearchUrl());
        $this->assertSame('tiers-custom.klevu.com', $baseUrlsProvider->getTiersUrl());

        // Store Scope (outwith website)
        $store = $storeManager->getStore('klevu_config_test_store_1');
        $scopeProvider->setCurrentScope($store);

        $this->assertSame('api.ksearchnet.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats.ksearchnet.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        $this->assertSame('box.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing.ksearchnet.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing.ksearchnet.com/v2', $baseUrlsProvider->getV2IndexingUrl());
        $this->assertSame('js.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSearchUrl());
        $this->assertSame('tiers.klevu.com', $baseUrlsProvider->getTiersUrl());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testGetUrls_SetAtStoreScope(): void
    {
        $this->createWebsite([
            'code' => 'klevu_config_test_website_1',
            'key' => 'test_website_1',
        ]);
        $website = $this->websiteFixturesPool->get('test_website_1');
        $this->createStore([
            'code' => 'klevu_config_test_store_1',
            'key' => 'test_store_1',
            'website_id' => $website->getId(),
        ]);

        $this->deleteExistingUrlsFromConfig();

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $store = $storeManager->getStore('default');

        /** @var ConfigWriter $configWriter */
        $configWriter = $this->objectManager->get(ConfigWriter::class);
        $urlFixtures = [
            BaseUrlsProvider::CONFIG_XML_PATH_URL_API => 'api-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_ANALYTICS => 'stats-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_CAT_NAV => 'cn-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_HOSTNAME => 'box-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_INDEXING => 'indexing-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_JS => 'js-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_SEARCH => 'cs-custom.klevu.com',
            BaseUrlsProvider::CONFIG_XML_PATH_URL_TIERS => 'tiers-custom.klevu.com',
        ];
        foreach ($urlFixtures as $configPath => $configValue) {
            $configWriter->save(
                path: $configPath,
                value: $configValue,
                scope: ScopeInterface::SCOPE_STORES,
                scopeId: $store->getId(),
            );
        }

        /** @var ReinitableConfig $reinitableConfig */
        $reinitableConfig = $this->objectManager->get(ReinitableConfig::class);
        $reinitableConfig->reinit();

        /** @var ScopeProviderInterface $scopeProvider */
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);

        $baseUrlsProvider = $this->instantiateTestObject();

        // Default Scope
        $scopeProvider->unsetCurrentScope();

        $this->assertSame('api.ksearchnet.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats.ksearchnet.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        $this->assertSame('box.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing.ksearchnet.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing.ksearchnet.com/v2', $baseUrlsProvider->getV2IndexingUrl());
        $this->assertSame('js.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSearchUrl());
        $this->assertSame('tiers.klevu.com', $baseUrlsProvider->getTiersUrl());

        // Website Scope
        $website = $storeManager->getWebsite('base');
        $scopeProvider->setCurrentScope($website);

        $this->assertSame('api.ksearchnet.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats.ksearchnet.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        $this->assertSame('box.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing.ksearchnet.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing.ksearchnet.com/v2', $baseUrlsProvider->getV2IndexingUrl());
        $this->assertSame('js.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSearchUrl());
        $this->assertSame('tiers.klevu.com', $baseUrlsProvider->getTiersUrl());

        // Store Scope (within website)
        $store = $storeManager->getStore('default');
        $scopeProvider->setCurrentScope($store);

        $this->assertSame('api-custom.klevu.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats-custom.klevu.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame('cn-custom.klevu.com', $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        $this->assertSame('box-custom.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing-custom.klevu.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing-custom.klevu.com/v2', $baseUrlsProvider->getV2IndexingUrl());
        $this->assertSame('js-custom.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame('cs-custom.klevu.com', $baseUrlsProvider->getSearchUrl());
        $this->assertSame('tiers-custom.klevu.com', $baseUrlsProvider->getTiersUrl());

        // Store Scope (outwith website)
        $store = $storeManager->getStore('klevu_config_test_store_1');
        $scopeProvider->setCurrentScope($store);

        $this->assertSame('api.ksearchnet.com', $baseUrlsProvider->getApiUrl());
        $this->assertSame('stats.ksearchnet.com', $baseUrlsProvider->getAnalyticsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSmartCategoryMerchandisingUrl());
        $this->assertSame('box.klevu.com', $baseUrlsProvider->getMerchantCenterUrl());
        $this->assertSame('indexing.ksearchnet.com', $baseUrlsProvider->getIndexingUrl());
        $this->assertSame('indexing.ksearchnet.com/v2', $baseUrlsProvider->getV2IndexingUrl());
        $this->assertSame('js.klevu.com', $baseUrlsProvider->getJsUrl());
        $this->assertSame(null, $baseUrlsProvider->getSearchUrl());
        $this->assertSame('tiers.klevu.com', $baseUrlsProvider->getTiersUrl());
    }

    /**
     * @return void
     */
    private function deleteExistingUrlsFromConfig(): void
    {
        /** @var ResourceConnection $resourceConnection */
        $resourceConnection = $this->objectManager->get(ResourceConnection::class);
        $connection = $resourceConnection->getConnection();
        $connection->delete(
            table: $resourceConnection->getTableName('core_config_data'),
            where: 'path LIKE "klevu_configuration/developer/url_%"',
        );

        /** @var ReinitableConfig $reinitableConfig */
        $reinitableConfig = $this->objectManager->get(ReinitableConfig::class);
        $reinitableConfig->reinit();
    }
}
