<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\ViewModel\Config;

use Klevu\Configuration\Cache\Type\Integration;
use Klevu\Configuration\Cache\Type\Integration as IntegrationCache;
use Klevu\Configuration\Exception\Integration\InvalidAccountFeatureException;
use Klevu\Configuration\Service\Account\AccountFeaturesService as AccountFeaturesServiceVirtualType;
use Klevu\Configuration\Service\Provider\StoreScopeProviderInterface;
use Klevu\Configuration\ViewModel\Config\AccountFeatures;
use Klevu\Configuration\ViewModel\Config\AccountFeaturesInterface;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountFeaturesServiceInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountLookupServiceInterface;
use Klevu\PhpSDK\Model\Account\AccountFeatures as KlevuSdkAccountFeatures;
use Klevu\PhpSDK\Model\Account\AccountFeaturesFactory;
use Klevu\PhpSDK\Model\AccountFactory;
use Klevu\PhpSDK\Service\Account\AccountFeaturesService;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\CurrentScopeTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Cache\TypeList;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class AccountFeaturesTest extends TestCase
{
    use CurrentScopeTrait;
    use StoreTrait;
    use WebsiteTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;
    /**
     * @var StoreScopeProviderInterface
     */
    private mixed $storeScopeProvider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->storeFixturesPool = $this->objectManager->create(StoreFixturesPool::class);
        $this->websiteFixturesPool = $this->objectManager->create(WebsiteFixturesPool::class);
        $this->storeScopeProvider = $this->objectManager->create(StoreScopeProviderInterface::class);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->storeFixturesPool->rollback();
        $this->websiteFixturesPool->rollback();
        $this->storeScopeProvider->setCurrentStoreById(Store::DEFAULT_STORE_ID);
    }

    public function testImplements_AccountFeaturesViewModelInterface(): void
    {
        $this->assertInstanceOf(
            expected: AccountFeaturesInterface::class,
            actual: $this->instantiateAccountFeaturesViewModel(),
        );
    }

    public function testPreference_ForAccountFeaturesViewModelInterface(): void
    {
        $this->assertInstanceOf(
            expected: AccountFeatures::class,
            actual: $this->objectManager->get(type: AccountFeaturesInterface::class),
        );
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key
     */
    public function testIsAvailable_ThrowsException_InvalidFeature(): void
    {
        $this->expectException(InvalidAccountFeatureException::class);
        $this->expectExceptionMessage(
            "Requested account feature is invalid. Received 'some_string', expected one of "
            . "'smartCategoryMerchandising, smartRecommendations, preserveLayout'.",
        );

        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $currentScope = $this->createCurrentScope($store->get());

        $viewModel = $this->instantiateAccountFeaturesViewModel();

        $viewModel->isAvailable('some_string', $currentScope);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     */
    public function testIsAvailable_ReturnsFalse_NotIntegrated_Store(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $currentScope = $this->createCurrentScope($store->get());
        $viewModel = $this->instantiateAccountFeaturesViewModel();

        $this->assertFalse(
            condition: $viewModel->isAvailable('smartCategoryMerchandising', $currentScope),
            message: 'Smart Category Merchandising',
        );
        $this->assertFalse(
            condition: $viewModel->isAvailable('smartRecommendations', $currentScope),
            message: 'Smart Recommendations',
        );
        $this->assertFalse(
            condition: $viewModel->isAvailable('preserveLayout', $currentScope),
            message: 'Preserve Layout',
        );
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     */
    public function testIsAvailable_ReturnsFalse_NotIntegrated_Website(): void
    {
        $this->createWebsite();
        $websiteFixture = $this->websiteFixturesPool->get('test_website');
        $currentScope = $this->createCurrentScope($websiteFixture->get());
        $viewModel = $this->instantiateAccountFeaturesViewModel();

        $this->assertFalse(
            condition: $viewModel->isAvailable('smartCategoryMerchandising', $currentScope),
            message: 'Smart Category Merchandising',
        );
        $this->assertFalse(
            condition: $viewModel->isAvailable('smartRecommendations', $currentScope),
            message: 'Smart Recommendations',
        );
        $this->assertFalse(
            condition: $viewModel->isAvailable('preserveLayout', $currentScope),
            message: 'Preserve Layout',
        );
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key
     */
    public function testIsAvailable_ReturnsAccountFeatures_FromCache_ForStore(): void
    {
        $this->clearCache();
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $accountFeatures = [
            'smartCategoryMerchandising' => true,
            'smartRecommendations' => true,
            'preserveLayout' => false,
        ];
        $account = $this->createAccount($accountFeatures);
        $this->populateCache(
            accountFeatures: $account->getAccountFeatures(),
            websiteId: (int)$store->getWebsiteId(),
            storeId: (int)$store->getId(),
        );

        $currentScope = $this->createCurrentScope($store->get());

        $viewModel = $this->instantiateAccountFeaturesViewModel();

        $this->assertTrue(
            condition: $viewModel->isAvailable('smartCategoryMerchandising', $currentScope),
            message: 'Smart Category Merchandising',
        );
        $this->assertTrue(
            condition: $viewModel->isAvailable('smartRecommendations', $currentScope),
            message: 'Smart Recommendations',
        );
        $this->assertFalse(
            condition: $viewModel->isAvailable('preserveLayout', $currentScope),
            message: 'Preserve Layout',
        );
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture klevu_test_website_1_website klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_website_1_website klevu_configuration/auth_keys/rest_auth_key rest-auth-key
     */
    public function testIsAvailable_ReturnsAccountFeatures_FromCache_ForWebsite(): void
    {
        $this->clearCache();
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get(key: 'test_website');
        $this->createStore(storeData: [
            'website_id' => $website->getId(),
        ]);
        $store = $this->storeFixturesPool->get(key: 'test_store');

        $accountFeatures = [
            'smartCategoryMerchandising' => false,
            'smartRecommendations' => false,
            'preserveLayout' => true,
        ];
        $account = $this->createAccount($accountFeatures);
        $this->populateCache(
            accountFeatures: $account->getAccountFeatures(),
            websiteId: (int)$store->getWebsiteId(),
            storeId: null,
        );

        $currentScope = $this->createCurrentScope($website->get());

        $viewModel = $this->instantiateAccountFeaturesViewModel();

        $this->assertFalse(
            condition: $viewModel->isAvailable('smartCategoryMerchandising', $currentScope),
            message: 'Smart Category Merchandising',
        );
        $this->assertFalse(
            condition: $viewModel->isAvailable('smartRecommendations', $currentScope),
            message: 'Smart Recommendations',
        );
        $this->assertTrue(
            condition: $viewModel->isAvailable('preserveLayout', $currentScope),
            message: 'Preserve Layout',
        );
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture klevu_test_website_1_website klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_website_1_website klevu_configuration/auth_keys/rest_auth_key rest-auth-key
     */
    public function testIsAvailable_ReturnsAccountFeatures_FromCache_ForWebsite_StoreRequested(): void
    {
        $this->clearCache();
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get(key: 'test_website');
        $this->createStore(storeData: [
            'website_id' => $website->getId(),
        ]);
        $store = $this->storeFixturesPool->get(key: 'test_store');

        $accountFeatures = [
            'smartCategoryMerchandising' => false,
            'smartRecommendations' => false,
            'preserveLayout' => true,
        ];
        $account = $this->createAccount($accountFeatures);
        $this->populateCache(
            accountFeatures: $account->getAccountFeatures(),
            websiteId: (int)$store->getWebsiteId(),
            storeId: null,
        );

        $currentScope = $this->createCurrentScope($store->get());

        $viewModel = $this->instantiateAccountFeaturesViewModel();

        $this->assertFalse(
            condition: $viewModel->isAvailable('smartCategoryMerchandising', $currentScope),
            message: 'Smart Category Merchandising',
        );
        $this->assertFalse(
            condition: $viewModel->isAvailable('smartRecommendations', $currentScope),
            message: 'Smart Recommendations',
        );
        $this->assertTrue(
            condition: $viewModel->isAvailable('preserveLayout', $currentScope),
            message: 'Preserve Layout',
        );
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key
     */
    public function testIsAvailable_ReturnsAccountFeatures_EmptyCache_ForStore(): void
    {
        $this->clearCache();
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $accountData = [
            'jsApiKey' => 'klevu-1234567890',
            'restAuthKey' => '9wueh9uqwhf93oiu',
            'platform' => 'magento',
            'active' => true,
            'companyName' => 'Klevu',
            'email' => 'user@klevu.com',
            'analyticsUrl' => 'stats.ksearchnet.com',
            'indexingUrl' => 'indexing.ksearchnet.com',
            'jsUrl' => 'js.klevu.com',
            'searchUrl' => 'search.klevu.com',
            'smartCategoryMerchandisingUrl' => 'catnav.klevu.com',
            'tiersUrl' => 'tiers.klevu.com',
        ];
        $accountFactory = new AccountFactory();
        $mockAccount = $accountFactory->create(data: $accountData);

        $mockAccountLookup = $this->getMockBuilder(AccountLookupServiceInterface::class)
            ->getMock();
        $mockAccountLookup->expects($this->once())
            ->method('execute')
            ->willReturn($mockAccount);
        $this->objectManager->addSharedInstance(
            instance: $mockAccountLookup,
            className: AccountLookupServiceInterface::class,
            forPreference: true,
        );
        $this->objectManager->addSharedInstance(
            instance: $mockAccountLookup,
            className: 'Klevu\Configuration\Service\Account\AccountLookupService', // virtualType
            forPreference: true,
        );

        $accountFeatures = [
            'smartCategoryMerchandising' => true,
            'smartRecommendations' => false,
            'preserveLayout' => true,
        ];
        $accountFeaturesFactory = new AccountFeaturesFactory();
        $mockAccountFeatures = $accountFeaturesFactory->create(data: $accountFeatures);
        $mockAccountFeaturesService = $this->getMockBuilder(AccountFeaturesServiceInterface::class)
            ->getMock();
        $mockAccountFeaturesService->expects($this->once())
            ->method('execute')
            ->willReturn($mockAccountFeatures);
        $this->objectManager->addSharedInstance(
            instance: $mockAccountFeaturesService,
            className: AccountFeaturesServiceInterface::class,
            forPreference: true,
        );
        $this->objectManager->addSharedInstance(
            instance: $mockAccountFeaturesService,
            className: AccountFeaturesServiceVirtualType::class, // virtualType
            forPreference: true,
        );

        $currentScope = $this->createCurrentScope($store->get());

        $viewModel = $this->instantiateAccountFeaturesViewModel();

        $this->assertTrue(
            condition: $viewModel->isAvailable('smartCategoryMerchandising', $currentScope),
            message: 'Smart Category Merchandising',
        );
        $this->assertFalse(
            condition: $viewModel->isAvailable('smartRecommendations', $currentScope),
            message: 'Smart Recommendations',
        );
        $this->assertTrue(
            condition: $viewModel->isAvailable('preserveLayout', $currentScope),
            message: 'Preserve Layout',
        );
    }

    // phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture klevu_test_website_1_website klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_website_1_website klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key
     */
    public function testIsAvailable_ReturnsAccountFeatures_EmptyCache_ForWebsite(): void
    {
        // phpcs:enable Generic.Files.LineLength.TooLong
        $this->clearCache();
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get(key: 'test_website');

        $accountData = [
            'jsApiKey' => 'klevu-1234567890',
            'restAuthKey' => '9wueh9uqwhf93oiu',
            'platform' => 'magento',
            'active' => true,
            'companyName' => 'Klevu',
            'email' => 'user@klevu.com',
            'analyticsUrl' => 'stats.ksearchnet.com',
            'indexingUrl' => 'indexing.ksearchnet.com',
            'jsUrl' => 'js.klevu.com',
            'searchUrl' => 'search.klevu.com',
            'smartCategoryMerchandisingUrl' => 'catnav.klevu.com',
            'tiersUrl' => 'tiers.klevu.com',
        ];
        $accountFactory = new AccountFactory();
        $mockAccount = $accountFactory->create(data: $accountData);

        $mockAccountLookup = $this->getMockBuilder(AccountLookupServiceInterface::class)
            ->getMock();
        $mockAccountLookup->expects($this->once())
            ->method('execute')
            ->willReturn($mockAccount);
        $this->objectManager->addSharedInstance(
            instance: $mockAccountLookup,
            className: AccountLookupServiceInterface::class,
            forPreference: true,
        );
        $this->objectManager->addSharedInstance(
            instance: $mockAccountLookup,
            className: 'Klevu\Configuration\Service\Account\AccountLookupService', // virtualType
            forPreference: true,
        );

        $accountFeatures = [
            'smartCategoryMerchandising' => true,
            'smartRecommendations' => false,
            'preserveLayout' => true,
        ];
        $accountFeaturesFactory = new AccountFeaturesFactory();
        $mockAccountFeatures = $accountFeaturesFactory->create(data: $accountFeatures);
        $mockAccountFeaturesService = $this->getMockBuilder(AccountFeaturesService::class)
            ->getMock();
        $mockAccountFeaturesService->expects($this->once())
            ->method('execute')
            ->willReturn($mockAccountFeatures);
        $this->objectManager->addSharedInstance(
            instance: $mockAccountFeaturesService,
            className: AccountFeaturesServiceInterface::class,
            forPreference: true,
        );
        $this->objectManager->addSharedInstance(
            instance: $mockAccountFeaturesService,
            className: AccountFeaturesServiceVirtualType::class, // virtualType
            forPreference: true,
        );

        $currentScope = $this->createCurrentScope($website->get());

        $viewModel = $this->instantiateAccountFeaturesViewModel();

        $this->assertTrue(
            condition: $viewModel->isAvailable('smartCategoryMerchandising', $currentScope),
            message: 'Smart Category Merchandising',
        );
        $this->assertFalse(
            condition: $viewModel->isAvailable('smartRecommendations', $currentScope),
            message: 'Smart Recommendations',
        );
        $this->assertTrue(
            condition: $viewModel->isAvailable('preserveLayout', $currentScope),
            message: 'Preserve Layout',
        );
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key
     */
    public function testIsAvailable_ReturnsFalse_AccountNotActive(): void
    {
        $this->clearCache();
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $accountData = [
            'jsApiKey' => 'klevu-1234567890',
            'restAuthKey' => '9wueh9uqwhf93oiu',
            'platform' => 'magento',
            'active' => false,
            'companyName' => 'Klevu',
            'email' => 'user@klevu.com',
            'analyticsUrl' => 'stats.ksearchnet.com',
            'indexingUrl' => 'indexing.ksearchnet.com',
            'jsUrl' => 'js.klevu.com',
            'searchUrl' => 'search.klevu.com',
            'smartCategoryMerchandisingUrl' => 'catnav.klevu.com',
            'tiersUrl' => 'tiers.klevu.com',
        ];
        $accountFactory = new AccountFactory();
        $mockAccount = $accountFactory->create(data: $accountData);

        $mockAccountLookup = $this->getMockBuilder(AccountLookupServiceInterface::class)
            ->getMock();
        $mockAccountLookup->expects($this->once())
            ->method('execute')
            ->willReturn($mockAccount);
        $this->objectManager->addSharedInstance(
            instance: $mockAccountLookup,
            className: AccountLookupServiceInterface::class,
            forPreference: true,
        );
        $this->objectManager->addSharedInstance(
            instance: $mockAccountLookup,
            className: 'Klevu\Configuration\Service\Account\AccountLookupService', // virtualType
            forPreference: true,
        );

        $currentScope = $this->createCurrentScope($store->get());

        $viewModel = $this->instantiateAccountFeaturesViewModel();

        $this->assertFalse(
            condition: $viewModel->isAvailable('smartCategoryMerchandising', $currentScope),
            message: 'Smart Category Merchandising',
        );
        $this->assertFalse(
            condition: $viewModel->isAvailable('smartRecommendations', $currentScope),
            message: 'Smart Recommendations',
        );
        $this->assertFalse(
            condition: $viewModel->isAvailable('preserveLayout', $currentScope),
            message: 'Preserve Layout',
        );
    }

    /**
     * @param KlevuSdkAccountFeatures $accountFeatures
     * @param int $websiteId
     * @param int|null $storeId
     *
     * @return void
     */
    private function populateCache(
        KlevuSdkAccountFeatures $accountFeatures,
        int $websiteId,
        ?int $storeId = null,
    ): void {
        $cacheKey = Integration::TYPE_IDENTIFIER . '_website_' . $websiteId;
        if ($storeId) {
            $cacheKey .= '_store_' . $storeId;
        }
        $serializer = $this->objectManager->get(type: SerializerInterface::class);
        $cache = $this->objectManager->get(type: CacheInterface::class);
        $cache->save(
            data: $serializer->serialize(data: ['accountFeatures' => $accountFeatures]),
            identifier: $cacheKey,
            tags: [IntegrationCache::CACHE_TAG],
            lifeTime: 86400,
        );
    }

    /**
     * @return void
     */
    private function clearCache(): void
    {
        $cacheState = $this->objectManager->get(type: StateInterface::class);
        $cacheState->setEnabled(cacheType: IntegrationCache::TYPE_IDENTIFIER, isEnabled: true);

        $typeList = $this->objectManager->get(TypeList::class);
        $typeList->cleanType(IntegrationCache::TYPE_IDENTIFIER);
    }

    /**
     * @param mixed[] $accountFeatures
     *
     * @return AccountInterface
     */
    private function createAccount(array $accountFeatures = []): AccountInterface
    {
        $jsApiKey = 'klevu-0987654321';
        $restAuthKey = 'qw94uth89q324ht4h89';
        $accountData = [
            'jsApiKey' => $jsApiKey,
            'restAuthKey' => $restAuthKey,
            'platform' => 'magento',
            'active' => true,
            'companyName' => 'Klevu Io',
            'email' => 'someone@klevu.com',
            'analyticsUrl' => 'stat.ksearchnet.com',
            'indexingUrl' => 'index.ksearchnet.com',
            'jsUrl' => 'javascript.klevu.com',
            'searchUrl' => 'search_irl.klevu.com',
            'smartCategoryMerchandisingUrl' => 'smartcatnav.klevu.com',
            'tiersUrl' => 'tiers-qa.klevu.com',
            'accountFeatures' => $accountFeatures,
        ];

        $accountFactory = new AccountFactory();
        $account = $accountFactory->create(data: $accountData);
        $accountFeaturesFactory = new AccountFeaturesFactory();
        $accountFeatures = $accountFeaturesFactory->create(data: $accountData['accountFeatures']);
        $account->setAccountFeatures(accountFeatures: $accountFeatures);

        return $account;
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return AccountFeatures
     */
    private function instantiateAccountFeaturesViewModel(?array $arguments = []): AccountFeatures
    {
        return $this->objectManager->create(
            type: AccountFeatures::class,
            arguments: $arguments,
        );
    }
}
