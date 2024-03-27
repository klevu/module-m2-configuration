<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Action;

use Klevu\Configuration\Cache\Type\Integration as IntegrationCache;
use Klevu\Configuration\Service\Action\CacheAccountAction;
use Klevu\Configuration\Service\Action\CacheAccountActionInterface;
use Klevu\Configuration\Service\Provider\CachedAccountProviderInterface;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Model\Account\AccountFeaturesFactory;
use Klevu\PhpSDK\Model\AccountFactory;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Cache\TypeList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\Action\CacheAccountAction
 */
class CacheAccountActionTest extends TestCase
{
    use StoreTrait;

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
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->storeFixturesPool->rollback();
    }

    public function testImplements_CacheAccountActionInterface(): void
    {
        $this->assertInstanceOf(
            expected: CacheAccountActionInterface::class,
            actual: $this->instantiateCacheAccountActionAction(),
        );
    }

    public function testPreference_ForCacheAccountActionInterface(): void
    {
        $this->assertInstanceOf(
            expected: CacheAccountAction::class,
            actual: $this->objectManager->create(CacheAccountActionInterface::class),
        );
    }

    public function testExecute_SavesDataToCache_ForStore_CacheEmpty(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get(key: 'test_store');
        $this->clearCache();

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

        $cacheAccountAction = $this->instantiateCacheAccountActionAction();
        $cacheAccountAction->execute(
            accountFeatures: $account->getAccountFeatures(),
            scopeId: $store->getId(),
        );

        $cacheAccountProvider = $this->objectManager->get(CachedAccountProviderInterface::class);
        $accountFeatures = $cacheAccountProvider->get(scopeId: (int)$store->getId());

        $this->assertTrue(
            condition: $accountFeatures->smartCategoryMerchandising,
            message: 'Smart Category Merchandising',
        );
        $this->assertTrue(
            condition: $accountFeatures->preserveLayout,
            message: 'Preserve Layout',
        );
        $this->assertTrue(
            condition: $accountFeatures->smartRecommendations,
            message: 'Smart Recommendations',
        );
    }

    public function testExecute_SavesDataToCache_ForWebsite_CacheEmpty(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get(key: 'test_store');
        $this->clearCache();

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

        $cacheAccountAction = $this->instantiateCacheAccountActionAction();
        $cacheAccountAction->execute(
            accountFeatures: $account->getAccountFeatures(),
            scopeId: $store->getWebsiteId(),
            scopeType: ScopeInterface::SCOPE_WEBSITES,
        );

        $cachedAccountProvider = $this->objectManager->get(CachedAccountProviderInterface::class);
        $accountFeatures = $cachedAccountProvider->get(
            scopeId: $store->getWebsiteId(),
            scopeType: ScopeInterface::SCOPE_WEBSITES,
        );

        $this->assertTrue(
            condition: $accountFeatures->smartCategoryMerchandising,
            message: 'Smart Category Merchandising',
        );
        $this->assertTrue(
            condition: $accountFeatures->preserveLayout,
            message: 'Preserve Layout',
        );
        $this->assertTrue(
            condition: $accountFeatures->smartRecommendations,
            message: 'Smart Recommendations',
        );
    }

    public function testExecute_DoesNotSaveMultipleVersionsOfAccount_ForStore(): void
    {
        $this->createStore();
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

        $cacheAccountAction = $this->instantiateCacheAccountActionAction();
        $cacheAccountAction->execute(accountFeatures: $account->getAccountFeatures(), scopeId: $store->getId());

        $jsApiKey = 'klevu-0987654321';
        $restAuthKey = $this->generateAuthKey(length: 10);
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
            'accountFeatures' => [
                'smartCategoryMerchandising' => false,
                'smartRecommendations' => false,
                'preserveLayout' => false,
            ],
        ];
        $account = $this->createAccount($accountData);

        $cacheAccountAction = $this->instantiateCacheAccountActionAction();
        $cacheAccountAction->execute(accountFeatures: $account->getAccountFeatures(), scopeId: (int)$store->getId());

        $accountProvider = $this->objectManager->get(CachedAccountProviderInterface::class);
        $accountFeatures = $accountProvider->get(scopeId: (int)$store->getId());

        $this->assertFalse(
            condition: $accountFeatures->smartCategoryMerchandising,
            message: 'Smart Category Merchandising',
        );
        $this->assertFalse(
            condition: $accountFeatures->preserveLayout,
            message: 'Preserve Layout',
        );
        $this->assertFalse(
            condition: $accountFeatures->smartRecommendations,
            message: 'Smart Recommendations',
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
     * @return CacheAccountAction
     */
    private function instantiateCacheAccountActionAction(?array $arguments = []): CacheAccountAction
    {
        return $this->objectManager->create(
            type: CacheAccountAction::class,
            arguments: $arguments,
        );
    }
}
