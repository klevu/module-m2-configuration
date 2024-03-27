<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Unit\ViewModel\Config\Information;

use Klevu\Configuration\Service\Provider\Modules\KlevuModuleListProviderInterface;
use Klevu\Configuration\Service\Provider\Modules\VersionProviderInterface;
use Klevu\Configuration\ViewModel\Config\Information\ModuleVersions;
use PHPUnit\Framework\TestCase;

/**
 * @covers ModuleVersions
 */
class ModuleVersionsTest extends TestCase
{
    public function testGetVersions(): void
    {
        $mockModuleProvider = $this->getMockBuilder(KlevuModuleListProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockModuleProvider->expects($this->once())
            ->method('get')
            ->willReturn([
                'Klevu_Configuration',
            ]);
        $mockVersionProvider = $this->getMockBuilder(VersionProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockVersionProvider->expects($this->once())
            ->method('get')
            ->with('Klevu_Configuration')
            ->willReturn('0.1.0');

        $moduleVersions = new ModuleVersions(
            $mockModuleProvider,
            $mockVersionProvider,
        );
        $modules = $moduleVersions->getVersions();

        $this->assertIsArray($modules);
        $this->assertCount(1, $modules);
        $this->assertArrayHasKey('Klevu_Configuration', $modules);
        $this->assertSame('0.1.0', $modules['Klevu_Configuration']);
    }

    public function testGetVersions_WhenNoKlevuModulesEnabled(): void
    {
        $mockModuleProvider = $this->getMockBuilder(KlevuModuleListProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockModuleProvider->expects($this->once())
            ->method('get')
            ->willReturn([]);
        $mockVersionProvider = $this->getMockBuilder(VersionProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockVersionProvider->expects($this->never())
            ->method('get');

        $moduleVersions = new ModuleVersions(
            $mockModuleProvider,
            $mockVersionProvider,
        );
        $modules = $moduleVersions->getVersions();

        $this->assertNull($modules);
    }
}
