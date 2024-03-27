<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider\Modules;

use Magento\Framework\Module\ModuleListInterface;

class KlevuModuleListProvider implements KlevuModuleListProviderInterface
{
    private const KLEVU_MODULE_IDENTIFIER = 'Klevu_';

    /**
     * @var ModuleListInterface
     */
    private readonly ModuleListInterface $moduleList;

    /**
     * @param ModuleListInterface $moduleList
     */
    public function __construct(ModuleListInterface $moduleList)
    {
        $this->moduleList = $moduleList;
    }

    /**
     * @return string[]
     */
    public function get(): array
    {
        $allModules = $this->moduleList->getNames();

        $moduleList = array_filter($allModules, static function (string $module): bool {
            return str_starts_with($module, self::KLEVU_MODULE_IDENTIFIER);
        });
        ksort($moduleList);

        return $moduleList;
    }
}
