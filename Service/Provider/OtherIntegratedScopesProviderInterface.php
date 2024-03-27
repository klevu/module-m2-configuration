<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

interface OtherIntegratedScopesProviderInterface
{
    /**
     * @param string $apiKey
     * @param string $authKey
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return string[]
     */
    public function get(string $apiKey, string $authKey, int $scopeId, string $scopeType): array;
}
