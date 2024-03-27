<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Klevu\Configuration\Exception\AccountCacheScopeException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

interface AccountCacheKeyProviderInterface
{
    /**
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return string
     * @throws AccountCacheScopeException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function get(int $scopeId, string $scopeType = ScopeInterface::SCOPE_STORES): string;
}
