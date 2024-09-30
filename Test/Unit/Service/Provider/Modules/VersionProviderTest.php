<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Unit\Service\Provider\Modules;

use Klevu\Configuration\Service\Provider\Modules\VersionProvider;
use Magento\Framework\Config\Composer\Package as ComposerPackage;
use Magento\Framework\Config\Composer\PackageFactory as ComposerPackageFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Module\Dir as ModuleDirectory;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers VersionProvider
 */
class VersionProviderTest extends TestCase
{
    /**
     * @var ModuleDirectory|(ModuleDirectory&MockObject)|MockObject
     */
    private ModuleDirectory|MockObject|null $mockModuleDirectory = null;
    /**
     * @var ComposerPackage|(ComposerPackage&MockObject)|MockObject
     */
    private ComposerPackage|MockObject|null $mockComposerPackage = null;
    /**
     * @var ComposerPackageFactory|(ComposerPackageFactory&MockObject)|MockObject
     */
    private ComposerPackageFactory|MockObject|null $mockComposerPackageFactory = null;
    /**
     * @var DriverInterface|(MockObject&DriverInterface)|MockObject
     */
    private DriverInterface|MockObject|null $mockFileSystemDriver = null;
    /**
     * @var LoggerInterface|(MockObject&LoggerInterface)|MockObject
     */
    private LoggerInterface|MockObject|null $mockLogger = null;
    /**
     * @var SerializerInterface|(SerializerInterface&MockObject)|MockObject
     */
    private SerializerInterface|MockObject|null $mockSerializer = null;

    public function testGet_ReturnsVersion(): void
    {
        $moduleName = 'Klevu_Configuration';
        $version = '0.1.0';
        $path = '/var/www/html/vendor/klevu/module-m2-configuration';

        $this->mockModuleDirectory->expects($this->once())
            ->method('getDir')
            ->with($moduleName)
            ->willReturn($path);

        $this->mockComposerPackage->expects($this->once())
            ->method('get')
            ->willReturn($version);
        $this->mockComposerPackageFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->mockComposerPackage);

        $this->mockFileSystemDriver->expects($this->once())
            ->method('fileGetContents')
            ->willReturn(
                '{"name": "klevu/module-m2-configuration","type": "magento2-module","version": "' . $version . '"}',
            );

        $this->mockSerializer->expects($this->once())
            ->method('unserialize')
            ->with('{"name": "klevu/module-m2-configuration","type": "magento2-module","version": "' . $version . '"}')
            ->willReturn([
                "name" => "klevu/module-m2-configuration",
                "type" => "magento2-module",
                "version" => $version,
            ]);

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('error');
        $this->mockLogger->expects($this->never())->method('warning');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->never())->method('log');

        $versionProvider = $this->instantiateVersionProvider();
        $actualVersion = $versionProvider->get($moduleName);
        $this->assertSame($version, $actualVersion);
    }

    public function testErrorIsLogged_IfModuleDoesNotExist(): void
    {
        $moduleName = 'Klevu_WrongName';

        $exceptionMessage = sprintf("Module '%s' is not correctly registered.", $moduleName);
        $exception = new \InvalidArgumentException($exceptionMessage);
        $this->mockModuleDirectory->expects($this->once())
            ->method('getDir')
            ->with($moduleName)
            ->willThrowException($exception);

        $this->mockComposerPackageFactory->expects($this->never())
            ->method('create');

        $this->mockFileSystemDriver->expects($this->never())
            ->method('fileGetContents');

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('warning');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->never())->method('log');

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'Method: {method} - Error: {exception}',
                [
                    'method' => 'Klevu\Configuration\Service\Provider\Modules\VersionProvider::getModulePath',
                    'exception' => $exceptionMessage,
                ],
            );

        $versionProvider = $this->instantiateVersionProvider();
        $version = $versionProvider->get($moduleName);
        $this->assertSame('unavailable', $version);
    }

    public function testErrorIsLogged_IfComposerDoesNotExist(): void
    {
        $moduleName = 'Klevu_Configuration';
        $path = '/var/www/html/vendor/klevu/module-m2-configuration/composer.json';

        $exceptionMessage = new Phrase(
            'The contents from the "%1" file can\'t be read. %2',
            [$path, 'Warning! Something went wrong'],
        );
        $exception = new FileSystemException($exceptionMessage);

        $this->mockModuleDirectory->expects($this->once())
            ->method('getDir')
            ->with($moduleName)
            ->willReturn($path);

        $this->mockComposerPackageFactory->expects($this->never())
            ->method('create');

        $this->mockFileSystemDriver->expects($this->once())
            ->method('fileGetContents')
            ->willThrowException($exception);

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('warning');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->never())->method('log');

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'Method: {method} - Error: {exception}',
                [
                    'method' => 'Klevu\Configuration\Service\Provider\Modules\VersionProvider::getComposerJson',
                    'exception' => $exceptionMessage,
                ],
            );

        $versionProvider = $this->instantiateVersionProvider();
        $version = $versionProvider->get($moduleName);
        $this->assertSame('unavailable', $version);
    }

    public function testErrorIsLogged_IfJsonDecodeFails(): void
    {
        $moduleName = 'Klevu_Configuration';
        $path = '/var/www/html/vendor/klevu/module-m2-configuration/composer.json';

        $this->mockModuleDirectory->expects($this->once())
            ->method('getDir')
            ->with($moduleName)
            ->willReturn($path);

        $this->mockComposerPackageFactory->expects($this->never())
            ->method('create');

        $this->mockFileSystemDriver->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('{["invalidJson": \}');

        $this->mockSerializer->expects($this->once())
            ->method('unserialize')
            ->with('{["invalidJson": \}')
            ->willThrowException(
                new \InvalidArgumentException("Unable to unserialize value. Error: Oops, that didn't work"),
            );

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('warning');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->never())->method('log');

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'Method: {method} - Error: {exception}',
                [
                    'method' => 'Klevu\Configuration\Service\Provider\Modules\VersionProvider::decodeJson',
                    'exception' => 'Unable to unserialize value. Error: Oops, that didn\'t work',
                ],
            );

        $versionProvider = $this->instantiateVersionProvider();
        $version = $versionProvider->get($moduleName);
        $this->assertSame('unavailable', $version);
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockModuleDirectory = $this->getMockBuilder(ModuleDirectory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockComposerPackage = $this->getMockBuilder(ComposerPackage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockComposerPackageFactory = $this->getMockBuilder(ComposerPackageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockFileSystemDriver = $this->getMockBuilder(DriverInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockSerializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return VersionProvider
     */
    private function instantiateVersionProvider(): VersionProvider
    {
        return new VersionProvider(
            $this->mockModuleDirectory,
            $this->mockComposerPackageFactory,
            $this->mockFileSystemDriver,
            $this->mockSerializer,
            $this->mockLogger,
        );
    }
}
