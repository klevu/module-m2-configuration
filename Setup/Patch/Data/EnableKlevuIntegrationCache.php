<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Setup\Patch\Data;

use Klevu\Configuration\Cache\Type\Integration;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class EnableKlevuIntegrationCache implements DataPatchInterface
{
    /**
     * @var TypeListInterface
     */
    private readonly TypeListInterface $typeList;
    /**
     * @var StateInterface
     */
    private readonly StateInterface $state;

    /**
     * @param TypeListInterface $typeList
     * @param StateInterface $state
     */
    public function __construct(
        TypeListInterface $typeList,
        StateInterface $state,
    ) {
        $this->typeList = $typeList;
        $this->state = $state;
    }

    /**
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return EnableKlevuIntegrationCache
     */
    public function apply(): EnableKlevuIntegrationCache
    {
        if (!array_key_exists(key: Integration::TYPE_IDENTIFIER, array: $this->typeList->getTypes())) {
            return $this;
        }
        if (!$this->state->isEnabled(cacheType: Integration::TYPE_IDENTIFIER)) {
            $this->state->setEnabled(cacheType: Integration::TYPE_IDENTIFIER, isEnabled: true);
        }

        return $this;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
