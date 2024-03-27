<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Provider;

use Klevu\Configuration\Cache\Type\Integration;
use Klevu\Configuration\Exception\AccountCacheScopeException;
use Klevu\Configuration\Service\Provider\AccountCacheKeyProvider;
use Klevu\Configuration\Service\Provider\AccountCacheKeyProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\Provider\AccountCacheKeyProvider
 */
class AccountCacheKeyProviderTest extends TestCase
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
            expected: AccountCacheKeyProviderInterface::class,
            actual: $this->instantiateAccountCacheKeyProvider(),
        );
    }

    public function testPreference_ForAccountFeaturesProviderInterface(): void
    {
        $this->assertInstanceOf(
            expected: AccountCacheKeyProvider::class,
            actual: $this->objectManager->create(AccountCacheKeyProviderInterface::class),
        );
    }

    /**
     * @dataProvider testGet_ThrowsException_InvalidScopeType_dataProvider
     */
    public function testGet_ThrowsException_InvalidScopeType(mixed $invalidScopeType): void
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

        $provider = $this->instantiateAccountCacheKeyProvider();
        $provider->get(scopeId: 1, scopeType: $invalidScopeType);
    }

    /**
     * @return mixed[][]
     */
    public function testGet_ThrowsException_InvalidScopeType_dataProvider(): array
    {
        return [
            [ScopeInterface::SCOPE_GROUPS],
            [ScopeInterface::SCOPE_GROUP],
            [ScopeConfigInterface::SCOPE_TYPE_DEFAULT],
            ['global'],
        ];
    }

    public function testGet_ThrowsException_IfRequestedStoreDoesNotExist(): void
    {
        $this->expectException(NoSuchEntityException::class);

        $provider = $this->instantiateAccountCacheKeyProvider();
        $provider->get(scopeId: 29857249857648967);
    }

    public function testGet_ThrowsException_IfRequestedWebsiteDoesNotExist(): void
    {
        $this->expectException(NoSuchEntityException::class);

        $provider = $this->instantiateAccountCacheKeyProvider();
        $provider->get(scopeId: 29857249857648967, scopeType: ScopeInterface::SCOPE_WEBSITES);
    }

    public function testGet_ReturnsWebsite_InWebsiteScope(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get(key: 'test_website');

        $provider = $this->instantiateAccountCacheKeyProvider();
        $cacheKey = $provider->get(scopeId: (int)$website->getId(), scopeType: ScopeInterface::SCOPE_WEBSITES);
        $this->assertSame(
            expected: Integration::TYPE_IDENTIFIER . '_website_' . $website->getId(),
            actual: $cacheKey,
        );
    }

    public function testGet_ReturnsWebsiteAndStore_InStoreScope(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get(key: 'test_website');
        $this->createStore([
            'website_id' => $website->getId(),
        ]);
        $store = $this->storeFixturesPool->get(key: 'test_store');

        $provider = $this->instantiateAccountCacheKeyProvider();
        $cacheKey = $provider->get(scopeId: (int)$store->getId());
        $this->assertSame(
            expected: Integration::TYPE_IDENTIFIER . '_website_' . $website->getId() . '_store_' . $store->getId(),
            actual: $cacheKey,
        );
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return AccountCacheKeyProvider
     */
    private function instantiateAccountCacheKeyProvider(?array $arguments = []): AccountCacheKeyProvider
    {
        return $this->objectManager->create(
            type: AccountCacheKeyProvider::class,
            arguments: $arguments,
        );
    }
}
