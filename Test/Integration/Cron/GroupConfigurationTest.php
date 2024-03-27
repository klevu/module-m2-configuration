<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Configuration\Test\Integration\Cron;

use Magento\Cron\Model\Groups\Config\Reader\Xml as XmlConfigReader;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager as TestObjectManager;
use PHPUnit\Framework\TestCase;

class GroupConfigurationTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    public function testCronGroupExists(): void
    {
        $xmlConfigReader = $this->objectManager->create(XmlConfigReader::class);
        $cronGroups = $xmlConfigReader->read();

        $this->assertIsArray($cronGroups);
        $this->assertArrayHasKey('klevu', $cronGroups);
        $this->assertIsArray($cronGroups['klevu']);
        $klevuCronGroup = $cronGroups['klevu'];

        $this->assertArrayHasKey('history_cleanup_every', $klevuCronGroup);
        $this->assertIsArray($klevuCronGroup['history_cleanup_every']);
        $this->assertArrayHasKey('value', $klevuCronGroup['history_cleanup_every']);
        $this->assertSame("10", $klevuCronGroup['history_cleanup_every']['value']);

        $this->assertArrayHasKey('history_failure_lifetime', $klevuCronGroup);
        $this->assertIsArray($klevuCronGroup['history_failure_lifetime']);
        $this->assertArrayHasKey('value', $klevuCronGroup['history_failure_lifetime']);
        $this->assertSame("4320", $klevuCronGroup['history_failure_lifetime']['value']);

        $this->assertArrayHasKey('history_success_lifetime', $klevuCronGroup);
        $this->assertIsArray($klevuCronGroup['history_success_lifetime']);
        $this->assertArrayHasKey('value', $klevuCronGroup['history_success_lifetime']);
        $this->assertSame("60", $klevuCronGroup['history_success_lifetime']['value']);

        $this->assertArrayHasKey('schedule_ahead_for', $klevuCronGroup);
        $this->assertIsArray($klevuCronGroup['schedule_ahead_for']);
        $this->assertArrayHasKey('value', $klevuCronGroup['schedule_ahead_for']);
        $this->assertSame("20", $klevuCronGroup['schedule_ahead_for']['value']);

        $this->assertArrayHasKey('schedule_generate_every', $klevuCronGroup);
        $this->assertIsArray($klevuCronGroup['schedule_generate_every']);
        $this->assertArrayHasKey('value', $klevuCronGroup['schedule_generate_every']);
        $this->assertSame("15", $klevuCronGroup['schedule_generate_every']['value']);

        $this->assertArrayHasKey('schedule_lifetime', $klevuCronGroup);
        $this->assertIsArray($klevuCronGroup['schedule_lifetime']);
        $this->assertArrayHasKey('value', $klevuCronGroup['schedule_lifetime']);
        $this->assertSame("15", $klevuCronGroup['schedule_lifetime']['value']);

        $this->assertArrayHasKey('use_separate_process', $klevuCronGroup);
        $this->assertIsArray($klevuCronGroup['use_separate_process']);
        $this->assertArrayHasKey('value', $klevuCronGroup['use_separate_process']);
        $this->assertSame("1", $klevuCronGroup['use_separate_process']['value']);
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = TestObjectManager::getInstance();
    }
}
