<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider\Modules;

use Magento\Framework\Config\Composer\PackageFactory as ComposerPackageFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DriverInterface as FileSystemDriverInterface;
use Magento\Framework\Module\Dir as ModuleDirectory;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class VersionProvider implements VersionProviderInterface
{
    private const COMPOSER_FILE_NAME = 'composer.json';

    /**
     * @var ModuleDirectory
     */
    private readonly ModuleDirectory $moduleDirectory;
    /**
     * @var ComposerPackageFactory
     */
    private readonly ComposerPackageFactory $composerPackageFactory;
    /**
     * @var FileSystemDriverInterface
     */
    private readonly FileSystemDriverInterface $fileSystemDriver;
    /**
     * @var SerializerInterface
     */
    private readonly SerializerInterface $serializer;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * @param ModuleDirectory $moduleDirectory
     * @param ComposerPackageFactory $composerPackageFactory
     * @param FileSystemDriverInterface $fileSystemDriver
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDirectory $moduleDirectory,
        ComposerPackageFactory $composerPackageFactory,
        FileSystemDriverInterface $fileSystemDriver,
        SerializerInterface $serializer,
        LoggerInterface $logger,
    ) {
        $this->moduleDirectory = $moduleDirectory;
        $this->composerPackageFactory = $composerPackageFactory;
        $this->fileSystemDriver = $fileSystemDriver;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @param string $moduleName
     *
     * @return string
     */
    public function get(string $moduleName): string
    {
        $version = 'unavailable';
        $modulePath = $this->getModulePath($moduleName);
        if (!$modulePath) {
            return $version;
        }
        $composerJson = $this->getComposerJson($modulePath);
        if (!$composerJson) {
            return $version;
        }
        $composerObj = $this->decodeJson($composerJson);
        if (!$composerObj) {
            return $version;
        }
        $composerPackage = $this->composerPackageFactory->create(['json' => $composerObj]);

        return $composerPackage->get('version');
    }

    /**
     * @param string $moduleName
     *
     * @return string|null
     */
    private function getModulePath(string $moduleName): ?string
    {
        $modulePath = null;

        try {
            $modulePath = $this->moduleDirectory->getDir($moduleName);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error(
                'Method: {method} - Error: {exception}',
                [
                    'method' => __METHOD__,
                    'exception' => $exception->getMessage(),
                ],
            );
        }

        return $modulePath;
    }

    /**
     * @param string $modulePath
     *
     * @return string|null
     */
    private function getComposerJson(string $modulePath): ?string
    {
        $composerJson = null;
        try {
            $composerPath = $modulePath . DIRECTORY_SEPARATOR . self::COMPOSER_FILE_NAME;
            $composerJson = $this->fileSystemDriver->fileGetContents($composerPath);
        } catch (FileSystemException $exception) {
            $this->logger->error(
                'Method: {method} - Error: {exception}',
                [
                    'method' => __METHOD__,
                    'exception' => $exception->getMessage(),
                ],
            );
        }

        return $composerJson;
    }

    /**
     * @param string $json
     *
     * @return \stdClass|null
     */
    private function decodeJson(string $json): ?\stdClass
    {
        $composerObj = null;
        try {
            /** @var mixed[] $composerArray */
            $composerArray = $this->serializer->unserialize($json);
            $composerObj = $this->convertToObject($composerArray);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error(
                'Method: {method} - Error: {exception}',
                [
                    'method' => __METHOD__,
                    'exception' => $exception->getMessage(),
                ],
            );
        }

        return $composerObj;
    }

    /**
     * @param mixed[] $array
     *
     * @return \stdClass
     */
    private function convertToObject(array $array): \stdClass
    {
        $object = new \stdClass();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->convertToObject($value);
            }
            $object->$key = $value;
        }

        return $object;
    }
}
