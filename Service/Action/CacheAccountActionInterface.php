<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action;

use Klevu\Configuration\Exception\AccountCacheScopeException;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

interface CacheAccountActionInterface
{
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
    ): void;
}
