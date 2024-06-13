<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Ui\Component\Listing\Integration\Column;

use Klevu\Configuration\Ui\Component\Listing\Integration\Column\Store;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers Store
 * @method Store instantiateTestObject(?array $arguments = null)
 * @method Store instantiateTestObjectFromInterface(?array $arguments = null)
 */
class StoreTest extends TestCase
{
    use ObjectInstantiationTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = Store::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testPrepare_EnablesWebsiteColumn_WhenSingleStoreModeNotSet(): void
    {
        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 0,
        );

        $component = $this->instantiateTestObject();
        $component->prepare();

        $config = $component->getData('config');
        $this->assertArrayHasKey(key: 'componentDisabled', array: $config);
        $this->assertFalse(condition: $config['componentDisabled']);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testPrepare_DisablesWebsiteColumn_WhenInSingleStoreMode(): void
    {
        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 1,
        );

        $component = $this->instantiateTestObject();
        $component->prepare();

        $config = $component->getData('config');
        $this->assertArrayHasKey(key: 'componentDisabled', array: $config);
        $this->assertTrue(condition: $config['componentDisabled']);
    }
}
