<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Plugin\Stores\Config;

use Klevu\Configuration\Plugin\Stores\Config\RemoveSaveButton;
use Klevu\TestFixtures\Traits\SetAreaTrait;
use Magento\Framework\App\Area;
use Magento\Framework\Interception\PluginList\PluginList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 * @runTestsInSeparateProcesses
 */
class RemoveSaveButtonPluginTest extends TestCase
{
    use SetAreaTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;
    /**
     * @var string|null
     */
    private ?string $pluginName = 'Klevu_Configuration::RemoveStoresConfigSaveButton';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoAppArea global
     */
    public function testPlugin_DoesNotInterceptsCallsToTheField_InGlobalScope(): void
    {
        $pluginInfo = $this->getSystemConfigPluginInfo();
        $this->assertArrayNotHasKey($this->pluginName, $pluginInfo);
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testPlugin_InterceptsCallsToTheField_InAdminScope(): void
    {
        $this->setArea(Area::AREA_ADMINHTML);

        $pluginInfo = $this->getSystemConfigPluginInfo();
        $this->assertArrayHasKey($this->pluginName, $pluginInfo);
        $this->assertSame(RemoveSaveButton::class, $pluginInfo[$this->pluginName]['instance']);
    }

    /**
     * @return mixed[]|null
     */
    private function getSystemConfigPluginInfo(): ?array
    {
        /** @var PluginList $pluginList */
        $pluginList = $this->objectManager->get(PluginList::class);

        return $pluginList->get(AbstractBlock::class, []);
    }
}
