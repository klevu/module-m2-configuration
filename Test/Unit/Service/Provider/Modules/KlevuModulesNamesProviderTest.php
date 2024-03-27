<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Configuration\Test\Unit\Service\Provider\Modules;

use Klevu\Configuration\Service\Provider\Modules\KlevuModuleListProvider;
use Magento\Framework\Module\ModuleListInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers KlevuModuleListProvider
 */
class KlevuModulesNamesProviderTest extends TestCase
{
    public function testGetReturnsKlevuModuleNames(): void
    {
        $moduleName = 'Klevu_Configuration';
        $mockModuleList = $this->getMockBuilder(ModuleListInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockModuleList->expects($this->once())
            ->method('getNames')
            ->willReturn([
                $moduleName,
                'Acme_TestModule',
            ]);

        $moduleVersions = new KlevuModuleListProvider($mockModuleList);
        $expectedResult = [
            $moduleName,
        ];
        $actualResult = $moduleVersions->get();
        $this->assertSame($expectedResult, $actualResult);
    }

    public function testGetReturnsEmptyArray_WhenNoKlevuModulesPresent(): void
    {
        $mockModuleList = $this->getMockBuilder(ModuleListInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockModuleList->expects($this->once())
            ->method('getNames')
            ->willReturn([
                'Acme_TestModule',
            ]);

        $moduleVersions = new KlevuModuleListProvider($mockModuleList);
        $expectedResult = [];
        $actualResult = $moduleVersions->get();
        $this->assertSame($expectedResult, $actualResult);
    }
}
