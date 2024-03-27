<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Configuration\Test\Integration\ViewModel\Config\Information;

use Klevu\Configuration\ViewModel\Config\FieldsetInterface;
use Klevu\Configuration\ViewModel\Config\Information\ModuleVersions;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\ViewModel\Config\Information\ModuleVersions
 */
class ModuleVersionsTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    public function testImplements_AuthKeysProviderInterface(): void
    {
        $moduleVersion = $this->objectManager->get(ModuleVersions::class);

        $this->assertInstanceOf(FieldsetInterface::class, $moduleVersion);
    }

    public function testGetInstalledModules_ReturnsEnabledModules(): void
    {
        $moduleVersion = $this->objectManager->get(ModuleVersions::class);
        $modules = $moduleVersion->getVersions();
        $this->assertArrayHasKey('Klevu_Configuration', $modules);
        $this->assertGreaterThanOrEqual('0.1.0', $modules['Klevu_Configuration']);
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
    }
}
