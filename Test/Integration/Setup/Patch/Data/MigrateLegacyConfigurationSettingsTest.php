<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Setup\Patch\Data;

use Klevu\Configuration\Service\Provider\AuthKeyProvider;
use Klevu\Configuration\Service\Provider\Sdk\BaseUrlsProvider;
use Klevu\Configuration\Setup\Patch\Data\MigrateLegacyConfigurationSettings;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\Writer as ConfigWriter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers MigrateLegacyConfigurationSettings
 * @method MigrateLegacyConfigurationSettings instantiateTestObject(?array $arguments = null)
 * @method MigrateLegacyConfigurationSettings instantiateTestObjectFromInterface(?array $arguments = null)
 */
class MigrateLegacyConfigurationSettingsTest extends TestCase
{
    use ObjectInstantiationTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;
    use WebsiteTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;
    /**
     * @var ScopeConfigInterface|null
     */
    private ?ScopeConfigInterface $scopeConfig = null;
    /**
     * @var ConfigResource|null
     */
    private ?ConfigResource $configResource = null;
    /**
     * @var ConfigWriter|null
     */
    private ?ConfigWriter $configWriter = null;

    /**
     * @return void
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = ObjectManager::getInstance();

        $this->implementationFqcn = MigrateLegacyConfigurationSettings::class;
        $this->interfaceFqcn = DataPatchInterface::class;

        $this->scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $this->configResource = $this->objectManager->get(ConfigResource::class);
        $this->configWriter = $this->objectManager->get(ConfigWriter::class);

        $this->websiteFixturesPool = $this->objectManager->get(WebsiteFixturesPool::class);
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);

        $this->createStore([
            'code' => 'klevu_analytics_test_store_1',
            'key' => 'test_store_1',
        ]);
        $this->createWebsite([
            'code' => 'klevu_analytics_test_website_1',
            'key' => 'test_website_1',
        ]);
        $testWebsite = $this->websiteFixturesPool->get('test_website_1');
        $this->createStore([
            'code' => 'klevu_analytics_test_store_2',
            'key' => 'test_store_2',
            'website_id' => $testWebsite->getId(),
        ]);
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

    public function testGetDependencies(): void
    {
        $dependencies = MigrateLegacyConfigurationSettings::getDependencies();

        $this->assertSame([], $dependencies);
    }

    public function testGetAliases(): void
    {
        $migrateLegacyConfigurationSettingsPatch = $this->instantiateTestObject();
        $aliases = $migrateLegacyConfigurationSettingsPatch->getAliases();

        $this->assertSame([], $aliases);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testApply_MigrateHostname_SetAtStoreScope(): void
    {
        $this->deleteExistingKlevuConfig();

        $testStore1 = $this->storeFixturesPool->get('test_store_1');
        $testStore2 = $this->storeFixturesPool->get('test_store_2');
        $this->configWriter->save(
            path: MigrateLegacyConfigurationSettings::XML_PATH_LEGACY_HOSTNAME,
            value: 'hostname-test.klevu.com',
            scope: ScopeInterface::SCOPE_STORES,
            scopeId: $testStore2->getId(),
        );

        $migrateLegacyConfigurationSettingsPatch = $this->instantiateTestObject();
        $migrateLegacyConfigurationSettingsPatch->apply();

        $this->cleanConfig();

        $this->assertSame(
            expected: 'box.klevu.com', // default value
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_HOSTNAME,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            ),
        );
        $this->assertSame(
            expected: 'box.klevu.com', // default value
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_HOSTNAME,
                ScopeInterface::SCOPE_STORES,
                $testStore1->getId(),
            ),
        );
        $this->assertSame(
            expected: 'hostname-test.klevu.com', // migrated value
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_HOSTNAME,
                ScopeInterface::SCOPE_STORES,
                $testStore2->getId(),
            ),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testApply_MigrateApiUrl_SetAtStoreScope(): void
    {
        $this->deleteExistingKlevuConfig();

        $testStore1 = $this->storeFixturesPool->get('test_store_1');
        $testStore2 = $this->storeFixturesPool->get('test_store_2');
        $this->configWriter->save(
            path: MigrateLegacyConfigurationSettings::XML_PATH_LEGACY_API_URL,
            value: 'api-url-test.klevu.com',
            scope: ScopeInterface::SCOPE_STORES,
            scopeId: $testStore2->getId(),
        );

        $migrateLegacyConfigurationSettingsPatch = $this->instantiateTestObject();
        $migrateLegacyConfigurationSettingsPatch->apply();

        $this->cleanConfig();

        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_API,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            ),
        );
        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_API,
                ScopeInterface::SCOPE_STORES,
                $testStore1->getId(),
            ),
        );
        $this->assertSame(
            expected: 'api-url-test.klevu.com',
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_API,
                ScopeInterface::SCOPE_STORES,
                $testStore2->getId(),
            ),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testApply_MigrateIndexingUrl_SetAtStoreScope(): void
    {
        $this->deleteExistingKlevuConfig();

        $testStore1 = $this->storeFixturesPool->get('test_store_1');
        $testStore2 = $this->storeFixturesPool->get('test_store_2');
        $this->configWriter->save(
            path: MigrateLegacyConfigurationSettings::XML_PATH_LEGACY_INDEXING_URL,
            value: 'indexing-test.klevu.com',
            scope: ScopeInterface::SCOPE_STORES,
            scopeId: $testStore2->getId(),
        );

        $migrateLegacyConfigurationSettingsPatch = $this->instantiateTestObject();
        $migrateLegacyConfigurationSettingsPatch->apply();

        $this->cleanConfig();

        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_INDEXING,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            ),
        );
        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_INDEXING,
                ScopeInterface::SCOPE_STORES,
                $testStore1->getId(),
            ),
        );
        $this->assertSame(
            expected: 'indexing-test.klevu.com',
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_INDEXING,
                ScopeInterface::SCOPE_STORES,
                $testStore2->getId(),
            ),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testApply_MigrateSearchUrl_SetAtStoreScope(): void
    {
        $this->deleteExistingKlevuConfig();

        $testStore1 = $this->storeFixturesPool->get('test_store_1');
        $testStore2 = $this->storeFixturesPool->get('test_store_2');
        $this->configWriter->save(
            path: MigrateLegacyConfigurationSettings::XML_PATH_LEGACY_SEARCH_URL,
            value: 'search-test.klevu.com',
            scope: ScopeInterface::SCOPE_STORES,
            scopeId: $testStore2->getId(),
        );

        $migrateLegacyConfigurationSettingsPatch = $this->instantiateTestObject();
        $migrateLegacyConfigurationSettingsPatch->apply();

        $this->cleanConfig();

        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_SEARCH,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            ),
        );
        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_SEARCH,
                ScopeInterface::SCOPE_STORES,
                $testStore1->getId(),
            ),
        );
        $this->assertSame(
            expected: 'search-test.klevu.com',
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_SEARCH,
                ScopeInterface::SCOPE_STORES,
                $testStore2->getId(),
            ),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testApply_MigrateAnalyticsUrl_SetAtStoreScope(): void
    {
        $this->deleteExistingKlevuConfig();

        $testStore1 = $this->storeFixturesPool->get('test_store_1');
        $testStore2 = $this->storeFixturesPool->get('test_store_2');
        $this->configWriter->save(
            path: MigrateLegacyConfigurationSettings::XML_PATH_LEGACY_ANALYTICS_URL,
            value: 'analytics-test.klevu.com',
            scope: ScopeInterface::SCOPE_STORES,
            scopeId: $testStore2->getId(),
        );

        $migrateLegacyConfigurationSettingsPatch = $this->instantiateTestObject();
        $migrateLegacyConfigurationSettingsPatch->apply();

        $this->cleanConfig();

        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_ANALYTICS,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            ),
        );
        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_ANALYTICS,
                ScopeInterface::SCOPE_STORES,
                $testStore1->getId(),
            ),
        );
        $this->assertSame(
            expected: 'analytics-test.klevu.com',
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_ANALYTICS,
                ScopeInterface::SCOPE_STORES,
                $testStore2->getId(),
            ),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testApply_MigrateJsUrl_SetAtStoreScope(): void
    {
        $this->deleteExistingKlevuConfig();

        $testStore1 = $this->storeFixturesPool->get('test_store_1');
        $testStore2 = $this->storeFixturesPool->get('test_store_2');
        $this->configWriter->save(
            path: MigrateLegacyConfigurationSettings::XML_PATH_LEGACY_JS_URL,
            value: 'js-test.klevu.com',
            scope: ScopeInterface::SCOPE_STORES,
            scopeId: $testStore2->getId(),
        );

        $migrateLegacyConfigurationSettingsPatch = $this->instantiateTestObject();
        $migrateLegacyConfigurationSettingsPatch->apply();

        $this->cleanConfig();

        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_JS,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            ),
        );
        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_JS,
                ScopeInterface::SCOPE_STORES,
                $testStore1->getId(),
            ),
        );
        $this->assertSame(
            expected: 'js-test.klevu.com',
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_JS,
                ScopeInterface::SCOPE_STORES,
                $testStore2->getId(),
            ),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testApply_MigrateTiersUrl_SetAtStoreScope(): void
    {
        $this->deleteExistingKlevuConfig();

        $testStore1 = $this->storeFixturesPool->get('test_store_1');
        $testStore2 = $this->storeFixturesPool->get('test_store_2');
        $this->configWriter->save(
            path: MigrateLegacyConfigurationSettings::XML_PATH_LEGACY_TIERS_URL,
            value: 'tiers-test.klevu.com',
            scope: ScopeInterface::SCOPE_STORES,
            scopeId: $testStore2->getId(),
        );

        $migrateLegacyConfigurationSettingsPatch = $this->instantiateTestObject();
        $migrateLegacyConfigurationSettingsPatch->apply();

        $this->cleanConfig();

        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_TIERS,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            ),
        );
        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_TIERS,
                ScopeInterface::SCOPE_STORES,
                $testStore1->getId(),
            ),
        );
        $this->assertSame(
            expected: 'tiers-test.klevu.com',
            actual: $this->scopeConfig->getValue(
                BaseUrlsProvider::CONFIG_XML_PATH_URL_TIERS,
                ScopeInterface::SCOPE_STORES,
                $testStore2->getId(),
            ),
        );
    }

    public function testApply_DoesNotThrowTypeError_WhenNoLegacySettings(): void
    {
        $this->deleteExistingKlevuConfig();

        $testStore1 = $this->storeFixturesPool->get('test_store_1');
        $testStore2 = $this->storeFixturesPool->get('test_store_2');

        $migrateLegacyConfigurationSettingsPatch = $this->instantiateTestObject();
        $migrateLegacyConfigurationSettingsPatch->apply();

        $this->cleanConfig();

        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            ),
        );
        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY,
                ScopeInterface::SCOPE_STORES,
                $testStore1->getId(),
            ),
        );
        $this->assertNull(
            actual: $this->scopeConfig->getValue(
                AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY,
                ScopeInterface::SCOPE_STORES,
                $testStore2->getId(),
            ),
        );
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    private function deleteExistingKlevuConfig(): void
    {
        $connection = $this->configResource->getConnection();
        $connection->delete(
            $this->configResource->getMainTable(),
            [
                'path like "klevu%"',
            ],
        );

        $this->cleanConfig();
    }

    /**
     * @return void
     */
    private function cleanConfig(): void
    {
        if (method_exists($this->scopeConfig, 'clean')) {
            $this->scopeConfig->clean();
        }
    }
}
