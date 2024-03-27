<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Provider\Stores\Config;

use Klevu\Configuration\Service\Provider\ApiKeyProvider;
use Klevu\Configuration\Service\Provider\AuthKeyProvider;
use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Configuration\Service\Provider\Stores\Config\AuthKeysCollectionProvider;
use Klevu\Configuration\Service\Provider\Stores\Config\AuthKeysCollectionProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\Writer as ConfigWriter;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\Configuration\Service\Provider\Stores\Config\AuthKeysCollectionProvider
 * @magentoConfigFixture default/general/single_store_mode/enabled 0
 * @magentoConfigFixture default_store general/single_store_mode/enabled 0
 */
class AuthKeysCollectionProviderTest extends TestCase
{
    use SetAuthKeysTrait;
    use StoreTrait;
    use WebsiteTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager;

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

    public function testImplements_AuthKeysProviderInterface(): void
    {
        $apiKeyProvider = $this->instantiateAuthKeysCollectionProvider();

        $this->assertInstanceOf(AuthKeysCollectionProviderInterface::class, $apiKeyProvider);
    }

    public function testPreference_ForAuthKeysInterface(): void
    {
        $apiKeyProvider = $this->objectManager->create(AuthKeysCollectionProviderInterface::class);

        $this->assertInstanceOf(AuthKeysCollectionProvider::class, $apiKeyProvider);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testGetAll_ReturnsCollection(): void
    {
        /** @var ConfigWriter $configWriter */
        $configWriter = $this->objectManager->get(ConfigWriter::class);
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        foreach ($storeManager->getWebsites() as $website) {
            $configWriter->delete(
                path: ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY,
                scope: ScopeInterface::SCOPE_WEBSITES,
                scopeId: $website->getId(),
            );
            $configWriter->delete(
                path: AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY,
                scope: ScopeInterface::SCOPE_WEBSITES,
                scopeId: $website->getId(),
            );
        }
        foreach ($storeManager->getStores() as $store) {
            $configWriter->delete(
                path: ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY,
                scope: ScopeInterface::SCOPE_STORES,
                scopeId: $store->getId(),
            );
            $configWriter->delete(
                path: AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY,
                scope: ScopeInterface::SCOPE_STORES,
                scopeId: $store->getId(),
            );
        }
        $configWriter->save(
            path: ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY,
            value: 'klevu-1234567890',
            scope: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            scopeId: 0,
        );
        $configWriter->save(
            path: AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY,
            value: 'ABCDE1234567890',
            scope: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            scopeId: 0,
        );

        $provider = $this->instantiateAuthKeysCollectionProvider();
        $collection = $provider->getAll(false);

        $this->assertInstanceOf(expected: ConfigCollection::class, actual: $collection);
        $select = $collection->getSelect();

        $pattern = "#SELECT `main_table`.* FROM#";
        $matches = [];
        preg_match($pattern, $select->__toString(), $matches);
        $this->assertCount(1, $matches, 'Select All Fields');

        $pattern = "#WHERE.*\(`path` LIKE 'klevu_configuration/auth_keys/%'\)#";
        $matches = [];
        preg_match($pattern, $select->__toString(), $matches);
        $this->assertCount(1, $matches, 'Filter By Auth Key Path');

        $pattern = "#WHERE.*\(`scope` IN\('store', 'stores', 'website', 'websites'\)\)#";
        $matches = [];
        preg_match($pattern, $select->__toString(), $matches);
        $this->assertCount(1, $matches, 'Filter By Scope');

        $this->assertIsArray(actual: $collection->getItems());
        $this->assertCount(0, $collection->getItems());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGet_ReturnsCollection_filteredByScope(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');
        $this->createStore([
            'website_id' => $website->getId(),
        ]);
        $store = $this->storeFixturesPool->get('test_store')->get();
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope(scope: $store);

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-key',
            restAuthKey: 'klevu-rest-key',
        );

        $provider = $this->instantiateAuthKeysCollectionProvider();
        $filter = [
            'scope' => ScopeInterface::SCOPE_STORES,
            'scope_id' => $store->getId(),
        ];
        $collection = $provider->get($filter, false);

        $this->assertInstanceOf(expected: ConfigCollection::class, actual: $collection);
        $select = $collection->getSelect();

        $pattern = "#SELECT `main_table`.* FROM#";
        $matches = [];
        preg_match($pattern, $select->__toString(), $matches);
        $this->assertCount(1, $matches, 'Select All Fields');

        $pattern = "#WHERE.*\(`path` LIKE 'klevu_configuration/auth_keys/%'\)#";
        $matches = [];
        preg_match($pattern, $select->__toString(), $matches);
        $this->assertCount(1, $matches, 'Filter By Auth Key Path');

        $pattern = "#WHERE.*\(`scope` = '" . ScopeInterface::SCOPE_STORES . "'\)"
            . " AND \(`scope_id` = '" . $store->getId() . "'\)#";
        $matches = [];
        preg_match($pattern, $select->__toString(), $matches);
        $this->assertCount(1, $matches, 'Filter By Scope');

        $this->assertIsArray(actual: $collection->getItems());
        $this->assertCount(expectedCount: 2, haystack: $collection->getItems());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGet_ReturnsCollection_SingleStoreMode(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope(scope: $storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-key',
            restAuthKey: 'klevu-rest-key',
        );

        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 1,
        );

        $provider = $this->instantiateAuthKeysCollectionProvider();
        $collection = $provider->get([
            'scope' => ScopeInterface::SCOPE_STORES,
            'scope_id' => $storeFixture->getId(),
        ]);

        $this->assertInstanceOf(expected: ConfigCollection::class, actual: $collection);

        $this->assertIsArray(actual: $collection->getItems());
        $this->assertCount(2, $collection->getItems());
    }

    /**
     * @magentoAppIsolation enabled
     * @dataProvider testGet_ThrowsException_WhenFilterDataIsMissing_DataProvider
     */
    public function testGet_ThrowsException_WhenFilterDataIsMissing(mixed $scope, mixed $scopeId): void
    {
        $this->expectException(\InvalidArgumentException::class);
        if (null === $scope) {
            $this->expectExceptionMessage('Filter array is missing "scope" key.');
        }
        if (null === $scopeId) {
            $this->expectExceptionMessage('Filter array is missing "scope_id" key.');
        }

        $provider = $this->instantiateAuthKeysCollectionProvider();
        $filter = [
            'scope' => $scope,
            'scope_id' => $scopeId,
        ];
        $provider->get($filter, false);
    }

    /**
     * @return mixed[][]
     */
    public function testGet_ThrowsException_WhenFilterDataIsMissing_DataProvider(): array
    {
        return [
            [null, '1'],
            [ScopeInterface::SCOPE_STORES, null],
        ];
    }

    /**
     * @magentoAppIsolation enabled
     * @dataProvider testGet_ThrowsException_WhenScopeIdInvalidType_DataProvider
     */
    public function testGet_ThrowsException_WhenScopeIdInvalidType(mixed $scope, mixed $scopeId): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            '#Invalid Argument: Invalid Scope ID provided. Expected string, int or null; received ' .
            str_replace('\\', '\\\\', get_debug_type($scopeId)) . '.#',
        );

