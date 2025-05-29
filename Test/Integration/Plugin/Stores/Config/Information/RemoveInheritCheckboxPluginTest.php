<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Plugin\Stores\Config\Information;

use Klevu\Configuration\Plugin\Stores\Config\Information\RemoveInheritCheckboxPlugin;
use Klevu\TestFixtures\Traits\SetAreaTrait;
use Magento\Config\Block\System\Config\Form\Field as ConfigFormField;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaInterface;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\ObjectManager\ConfigLoader;
use Magento\Framework\App\State;
use Magento\Framework\Config\Scope;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Interception\PluginList\PluginList;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Application;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 * @runTestsInSeparateProcesses
 */
class RemoveInheritCheckboxPluginTest extends TestCase
{
    use SetAreaTrait;
    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;
    /**
     * @var string|null
     */
    private ?string $pluginName = 'Klevu_Configuration::Stores_Config_Information_RemoveInheritCheckbox';

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
        $this->assertSame(RemoveInheritCheckboxPlugin::class, $pluginInfo[$this->pluginName]['instance']);
    }

    /**
     * @return mixed[]|null
     */
    private function getSystemConfigPluginInfo(): ?array
    {
        /** @var PluginList $pluginList */
        $pluginList = $this->objectManager->get(PluginList::class);

        return $pluginList->get(ConfigFormField::class, []);
    }
}
