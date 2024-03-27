<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Configuration\Test\Integration\Service\Provider\Modules;

use Klevu\Configuration\Service\Provider\Modules\KlevuModuleListProvider;
use Klevu\Configuration\Service\Provider\Modules\KlevuModuleListProviderInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\Provider\Modules\KlevuModuleListProvider
 */
class KlevuModuleListProviderTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    public function testImplementsGetKlevuModulesInterface(): void
    {
        $moduleListProvider = $this->objectManager->create(KlevuModuleListProvider::class);
        $this->assertInstanceOf(KlevuModuleListProviderInterface::class, $moduleListProvider);
    }

    public function testGet_ReturnsArray_WithModuleNameAsKey(): void
    {
        $moduleListProvider = $this->objectManager->create(KlevuModuleListProviderInterface::class);
        $klevuModules = $moduleListProvider->get();
        $this->assertIsArray($klevuModules);
        $this->assertContains('Klevu_Configuration', $klevuModules);
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
    }
}
