<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Klevu\Configuration\Exception\AccountCacheScopeException;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Klevu\PhpSDK\Model\Account\AccountFeaturesFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @see \Klevu\Configuration\Service\Action\CacheAccountAction::execute to cache data
 */
class CachedAccountProvider implements CachedAccountProviderInterface
{
    /**
     * @var CacheInterface
     */
    private readonly CacheInterface $cache;
    /**
     * @var SerializerInterface
     */
    private readonly SerializerInterface $serializer;
    /**
     * @var AccountFeaturesFactory
     */
    private readonly AccountFeaturesFactory $accountFeaturesFactory;
    /**
     * @var AccountCacheKeyProviderInterface
     */
    private readonly AccountCacheKeyProviderInterface $accountCacheKeyProvider;

    /**
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param AccountFeaturesFactory $accountFeaturesFactory
     * @param AccountCacheKeyProviderInterface $accountCacheKeyProvider
     */
    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer,
        AccountFeaturesFactory $accountFeaturesFactory,
        AccountCacheKeyProviderInterface $accountCacheKeyProvider,
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->accountFeaturesFactory = $accountFeaturesFactory;
        $this->accountCacheKeyProvider = $accountCacheKeyProvider;
    }

    /**
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return AccountFeatures|null
     * @throws AccountCacheScopeException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function get(int $scopeId, string $scopeType = ScopeInterface::SCOPE_STORES): ?AccountFeatures
    {
        $cacheKey = $this->accountCacheKeyProvider->get(
            scopeId: $scopeId,
            scopeType: $scopeType,
        );
        $cachedData = $this->getCachedData($cacheKey);

        return $cachedData
            ? $this->createAccountFeaturesObjectFromCache($cachedData)
            : null;
    }

    /**
     * @param string $cacheKey
     *
     * @return string|null
     */
    private function getCachedData(string $cacheKey): ?string
    {
        $cache = $this->cache->load(identifier: $cacheKey);
        if (!$cache && str_contains(haystack: $cacheKey, needle: '_store_')) {
            $cacheArray = explode('_store_', $cacheKey);
            $cacheKey = $cacheArray[0];
            $cache = $this->cache->load(identifier: $cacheKey);
        }

        return $cache
            ? (string)$cache
            : null;
    }

    /**
     * @param string $cachedData
     *
     * @return AccountFeatures
     */
    private function createAccountFeaturesObjectFromCache(string $cachedData): AccountFeatures
    {
        $data = $this->serializer->unserialize($cachedData);

        return $this->accountFeaturesFactory->create(
            data: $data['accountFeatures'] ?? [],
        );
    }
}
