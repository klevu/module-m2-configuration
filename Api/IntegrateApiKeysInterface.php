<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Api;

use Klevu\Configuration\Api\Data\ApiResponseInterface;
use Magento\Store\Model\ScopeInterface;

interface IntegrateApiKeysInterface
{
    /**
     * @param string $apiKey
     * @param string $authKey
     * @param int $scopeId
     * @param string|null $scopeType
     * @param int|null $loggerScopeId
     *
     * @return \Klevu\Configuration\Api\Data\ApiResponseInterface
     */
    public function execute(
        string $apiKey,
        string $authKey,
        int $scopeId,
        ?string $scopeType = ScopeInterface::SCOPE_STORES,
        ?int $loggerScopeId = null,
    ): ApiResponseInterface;
}