        $provider = $this->instantiateAuthKeysCollectionProvider();
        $filter = [
            'scope' => $scope,
            'scope_id' => $scopeId,
        ];
        $provider->get($filter, false);
    }

    /**
     * @return mixed[][]
     */
    public function testGet_ThrowsException_WhenScopeIdInvalidType_DataProvider(): array
    {
        return [
//            [ScopeInterface::SCOPE_WEBSITE, false], // @TODO add when channels are available
//            [ScopeInterface::SCOPE_WEBSITES, true], // @TODO add when channels are available
//            [ScopeInterface::SCOPE_WEBSITE, new DataObject(['1', '2'])], // @TODO add when channels are available
            [ScopeInterface::SCOPE_STORE, 12.34],
            [ScopeInterface::SCOPE_STORES, ['1', '2']],
        ];
    }

    /**
     * @magentoAppIsolation enabled
     * @dataProvider testGet_ThrowsException_WhenScopeIdInvalidValue_DataProvider
     */
    public function testGet_ThrowsException_WhenScopeIdInvalidValue(mixed $scope, mixed $scopeId): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            '#Invalid Argument: Invalid Scope ID provided. Expected numeric value or null; received '
            . $scopeId . ' \(' . get_debug_type($scopeId) . '\).#',
        );

        $provider = $this->instantiateAuthKeysCollectionProvider();
        $filter = [
            'scope' => $scope,
            'scope_id' => $scopeId,
        ];
        $provider->get($filter, false);
    }

    /**
     * @return mixed[][]
     */
    public function testGet_ThrowsException_WhenScopeIdInvalidValue_DataProvider(): array
    {
        return [
            [ScopeInterface::SCOPE_STORE, '12345f'],
            [ScopeInterface::SCOPE_STORES, 'string'],
//            [ScopeInterface::SCOPE_WEBSITE, '12.34'], // @TODO add when channels are available
//            [ScopeInterface::SCOPE_WEBSITES, '12.34'], // @TODO add when channels are available
        ];
    }

    /**
     * @magentoAppIsolation enabled
     * @dataProvider testGet_ThrowsException_WhenScopeInvalidType_DataProvider
     */
    public function testGet_ThrowsException_WhenScopeInvalidType(mixed $scope, mixed $scopeId): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            '#Invalid Argument: Invalid Scope provided. Expected string; received '
            . get_debug_type($scope) . '.#',
        );

        $provider = $this->instantiateAuthKeysCollectionProvider();
        $filter = [
            'scope' => $scope,
            'scope_id' => $scopeId,
        ];
        $provider->get($filter, false);
    }

    /**
     * @return mixed[][]
     */
    public function testGet_ThrowsException_WhenScopeInvalidType_DataProvider(): array
    {
        return [
            [0, '1'],
            [10, '1'],
            [false, '1'],
            [true, '1'],
            [['1', '2'], '1'],
        ];
    }

    /**
     * @magentoAppIsolation enabled
     * @dataProvider testGet_ThrowsException_WhenScopeInvalidValue_DataProvider
     */
    public function testGet_ThrowsException_WhenScopeInvalidValue(mixed $scope, mixed $scopeId): void
    {
        $allowedScopes = [
            ScopeInterface::SCOPE_STORE,
            ScopeInterface::SCOPE_STORES,
//            ScopeInterface::SCOPE_WEBSITE,  // @TODO add when channels are available
//            ScopeInterface::SCOPE_WEBSITES, // @TODO add when channels are available
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            '#Invalid Argument: Invalid Scope provided. Expected one of '
            . implode(', ', $allowedScopes) . '; received ' . $scope . '.#',
        );

        $provider = $this->instantiateAuthKeysCollectionProvider();
        $filter = [
            'scope' => $scope,
            'scope_id' => $scopeId,
        ];
        $provider->get($filter, false);
    }

    /**
     * @return mixed[][]
     */
    public function testGet_ThrowsException_WhenScopeInvalidValue_DataProvider(): array
    {
        return [
            ['0', '1'],
            ['string', '1'],
            [ScopeInterface::SCOPE_GROUP, '1'],
            [ScopeInterface::SCOPE_GROUPS, '1'],
            [ScopeInterface::SCOPE_WEBSITE, '1'], // @TODO remove when channels are available
            [ScopeInterface::SCOPE_WEBSITES, '1'], // @TODO remove when channels are available
        ];
    }

    /**
     * @param mixed[] $arguments
     *
     * @return AuthKeysCollectionProvider
     */
    private function instantiateAuthKeysCollectionProvider(?array $arguments = []): AuthKeysCollectionProvider
    {
        return $this->objectManager->create(
            type: AuthKeysCollectionProvider::class,
            arguments: $arguments,
        );
    }
}
