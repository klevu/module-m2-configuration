<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Unit\Service\Provider\Sdk\UserAgent\SystemInformation;

use Composer\InstalledVersions; // phpcs:ignore SlevomatCodingStandard.Namespaces.UseOnlyWhitelistedNamespaces.NonFullyQualified, Generic.Files.LineLength.TooLong
use Klevu\Configuration\Service\Provider\Sdk\UserAgent\SystemInformation\MagentoInventoryProvider;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;
use PHPUnit\Framework\TestCase;

class MagentoInventoryProviderTest extends TestCase
{
    public function testIsInstanceOfInterface(): void
    {
        $magentoInventoryProvider = new MagentoInventoryProvider();

        $this->assertInstanceOf(
            expected: UserAgentProviderInterface::class,
            actual: $magentoInventoryProvider,
        );
    }

    public function testExecute(): void
    {
        if (!InstalledVersions::isInstalled('magento/module-inventory')) {
            $this->markTestSkipped('MSI Module not installed');
        }

        $magentoInventoryProvider = new MagentoInventoryProvider();

        $result = $magentoInventoryProvider->execute();

        $this->assertStringContainsString(
            needle: 'magento-inventory/' . $this->getLibraryVersion(),
            haystack: $result,
        );
    }

    /**
     * @return string
     */
    private function getLibraryVersion(): string
    {
        $composerFilename = BP . '/vendor/magento/module-inventory/composer.json';
        $composerContent = json_decode(
            json: file_get_contents($composerFilename) ?: '{}',
            associative: true,
        );
        if (!is_array($composerContent)) {
            $composerContent = [];
        }

        $version = $composerContent['version'] ?? '-';
        $versionParts = explode('.', $version) + array_fill(0, 4, '0');

        return implode('.', $versionParts);
    }
}
