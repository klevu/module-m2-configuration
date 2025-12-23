<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\ViewModel\Config\Information;

use Composer\InstalledVersions as ComposerInstalledVersions;
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
     * @var array
     */
    private array $composerPackageIdentifiers = [];
    /**
     * @var string[]|null
     */
    private ?array $moduleVersions = null;

    /**
     * @param KlevuModuleListProviderInterface $klevuModuleListProvider
     * @param VersionProviderInterface $versionProvider
     * @param string[] $composerPackageIdentifiers
     */
    public function __construct(
        KlevuModuleListProviderInterface $klevuModuleListProvider,
        VersionProviderInterface $versionProvider,
        array $composerPackageIdentifiers = [],
    ) {
        $this->klevuModuleListProvider = $klevuModuleListProvider;
        $this->versionProvider = $versionProvider;
        $this->composerPackageIdentifiers = array_unique(
            array: array_map('strval', $composerPackageIdentifiers),
        );
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
        $messages = [];

        if (!$this->hasVersions()) {
            $messages[] = [
                'warning' => [
                    __('Could not retrieve list of modules. Please check using CLI.'),
                ],
            ];
        }

        if (null === $this->getLibraryVersions()) {
            $messages[] = [
                'warning' => [
                    __('Could not retrieve library versions. Composer information is not available.'),
                ],
            ];
        }

        return $messages;
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

    /**
     * @return array<string, string>|null
     */
    public function getLibraryVersions(): ?array
    {
        if (!class_exists(ComposerInstalledVersions::class)) {
            return null;
        }

        $return = [];
        foreach ($this->composerPackageIdentifiers as $packageIdentifier) {
            $return[$packageIdentifier] = ComposerInstalledVersions::isInstalled($packageIdentifier)
                ? ComposerInstalledVersions::getPrettyVersion($packageIdentifier)
                : __('Not installed');
        }

        return array_map('strval', $return);
    }
}
