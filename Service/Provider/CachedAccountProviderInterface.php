<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Magento\Store\Model\ScopeInterface;

interface CachedAccountProviderInterface
{
    /**
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return AccountFeatures|null
     */
    public function get(
        int $scopeId,
        string $scopeType = ScopeInterface::SCOPE_STORES,
    ): ?AccountFeatures;
}
