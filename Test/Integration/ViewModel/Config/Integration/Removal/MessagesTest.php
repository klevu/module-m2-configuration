<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\ViewModel\Config\Integration\Removal;

use Klevu\Configuration\ViewModel\Config\Integration\Removal\Messages;
use Klevu\Configuration\ViewModel\MessageInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers Messages
 * @method MessageInterface instantiateTestObject(?array $arguments = null)
 * @method MessageInterface instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 */
class MessagesTest extends TestCase
{
    use ObjectInstantiationTrait;
    use TestImplementsInterfaceTrait;

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
        $this->implementationFqcn = Messages::class;
        $this->interfaceFqcn = MessageInterface::class;
    }

    public function testGetMessages_ReturnsInfoMessage_WhenNotSingleStoreMode(): void
    {
        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 0,
        );

        $scope = 'store';
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(['scope' => $scope]);

        $viewModel = $this->instantiateTestObject([]);
        $result = $viewModel->getMessages();

        $this->assertArrayHasKey(key: 'warning', array: $result);
        $this->assertCount(expectedCount: 1, haystack: $result['warning']);
        $messagePhrase = array_shift($result['warning']);
        $this->assertSame(
            expected: sprintf("Warning: This action will remove your integration at '%s' scope.", $scope),
            actual: $messagePhrase->render(),
        );
    }

    public function testGetMessages_ReturnsEmptyArray_WhenSingleStoreModeEnabled(): void
    {
        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 1,
        );
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->assertTrue(
            condition: $storeManager->isSingleStoreMode(),
            message: 'Single store mode should be enabled.',
        );

        $viewModel = $this->instantiateTestObject([]);
        $result = $viewModel->getMessages();

        $this->assertArrayNotHasKey(key: 'warning', array: $result);
        $this->assertCount(expectedCount: 0, haystack: $result);
    }
}
