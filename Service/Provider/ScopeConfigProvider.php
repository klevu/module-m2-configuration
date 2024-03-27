<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ScopeConfigProvider implements ScopeConfigProviderInterface
{
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_FLOAT = 'float';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_STRING = 'string';

    /**
     * @var ScopeConfigInterface
     */
    private readonly ScopeConfigInterface $scopeConfig;
    /**
     * @var ScopeProviderInterface
     */
    private readonly ScopeProviderInterface $scopeProvider;
    /**
     * @var string
     */
    private readonly string $path;
    /**
     * @var string|null
     */
    private readonly ?string $returnType;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ScopeProviderInterface $scopeProvider
     * @param string $path
     * @param string|null $returnType
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ScopeProviderInterface $scopeProvider,
        string $path,
        ?string $returnType = null,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->scopeProvider = $scopeProvider;
        $this->path = $path;
        $this->returnType = $returnType;
    }

    /**
     * @return bool|int|float|string|null
     */
    public function get(): bool|int|float|string|null
    {
        $scope = $this->scopeProvider->getCurrentScope();
        $value = $this->scopeConfig->getValue(
            $this->path,
            $scope->getScopeType(),
            $scope->getScopeId(),
        );
        if (null === $value) {
            return null;
        }

        return match ($this->returnType) {
            static::TYPE_BOOLEAN => $this->getBooleanValue($value),
            static::TYPE_FLOAT => (float)$value,
            static::TYPE_INTEGER => (int)$value,
            static::TYPE_STRING => (string)$value,
            default => $value,
        };
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private function getBooleanValue(mixed $value): bool
    {
        $value = is_string($value)
            ? trim($value)
            : $value;

        return 'false' !== $value && $value;
    }
}
