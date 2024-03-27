<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Configuration\Test\Integration\ViewModel\Config\Integration;

use Klevu\Configuration\ViewModel\Config\FieldsetInterface;
use Klevu\Configuration\ViewModel\Config\Integration\StoreList;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\ViewModel\Config\Integration\StoreList
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 */
class StoreListTest extends TestCase
{
    use StoreTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeFixturesPool = $this->objectManager->create(StoreFixturesPool::class);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->storeFixturesPool->rollback();
    }

    public function testImplements_AuthKeysProviderInterface(): void
    {
        $viewModel = $this->instantiateViewModel();

        $this->assertInstanceOf(FieldsetInterface::class, $viewModel);
    }

    /**
     * @param mixed[]|null $params
     *
     * @return StoreList
     */
    private function instantiateViewModel(?array $params = []): StoreList
    {
        return $this->objectManager->create(StoreList::class, $params);
    }
}
