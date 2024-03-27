<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider\Sdk\UserAgent\SystemInformation;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UseOnlyWhitelistedNamespaces.NonFullyQualified
use Composer\InstalledVersions;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;

class MagentoFrameworkProvider implements UserAgentProviderInterface
{
    public const PRODUCT_NAME = 'magento-framework';

    /**
     * @return string
     */
    public function execute(): string
    {
        try {
            $version = InstalledVersions::getVersion('magento/framework');
        } catch (\OutOfBoundsException) {
            $version = null;
        }

        return $version
            ? sprintf('%s/%s', static::PRODUCT_NAME, $version)
            : self::PRODUCT_NAME;
    }
}
