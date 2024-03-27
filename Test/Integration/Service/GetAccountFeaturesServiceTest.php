<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service;

use Klevu\Configuration\Cache\Type\Integration;
use Klevu\Configuration\Cache\Type\Integration as IntegrationCache;
use Klevu\Configuration\Exception\StoreNotIntegratedException;
use Klevu\Configuration\Service\GetAccountFeaturesService;
use Klevu\Configuration\Service\GetAccountFeaturesServiceInterface;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountFeaturesServiceInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountLookupServiceInterface;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Klevu\PhpSDK\Model\Account\AccountFeaturesFactory;
use Klevu\PhpSDK\Model\AccountFactory;
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
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\GetAccountFeaturesService
 */
class GetAccountFeaturesServiceTest extends TestCase
{
    use CurrentScopeTrait;
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

    /**
     * @magentoAppIsolation enabled
     */
    public function testImplements_GetAccountFeaturesServiceInterface(): void
    {
        $this->assertInstanceOf(
            expected: GetAccountFeaturesServiceInterface::class,
            actual: $this->instantiateGetAccountFeaturesService(),
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testPreference_ForGetAccountFeaturesServiceInterface(): void
    {
        $this->assertInstanceOf(
            expected: GetAccountFeaturesService::class,
            actual: $this->objectManager->get(type: GetAccountFeaturesServiceInterface::class),
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecute_ThrowsException_StoreNotIntegrated(): void
    {
        $this->expectException(StoreNotIntegratedException::class);

        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');
        $this->createStore([
            'website_id' => $website->getId(),
        ]);
        $store = $this->storeFixturesPool->get('test_store')->get();
        $currentScope = $this->createCurrentScope($store);

        $service = $this->instantiateGetAccountFeaturesService();
        $service->execute($currentScope);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecute_ReturnsAccountFeatures_FromCache_ForStore(): void
    {
        $this->clearCache();
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');
        $this->createStore([
            'website_id' => $website->getId(),
        ]);
        $store = $this->storeFixturesPool->get('test_store')->get();

        $accountFeatures = [
            'smartCategoryMerchandising' => true,
            'smartRecommendations' => true,
            'preserveLayout' => false,
        ];
        $account = $this->createAccount($accountFeatures);
        $this->populateCache($account->getAccountFeatures(), (int)$store->getWebsiteId(), (int)$store->getId());

        $currentScope = $this->createCurrentScope($store);
        $service = $this->instantiateGetAccountFeaturesService();
        $features = $service->execute($currentScope);

        $this->assertTrue(condition: $features->smartCategoryMerchandising, message: 'Smart Category Merchandising');
        $this->assertTrue(condition: $features->smartRecommendations, message: 'Smart Recommendations');
        $this->assertFalse(condition: $features->preserveLayout, message: 'Preserve Layout');
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecute_ReturnsAccountFeatures_FromCache_ForWebsite(): void
    {
        $this->clearCache();
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');
        $this->createStore([
            'website_id' => $website->getId(),
        ]);
        $store = $this->storeFixturesPool->get('test_store')->get();

        $accountFeatures = [
            'smartCategoryMerchandising' => true,
            'smartRecommendations' => false,
            'preserveLayout' => true,
        ];
        $account = $this->createAccount($accountFeatures);
        $this->populateCache(
            accountFeatures: $account->getAccountFeatures(),
            websiteId: (int)$store->getWebsiteId(),
            storeId: null,
        );

        $currentScope = $this->createCurrentScope($store);
        $service = $this->instantiateGetAccountFeaturesService();
        $features = $service->execute($currentScope);

        $this->assertTrue(condition: $features->smartCategoryMerchandising, message: 'Smart Category Merchandising');
        $this->assertFalse(condition: $features->smartRecommendations, message: 'Smart Recommendations');
        $this->assertTrue(condition: $features->preserveLayout, message: 'Preserve Layout');
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/auth_keys/rest_auth_key klevu-rest-auth-key
     */
    public function testExecute_ReturnsAccountFeatures_CacheEmpty_ForStore(): void
    {
        $this->clearCache();
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');
        $this->createStore([
            'website_id' => $website->getId(),
        ]);
        $store = $this->storeFixturesPool->get('test_store')->get();

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
            className: 'Klevu\Configuration\Service\Account\AccountFeaturesService', // virtualType
            forPreference: true,
        );

        $currentScope = $this->createCurrentScope($store);

        $service = $this->instantiateGetAccountFeaturesService();
        try {
            $features = $service->execute($currentScope);
        } catch (StoreNotIntegratedException $e) {
            $this->fail('Store is not integrated: ' . $e->getMessage());
        }

        $this->assertTrue(condition: $features->smartCategoryMerchandising, message: 'Smart Category Merchandising');
        $this->assertFalse(condition: $features->smartRecommendations, message: 'Smart Recommendations');
        $this->assertTrue(condition: $features->preserveLayout, message: 'Preserve Layout');
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
     * @param AccountFeatures $accountFeatures
     * @param int $websiteId
     * @param int|null $storeId
     *
     * @return void
     */
    private function populateCache(AccountFeatures $accountFeatures, int $websiteId, ?int $storeId = null): void
    {
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
     * @param mixed[] $arguments
     *
     * @return GetAccountFeaturesService
     */
    private function instantiateGetAccountFeaturesService(array $arguments = []): GetAccountFeaturesService
    {
        return $this->objectManager->create(
            type: GetAccountFeaturesService::class,
            arguments: $arguments,
        );
    }
}
