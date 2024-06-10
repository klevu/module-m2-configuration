<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Ui\Component\Listing\Integration\Column;

use Klevu\Configuration\Ui\Component\Listing\Integration\Column\Website;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers Website
 * @method Website instantiateTestObject(?array $arguments = null)
 * @method Website instantiateTestObjectFromInterface(?array $arguments = null)
 */
class WebsiteTest extends TestCase
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

        $this->implementationFqcn = Website::class;
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
