<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Configuration\Test\Integration\ViewModel\Config\Integration;

use Klevu\Configuration\ViewModel\Config\FieldsetInterface;
use Klevu\Configuration\ViewModel\Config\Integration\StoreList;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers StoreList
 * @method StoreList instantiateTestObject(?array $arguments = null)
 * @method StoreList instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 */
class StoreListTest extends TestCase
{
    use ObjectInstantiationTrait;
    use TestImplementsInterfaceTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->implementationFqcn = StoreList::class;
        $this->interfaceFqcn = FieldsetInterface::class;
    }

    public function testGetMessages_ReturnsInfoMessage_WhenNotSingleStoreMode(): void
    {
        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 0,
        );

        $viewModel = $this->instantiateTestObject();
        $result = $viewModel->getMessages();

        $this->assertArrayHasKey(key: 'info', array: $result);
        $this->assertCount(expectedCount: 1, haystack: $result['info']);
        $messagePhrase = array_shift($result['info']);
        $this->assertSame(
            expected: 'Note: An integration at Store Scope will override an integration at Website Scope.',
            actual: $messagePhrase->render(),
        );
    }

    public function testGetMessages_ReturnsEmptyArray_WhenSingleStoreModeEnabled(): void
    {
        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 1,
        );

        $viewModel = $this->instantiateTestObject();
        $result = $viewModel->getMessages();

        $this->assertArrayNotHasKey(key: 'info', array: $result);
        $this->assertCount(expectedCount: 0, haystack: $result);
    }
}
