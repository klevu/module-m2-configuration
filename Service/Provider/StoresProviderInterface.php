<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Magento\Store\Api\Data\StoreInterface;

interface StoresProviderInterface
{
    /**
     * @param string $apiKey
     *
     * @return StoreInterface[]
     */
    public function get(string $apiKey): array;
}
