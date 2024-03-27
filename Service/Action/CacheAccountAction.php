<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action;

use Klevu\Configuration\Cache\Type\Integration as IntegrationCache;
use Klevu\Configuration\Exception\AccountCacheScopeException;
use Klevu\Configuration\Service\Provider\AccountCacheKeyProviderInterface;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @see \Klevu\Configuration\Service\Provider\CachedAccountProvider to retrieve cached data
 */
class CacheAccountAction implements CacheAccountActionInterface
{
    private const CACHE_LIFETIME = 14400; // 4 hours

    /**
     * @var CacheInterface
     */
    private readonly CacheInterface $cache;
    /**
     * @var SerializerInterface
     */
    private readonly SerializerInterface $serializer;
    /**
     * @var AccountCacheKeyProviderInterface
     */
    private readonly AccountCacheKeyProviderInterface $accountCacheKeyProvider;

    /**
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param AccountCacheKeyProviderInterface $accountCacheKeyProvider
     */
    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer,
        AccountCacheKeyProviderInterface $accountCacheKeyProvider,
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->accountCacheKeyProvider = $accountCacheKeyProvider;
    }

    /**
     * @param AccountFeatures $accountFeatures
     * @param int $scopeId
     * @param string|null $scopeType
     *
     * @return void
     * @throws AccountCacheScopeException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(
        AccountFeatures $accountFeatures,
        int $scopeId,
        ?string $scopeType = ScopeInterface::SCOPE_STORES,
    ): void {
        $cacheId = $this->accountCacheKeyProvider->get($scopeId, $scopeType);
        $data = $this->serializer->serialize(
            data: $this->toArray($accountFeatures),
        );

        $this->cache->save(
            data: $data,
            identifier: $cacheId,
            tags: [IntegrationCache::CACHE_TAG],
            lifeTime: self::CACHE_LIFETIME,
        );
    }

    /**
     * @param AccountFeatures $accountFeatures
     *
     * @return mixed[]
     */
    private function toArray(AccountFeatures $accountFeatures): array
    {
        return [
            'accountFeatures' => $accountFeatures,
        ];
    }
}
