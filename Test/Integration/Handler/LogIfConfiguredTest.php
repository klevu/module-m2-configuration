<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Handler;

use Klevu\Configuration\Logger\Handler\LogIfConfigured as LogIfConfiguredVirtualType;
use Klevu\Configuration\Service\Provider\ScopeProvider;
use Klevu\Configuration\Service\Provider\StoreScopeProviderInterface;
use Klevu\Logger\Handler\LogIfConfigured;
use Klevu\Logger\Test\Integration\Traits\FileSystemTrait;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\ObjectManager;
// phpcs:ignore SlevomatCodingStandard.Namespaces.UseOnlyWhitelistedNamespaces.NonFullyQualified
use Monolog\Handler\HandlerInterface;
// phpcs:ignore SlevomatCodingStandard.Namespaces.UseOnlyWhitelistedNamespaces.NonFullyQualified
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\Configuration\Logger\Handler\LogIfConfigured
 * @phpstan-type Level Logger::DEBUG|Logger::INFO|Logger::NOTICE|Logger::WARNING|Logger::ERROR|Logger::CRITICAL|Logger::ALERT|Logger::EMERGENCY
 * @runTestsInSeparateProcesses
 */
class LogIfConfiguredTest extends TestCase
{
    use FileSystemTrait;
    use StoreTrait;
    use WebsiteTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;
    /**
     * @var StoreScopeProviderInterface|null
     */
    private ?StoreScopeProviderInterface $storeScopeProvider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->storeFixturesPool = $this->objectManager->create(StoreFixturesPool::class);
        $this->websiteFixturesPool = $this->objectManager->create(WebsiteFixturesPool::class);
        $this->storeScopeProvider = $this->objectManager->get(StoreScopeProviderInterface::class);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->storeFixturesPool->rollback();
        $this->websiteFixturesPool->rollback();
        $this->storeScopeProvider->setCurrentStoreById(Store::DEFAULT_STORE_ID);
    }

    public function testImplements_HandlerInterface(): void
    {
        $this->assertInstanceOf(
            HandlerInterface::class,
            $this->instantiateHandlerLogIfConfigured(),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_configuration/developer/log_level_configuration 500
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/log_level_configuration 200
     * @return void
     */
    public function testIsHandling_ReturnsTrue_IfLogLevelIsMoreThenMinLevel_ForStore(): void
    {
        $record = $this->getRecord(
            level: 400,
        );
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());

        $logIfConfigured = $this->instantiateHandlerLogIfConfigured();
        $this->assertTrue(
            $logIfConfigured->isHandling($record),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_configuration/developer/log_level_configuration 500
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store general/single_store_mode/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/log_level_configuration 200
     * @return void
     */
    public function testIsHandling_ReturnsTrue_IfLogLevelIsMoreThenMinLevel_ForStoreWhenSSMIsEnabled(): void
    {
        $record = $this->getRecord(
            level: 400,
        );
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());

        $logIfConfigured = $this->instantiateHandlerLogIfConfigured();
        $this->assertTrue(
            $logIfConfigured->isHandling($record),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_configuration/developer/log_level_configuration 400
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/log_level_configuration 500
     * @return void
     */
    public function testIsHandling_ReturnsTrue_IfLogLevelIsEqualToMinLevel_ForStore(): void
    {
        $record = $this->getRecord(
            level: 500,
        );
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());

        $logIfConfigured = $this->instantiateHandlerLogIfConfigured();
        $this->assertTrue(
            $logIfConfigured->isHandling($record),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_configuration/developer/log_level_configuration 400
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store general/single_store_mode/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/log_level_configuration 500
     * @return void
     */
    public function testIsHandling_ReturnsTrue_IfLogLevelIsEqualToMinLevel_ForStoreWhenSSMIsEnabled(): void
    {
        $record = $this->getRecord(
            level: 500,
        );
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());

        $logIfConfigured = $this->instantiateHandlerLogIfConfigured();
        $this->assertTrue(
            $logIfConfigured->isHandling($record),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_configuration/developer/log_level_configuration 100
     * @magentoConfigFixture klevu_test_store_1 klevu_configuration/developer/log_level_configuration 400
     * @return void
     */
    public function testIsHandling_ReturnsFalse_IfLogLevelIsLessThanMinLevel_ForStore(): void
    {
        $record = $this->getRecord(
            level: 300,
        );

        $this->createStore();
        ConfigFixture::setGlobal(
            path: 'klevu_configuration/developer/log_level_configuration',
            value: 100,
        );
        ConfigFixture::setForStore(
            path: 'klevu_configuration/developer/log_level_configuration',
            value: 400,
            storeCode: 'klevu_test_store_1',
        );

        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());

        $logIfConfigured = $this->instantiateHandlerLogIfConfigured();
        $this->assertFalse(
            $logIfConfigured->isHandling($record),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_configuration/developer/log_level_configuration 200
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store general/single_store_mode/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/log_level_configuration 400
     * @return void
     */
    public function testIsHandling_ReturnsFalse_IfLogLevelIsLessThanMinLevel_ForStoreWhenSSMIsEnabled(): void
    {
        $record = $this->getRecord(
            level: 300,
        );

        $this->createStore();
        ConfigFixture::setGlobal(
            path: 'klevu_configuration/developer/log_level_configuration',
            value: 100,
        );
        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 1,
        );

        ConfigFixture::setForStore(
            path: 'klevu_configuration/developer/log_level_configuration',
            value: 400,
            storeCode: 'klevu_test_store_1',
        );

        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());

        $logIfConfigured = $this->instantiateHandlerLogIfConfigured();
        $this->assertFalse(
            $logIfConfigured->isHandling($record),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @return void
     */
    public function testIsHandling_ReturnsTrue_IfMinLogLevelIsNotSet_ForStore(): void
    {
        $record = $this->getRecord(
            level: 300,
        );
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());
        $logIfConfigured = $this->instantiateHandlerLogIfConfigured();
        $this->assertTrue(
            $logIfConfigured->isHandling($record),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store general/single_store_mode/enabled 1
     * @return void
     */
    public function testIsHandling_ReturnsTrue_IfMinLogLevelIsNotSet_ForStoreWhenSSMIsEnabled(): void
    {
        $record = $this->getRecord(
            level: 300,
        );
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());
        $logIfConfigured = $this->instantiateHandlerLogIfConfigured();
        $this->assertTrue(
            $logIfConfigured->isHandling($record),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_configuration/developer/log_level_configuration 200
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/log_level_configuration 400
     * @dataProvider testIsHandling_ReturnsFalse_IfLogLevelIsNotValid_ForStore_DataProvider
     * @return void
     */
    public function testIsHandling_ReturnsFalseIfLogLevelIsNotValid_ForStore(mixed $logLevels): void
    {
        if (class_exists(LogRecord::class)) {
            $this->markTestSkipped(
                'This test is not applicable for LogRecord, as it does not support invalid log levels.'
            );
        }

        $record = ['level' => $logLevels];
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());

        $logIfConfigured = $this->instantiateHandlerLogIfConfigured();
        $this->assertFalse(
            $logIfConfigured->isHandling($record),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_configuration/developer/log_level_configuration 200
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store general/single_store_mode/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/log_level_configuration 400
     * @dataProvider testIsHandling_ReturnsFalse_IfLogLevelIsNotValid_ForStore_DataProvider
     * @return void
     */
    public function testIsHandling_ReturnsFalseIfLogLevelIsNotValid_ForStoreWhenSSMIsEnabled(mixed $logLevels): void
    {
        if (class_exists(LogRecord::class)) {
            $this->markTestSkipped(
                'This test is not applicable for LogRecord, as it does not support invalid log levels.'
            );
        }

        $record = ['level' => $logLevels];
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());

        $logIfConfigured = $this->instantiateHandlerLogIfConfigured();
        $this->assertFalse(
            $logIfConfigured->isHandling($record),
        );
    }

    /**
     * @return mixed[][]
     */
    public function testIsHandling_ReturnsFalse_IfLogLevelIsNotValid_ForStore_DataProvider(): array
    {
        return [
            ['level'],
            [true],
            [[12]],
            [new \stdClass()],
            [false],
            [null],
            [1.23456],
            ['3.14e-14'],
        ];
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testWrite_WriteLogFile(): void
    {
        $this->deleteAllLogs();
        $record = $this->getRecord();
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $this->createStoreLogsDirectory(null, $store->getCode());
        $directory = $this->getStoreLogsDirectoryPath(null, $store->getCode());
        $fileName = $directory . DIRECTORY_SEPARATOR . 'klevu-' . $store->getCode() . '-configuration.log';

        $this->storeScopeProvider->setCurrentStoreByCode($store->getCode());
        $logIfConfigured = $this->instantiateHandlerLogIfConfigured();
        $logIfConfigured->write($record);
        $fileIo = $this->objectManager->get(File::class);
        $this->assertTrue($fileIo->fileExists($fileName));
        $fileContents = $fileIo->read($fileName);

        $this->assertStringContainsString(
            'randomTestMessage',
            $fileContents,
        );
    }

    /**
     * @magentoAppArea crontab
     */
    public function testWrite_WriteMultipleLogFiles(): void
    {
        $this->deleteAllLogs();
        $record = $this->getRecord();

        $this->createWebsite();
        $websiteFixture = $this->websiteFixturesPool->get('test_website');
        $this->createStore([
            'website_id' => $websiteFixture->getId(),
            'key' => 'test_store_1',
        ]);
        $this->createStore([
            'website_id' => $websiteFixture->getId(),
            'code' => 'klevu_test_store_2',
            'key' => 'test_store_2',
        ]);
        $store1 = $this->storeFixturesPool->get('test_store_1');
        $store2 = $this->storeFixturesPool->get('test_store_2');
        $stores = [$store1, $store2];

        foreach ($stores as $store) {
            $this->createStoreLogsDirectory(null, $store->getCode());
        }

        $websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);
        $website = $websiteRepository->get($websiteFixture->getCode());
        $scopeProvider = $this->objectManager->get(ScopeProvider::class);
        $scopeProvider->setCurrentScope($website);
        $logIfConfigured = $this->instantiateHandlerLogIfConfigured();
        $logIfConfigured->write($record);

        $fileIo = $this->objectManager->get(File::class);

        foreach ($stores as $store) {
            $directory = $this->getStoreLogsDirectoryPath(null, $store->getCode());
            $fileName = $directory . DIRECTORY_SEPARATOR . 'klevu-' . $store->getCode() . '-configuration.log';

            $this->assertTrue(
                condition: $fileIo->fileExists($fileName),
                message: sprintf('File exists %s', $fileName),
            );
            $fileContents = $fileIo->read($fileName);

            $this->assertStringContainsString(
                needle: 'randomTestMessage',
                haystack: $fileContents,
                message: 'Store ID: ' . $store->getId(),
            );
        }
    }

    /**
     * @param mixed[]|null $params
     *
     * @return LogIfConfigured
     */
    private function instantiateHandlerLogIfConfigured(?array $params = []): LogIfConfigured
    {
        return $this->objectManager->create(
            LogIfConfiguredVirtualType::class, // @phpstan-ignore-line
            $params,
        );
    }

    /**
     * @param int $level
     * @param string $message
     * @param mixed[] $context
     *
     * @return mixed[]
     *
     * @phpstan-param Level $level
     */
    private function getRecord(
        int $level = Logger::WARNING,
        string $message = 'randomTestMessage',
        array $context = [],
    ): array|LogRecord {
        $returnData = [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => Logger::getLevelName($level),
            'channel' => 'klevu',
            'datetime' => \DateTimeImmutable::createFromFormat(
                'U.u',
                sprintf('%.6F', microtime(true)),
            ),
            'extra' => [],
        ];

        return (class_exists(LogRecord::class))
            ? new LogRecord(
                datetime: $returnData['datetime'],
                channel: $returnData['channel'],
                level: Level::fromValue($level),
                message: $returnData['message'],
                context: $returnData['context'],
                extra: $returnData['extra'],
                formatted: null,
            )
            : $returnData;
    }
}
