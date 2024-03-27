<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Magento\Store\Api\Data\StoreInterface;

interface StoreLocaleCodesProviderInterface
{
    /**
     * @param string $apiKey
     *
     * @return string[]
     */
    public function get(string $apiKey): array;

    /**
     * @param StoreInterface $store
     *
     * @return string
     */
    public function getByStore(StoreInterface $store): string;
}
