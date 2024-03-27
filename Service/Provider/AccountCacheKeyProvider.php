<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Klevu\Configuration\Cache\Type\Integration;
use Klevu\Configuration\Exception\AccountCacheScopeException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class AccountCacheKeyProvider implements AccountCacheKeyProviderInterface
{
    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager,
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return string
     * @throws AccountCacheScopeException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function get(int $scopeId, string $scopeType = ScopeInterface::SCOPE_STORES): string
    {
        switch ($scopeType) {
            case ScopeInterface::SCOPE_WEBSITE:
            case ScopeInterface::SCOPE_WEBSITES:
                $storeId = null;
                $website = $this->storeManager->getWebsite($scopeId);
                $websiteId = $website->getId();
                break;
            case ScopeInterface::SCOPE_STORE:
            case ScopeInterface::SCOPE_STORES:
                $store = $this->storeManager->getStore($scopeId);
                $storeId = $store->getId();
                $websiteId = $store->getWebsiteId();
                break;
            default:
                throw new AccountCacheScopeException(
                    __(
                        'Incorrect Scope Provided (%1). Must be one of %2',
                        $scopeType,
                        implode(', ', [
                            ScopeInterface::SCOPE_WEBSITE,
                            ScopeInterface::SCOPE_WEBSITES,
                            ScopeInterface::SCOPE_STORE,
                            ScopeInterface::SCOPE_STORES,
                        ]),
                    ),
                );
        }

        $return = Integration::TYPE_IDENTIFIER . '_website_' . $websiteId;
        if (null !== $storeId) {
            $return .= '_store_' . $storeId;
        }

        return $return;
    }
}
