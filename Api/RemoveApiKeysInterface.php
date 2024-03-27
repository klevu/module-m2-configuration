<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Api;

use Klevu\Configuration\Api\Data\ApiResponseInterface;
use Magento\Store\Model\ScopeInterface;

interface RemoveApiKeysInterface
{
    /**
     * @param int $scopeId
     * @param string|null $scopeType
     *
     * @return \Klevu\Configuration\Api\Data\ApiResponseInterface
     */
    public function execute(
        int $scopeId,
        ?string $scopeType = ScopeInterface::SCOPE_STORES,
    ): ApiResponseInterface;
}
