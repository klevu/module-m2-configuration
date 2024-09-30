<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider\Stores\Config;

use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigCollection;

interface OldAuthKeysCollectionProviderInterface
{
    /**
     * @param string[] $filter ['scope' => 'Some scope', 'scope_id' => 'some id']
     * @param bool $load
     *
     * @return ConfigCollection
     */
    public function get(array $filter = [], bool $load = true): ConfigCollection;
}
