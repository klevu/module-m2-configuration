<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Unit\Service\Provider\Sdk\UserAgent;

use Composer\InstalledVersions; // phpcs:ignore SlevomatCodingStandard.Namespaces.UseOnlyWhitelistedNamespaces.NonFullyQualified, Generic.Files.LineLength.TooLong
use Klevu\Configuration\Service\Provider\Sdk\UserAgent\PlatformUserAgentProvider;
use Klevu\PhpSDK\Provider\ComposableUserAgentProviderInterface;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlatformUserAgentProviderTest extends TestCase
{
    public function testIsInstanceOfInterface(): void
    {
        $platformUserAgentProvider = new PlatformUserAgentProvider();

        $this->assertInstanceOf(
            expected: ComposableUserAgentProviderInterface::class,
            actual: $platformUserAgentProvider,
        );
    }

    public function testExecute_ComposerInstall_WithoutSystemInformation(): void
    {
        if (!InstalledVersions::isInstalled('klevu/module-m2-search')) {
            $this->markTestSkipped('Module not installed by composer');
        }

        $platformUserAgentProvider = new PlatformUserAgentProvider();

        $result = $platformUserAgentProvider->execute();

        $this->assertSame(
            expected: 'klevu-m2-search/' . $this->getLibraryVersion(),
            actual: $result,
        );
    }

    public function testExecute_ComposerInstall_WithSystemInformation(): void
    {
        if (!InstalledVersions::isInstalled('klevu/module-m2-search')) {
            $this->markTestSkipped('Module not installed by composer');
        }

        $platformUserAgentProvider = new PlatformUserAgentProvider();

        $result = $platformUserAgentProvider->execute();

        $this->assertSame(
            expected: 'klevu-m2-search/' . $this->getLibraryVersion(),
            actual: $result,
        );
    }

    public function testExecute_AppInstall_WithoutSystemInformation(): void
    {
        if (InstalledVersions::isInstalled('klevu/module-m2-search')) {
            $this->markTestSkipped('Module installed by composer');
        }

        $platformUserAgentProvider = new PlatformUserAgentProvider();

        $result = $platformUserAgentProvider->execute();

        $this->assertSame(
            expected: 'klevu-m2-search',
            actual: $result,
        );
    }

    public function testExecute_AppInstall_WithSystemInformation(): void
    {
        if (InstalledVersions::isInstalled('klevu/module-m2-search')) {
            $this->markTestSkipped('Module installed by composer');
        }

        $platformUserAgentProvider = new PlatformUserAgentProvider(
            systemInformationProviders: [
                $this->getMockSystemInformationProvider('foo/1.2.3'),
                $this->getMockSystemInformationProvider('bar/42.0-beta'),
            ],
        );

        $result = $platformUserAgentProvider->execute();

        $this->assertSame(
            expected: sprintf(
                '%s (%s; %s)',
                'klevu-m2-search',
                'foo/1.2.3',
                'bar/42.0-beta',
            ),
            actual: $result,
        );
    }

    /**
     * @return string
     */
    private function getLibraryVersion(): string
    {
        $composerFilename = __DIR__ . '/../../../../../../composer.json';
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

    private function getMockSystemInformationProvider(string $systemInformation): UserAgentProviderInterface&MockObject
    {
        $systemInformationProvider = $this->getMockBuilder(UserAgentProviderInterface::class)
            ->getMock();
        $systemInformationProvider->method('execute')
            ->willReturn($systemInformation);

        return $systemInformationProvider;
    }
}
