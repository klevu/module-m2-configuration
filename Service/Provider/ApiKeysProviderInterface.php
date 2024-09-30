<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

interface ApiKeysProviderInterface
{
    /**
     * @param int[]|null $storeIds
     *
     * @return string[]
     */
    public function get(?array $storeIds = null): array;
}
