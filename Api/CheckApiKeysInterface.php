<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Api;

use Klevu\Configuration\Api\Data\ApiResponseInterface;

interface CheckApiKeysInterface
{
    /**
     * @param string $apiKey
     * @param string $authKey
     * @param int|null $scopeId
     * @param string|null $scopeType
     * @param int|null $loggerScopeId
     *
     * @return \Klevu\Configuration\Api\Data\ApiResponseInterface
     */
    public function execute(
        string $apiKey,
        string $authKey,
        ?int $scopeId,
        ?string $scopeType,
        ?int $loggerScopeId = null,
    ): ApiResponseInterface;
}
