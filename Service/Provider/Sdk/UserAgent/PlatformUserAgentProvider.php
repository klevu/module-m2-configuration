<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider\Sdk\UserAgent;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UseOnlyWhitelistedNamespaces.NonFullyQualified
use Composer\InstalledVersions;
use Klevu\PhpSDK\Provider\ComposableUserAgentProviderInterface;
use Klevu\PhpSDK\Provider\ComposableUserAgentProviderTrait;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;

class PlatformUserAgentProvider implements ComposableUserAgentProviderInterface
{
    use ComposableUserAgentProviderTrait;

    public const PRODUCT_NAME = 'klevu-m2-search';

    /**
     * @param UserAgentProviderInterface[] $systemInformationProviders
     */
    public function __construct(
        array $systemInformationProviders = [],
    ) {
        array_walk(
            array: $systemInformationProviders,
            callback: function (mixed $systemInformationProvider, int|string $identifier): void {
                $this->addUserAgentProvider(
                    userAgentProvider: $systemInformationProvider,
                    identifier: is_string($identifier) ? $identifier : null,
                );
            },
        );
    }

    /**
     * @return string
     */
    public function execute(): string
    {
        try {
            $version = InstalledVersions::getVersion('klevu/module-m2-search');
        } catch (\OutOfBoundsException) {
            $version = null;
        }

        $userAgent = $version
            ? sprintf('%s/%s', static::PRODUCT_NAME, $version)
            : static::PRODUCT_NAME;

        $systemInformation = array_filter(
            array_map(
                static fn (UserAgentProviderInterface $systemInformationProvider): string => (
                    $systemInformationProvider->execute()
                ),
                $this->userAgentProviders,
            ),
        );
        if ($systemInformation) {
            $userAgent .= sprintf(
                ' (%s)',
                implode('; ', $systemInformation),
            );
        }

        return $userAgent;
    }
}
