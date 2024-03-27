<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Configuration\Test\Integration\Service\Provider\Modules;

use Klevu\Configuration\Service\Provider\Modules\VersionProvider;
use Klevu\Configuration\Service\Provider\Modules\VersionProviderInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\Provider\Modules\VersionProvider
 */
class VersionProviderTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    public function testImplementsVersionProviderInterface(): void
    {
        $moduleVersionProvider = $this->objectManager->create(VersionProvider::class);
        $this->assertInstanceOf(VersionProviderInterface::class, $moduleVersionProvider);
    }

    public function testConfigurationModuleVersionIsReturned(): void
    {
        $moduleVersionProvider = $this->objectManager->create(VersionProvider::class);
        $version = $moduleVersionProvider->get('Klevu_Configuration');
        $this->assertIsString($version);
        $this->assertGreaterThanOrEqual('0.1.0', $version);
    }

    public function testIncorrectModuleVersionReturnsUnavailable(): void
    {
        $moduleVersionProvider = $this->objectManager->create(VersionProvider::class);
        $version = $moduleVersionProvider->get('aosiuioguaioru');
        $this->assertIsString($version);
        $this->assertSame('unavailable', $version);
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
    }
}
