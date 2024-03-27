<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\ViewModel\Config\Information;

use Klevu\Configuration\Service\Provider\Modules\KlevuModuleListProviderInterface;
use Klevu\Configuration\Service\Provider\Modules\VersionProviderInterface;
use Magento\Framework\Phrase;

class ModuleVersions implements ModuleVersionsInterface
{
    /**
     * @var KlevuModuleListProviderInterface
     */
    private readonly KlevuModuleListProviderInterface $klevuModuleListProvider;
    /**
     * @var VersionProviderInterface
     */
    private readonly VersionProviderInterface $versionProvider;
    /**
     * @var string[]|null
     */
    private ?array $moduleVersions = null;

    /**
     * @param KlevuModuleListProviderInterface $klevuModuleListProvider
     * @param VersionProviderInterface $versionProvider
     */
    public function __construct(
        KlevuModuleListProviderInterface $klevuModuleListProvider,
        VersionProviderInterface $versionProvider,
    ) {
        $this->klevuModuleListProvider = $klevuModuleListProvider;
        $this->versionProvider = $versionProvider;
    }

    /**
     * @return string[]
     */
    public function getChildBlocks(): array
    {
        return [];
    }

    /**
     * {@inherit-doc}
     *
     * @return Phrase[][]
     */
    public function getMessages(): array
    {
        if ($this->hasVersions()) {
            return [];
        }

        return [
            'warning' => [
                __('Could not retrieve list of modules. Please check using CLI.'),
            ],
        ];
    }

    /**
     * @return string
     */
    public function getStyles(): string
    {
        return '';
    }

    /**
     * @return bool
     */
    public function hasVersions(): bool
    {
        return (bool)$this->getVersions();
    }

    /**
     * @return string[]|null
     */
    public function getVersions(): ?array
    {
        if (null !== $this->moduleVersions) {
            return $this->moduleVersions;
        }
        $klevuModules = $this->klevuModuleListProvider->get();
        foreach ($klevuModules as $moduleName) {
            $this->moduleVersions[$moduleName] = $this->versionProvider->get($moduleName);
        }
        ksort($this->moduleVersions);

        return $this->moduleVersions;
    }
}
