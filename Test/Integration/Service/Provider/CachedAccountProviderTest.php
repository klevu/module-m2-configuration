<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Provider;

use Klevu\Configuration\Cache\Type\Integration;
use Klevu\Configuration\Cache\Type\Integration as IntegrationCache;
use Klevu\Configuration\Exception\AccountCacheScopeException;
use Klevu\Configuration\Service\Provider\CachedAccountProvider;
use Klevu\Configuration\Service\Provider\CachedAccountProviderInterface;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Klevu\PhpSDK\Model\Account\AccountFeaturesFactory;
use Klevu\PhpSDK\Model\AccountFactory;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Cache\TypeList;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\Provider\CachedAccountProvider
 */
class CachedAccountProviderTest extends TestCase
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
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
        $this->websiteFixturesPool = $this->objectManager->get(WebsiteFixturesPool::class);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->websiteFixturesPool->rollback();
        $this->storeFixturesPool->rollback();
    }

    public function testImplements_AccountFeaturesProviderInterface(): void
    {
        $this->assertInstanceOf(
            expected: CachedAccountProviderInterface::class,
            actual: $this->instantiateAccountFeaturesProvider(),
        );
    }

    public function testPreference_ForAccountFeaturesProviderInterface(): void
    {
        $this->assertInstanceOf(
            expected: CachedAccountProvider::class,
            actual: $this->objectManager->create(CachedAccountProviderInterface::class),
        );
    }

    /**
     * @dataProvider testGet_ThrowsException_IfInvalidScopeType_dataProvider
     */
    public function testGet_ThrowsException_IfInvalidScopeType(mixed $invalidScopeType): void
    {
        $this->expectException(AccountCacheScopeException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Incorrect Scope Provided (%s). Must be one of %s',
                $invalidScopeType,
                implode(', ', [
                    ScopeInterface::SCOPE_WEBSITE,
                    ScopeInterface::SCOPE_WEBSITES,
                    ScopeInterface::SCOPE_STORE,
                    ScopeInterface::SCOPE_STORES,
                ]),
            ),
        );

        $provider = $this->instantiateAccountFeaturesProvider();
        $provider->get(scopeId: 1, scopeType: $invalidScopeType);
    }

    /**
     * @return mixed[][]
     */
    public function testGet_ThrowsException_IfInvalidScopeType_dataProvider(): array
    {
        return [
            [ScopeInterface::SCOPE_GROUPS],
            [ScopeInterface::SCOPE_GROUP],
            [ScopeConfigInterface::SCOPE_TYPE_DEFAULT],
            ['global'],
        ];
    }

    public function testGet_ReturnsCachedData_ForStore(): void
    {
        $this->clearCache();
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get(key: 'test_website');
        $this->createStore([
            'website_id' => $website->getId(),
        ]);
        $store = $this->storeFixturesPool->get(key: 'test_store');
        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $accountData = [
            'jsApiKey' => $jsApiKey,
            'restAuthKey' => $restAuthKey,
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
            'accountFeatures' => [
                'smartCategoryMerchandising' => true,
                'smartRecommendations' => true,
                'preserveLayout' => true,
            ],
        ];
        $account = $this->createAccount($accountData);

        $this->populateCache(
            accountFeatures: $account->getAccountFeatures(),
            websiteId: $website->getId(),
            storeId: $store->getId(),
        );

        $provider = $this->instantiateAccountFeaturesProvider();
        $accountFeatures = $provider->get(scopeId: $store->getId());

        $this->assertInstanceOf(expected: AccountFeatures::class, actual: $accountFeatures);
        $this->assertTrue(
            condition: $accountFeatures->smartCategoryMerchandising,
            message: 'Smart Category Merchandising',
        );
        $this->assertTrue(
            condition: $accountFeatures->smartRecommendations,
            message: 'Smart Recommendations',
        );
        $this->assertTrue(
            condition: $accountFeatures->preserveLayout,
            message: 'Preserve Layout',
        );
    }

    public function testGet_ReturnsCachedData_ForWebsite(): void
    {
        $this->clearCache();
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get(key: 'test_website');

        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $accountData = [
            'jsApiKey' => $jsApiKey,
            'restAuthKey' => $restAuthKey,
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
            'accountFeatures' => [
                'smartCategoryMerchandising' => true,
                'smartRecommendations' => true,
                'preserveLayout' => true,
            ],
        ];
        $account = $this->createAccount($accountData);

        $this->populateCache(
            accountFeatures: $account->getAccountFeatures(),
            websiteId: $website->getId(),
            storeId: null,
        );

        $provider = $this->instantiateAccountFeaturesProvider();
        $accountFeatures = $provider->get(scopeId: $website->getId(), scopeType: ScopeInterface::SCOPE_WEBSITES);

        $this->assertInstanceOf(expected: AccountFeatures::class, actual: $accountFeatures);
        $this->assertTrue(
            condition: $accountFeatures->smartCategoryMerchandising,
            message: 'Smart Category Merchandising',
        );
        $this->assertTrue(
            condition: $accountFeatures->smartRecommendations,
            message: 'Smart Recommendations',
        );
        $this->assertTrue(
            condition: $accountFeatures->preserveLayout,
            message: 'Preserve Layout',
        );
    }

    public function testGet_ReturnsCachedData_ForWebsiteWhenStoreCacheEmpty(): void
    {
        $this->clearCache();
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get(key: 'test_website');
        $this->createStore([
            'website_id' => $website->getId(),
        ]);
        $store = $this->storeFixturesPool->get(key: 'test_store');

        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $accountData = [
            'jsApiKey' => $jsApiKey,
            'restAuthKey' => $restAuthKey,
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
            'accountFeatures' => [
                'smartCategoryMerchandising' => true,
                'smartRecommendations' => true,
                'preserveLayout' => true,
            ],
        ];
        $account = $this->createAccount($accountData);

        $this->populateCache(
            accountFeatures: $account->getAccountFeatures(),
            websiteId: $website->getId(),
            storeId: null,
        );

        $provider = $this->instantiateAccountFeaturesProvider();
        $accountFeatures = $provider->get(scopeId: $store->getId());

        $this->assertInstanceOf(expected: AccountFeatures::class, actual: $accountFeatures);
        $this->assertTrue(
            condition: $accountFeatures->smartCategoryMerchandising,
            message: 'Smart Category Merchandising',
        );
        $this->assertTrue(
            condition: $accountFeatures->smartRecommendations,
            message: 'Smart Recommendations',
        );
        $this->assertTrue(
            condition: $accountFeatures->preserveLayout,
            message: 'Preserve Layout',
        );
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
     * @param mixed[] $accountData
     *
     * @return AccountInterface
     */
    private function createAccount(array $accountData): AccountInterface
    {
        $accountFactory = new AccountFactory();
        $account = $accountFactory->create(data: $accountData);
        $accountFeaturesFactory = new AccountFeaturesFactory();
        $accountFeatures = $accountFeaturesFactory->create(data: $accountData['accountFeatures']);
        $account->setAccountFeatures(accountFeatures: $accountFeatures);

        return $account;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    private function generateAuthKey(int $length): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $return = '';
        while (strlen(string: $return) < $length) {
            $return .= substr(
                string: str_shuffle(string: $characters),
                offset: 0,
                length: $length - strlen(string: $return),
            );
        }

        return $return;
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return CachedAccountProvider
     */
    private function instantiateAccountFeaturesProvider(?array $arguments = []): CachedAccountProvider
    {
        return $this->objectManager->create(
            type: CachedAccountProvider::class,
            arguments: $arguments,
        );
    }
}
