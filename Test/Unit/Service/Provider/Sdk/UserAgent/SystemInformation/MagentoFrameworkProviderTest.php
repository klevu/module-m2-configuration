<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Unit\Service\Provider\Sdk\UserAgent\SystemInformation;

use Klevu\Configuration\Service\Provider\Sdk\UserAgent\SystemInformation\MagentoFrameworkProvider;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;
use PHPUnit\Framework\TestCase;

class MagentoFrameworkProviderTest extends TestCase
{
    public function testIsInstanceOfInterface(): void
    {
        $magentoFrameworkProvider = new MagentoFrameworkProvider();

        $this->assertInstanceOf(
            expected: UserAgentProviderInterface::class,
            actual: $magentoFrameworkProvider,
        );
    }

    public function testExecute(): void
    {
        $magentoFrameworkProvider = new MagentoFrameworkProvider();

        $result = $magentoFrameworkProvider->execute();

        $this->assertStringContainsString(
            needle: 'magento-framework/' . $this->getLibraryVersion(),
            haystack: $result,
        );
    }

    /**
     * @return string
     */
    private function getLibraryVersion(): string
    {
        $composerFilename = BP . '/vendor/magento/framework/composer.json';
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
