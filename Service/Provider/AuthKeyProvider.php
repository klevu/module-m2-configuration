<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Klevu\Configuration\Model\CurrentScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class AuthKeyProvider implements AuthKeyProviderInterface
{
    public const CONFIG_XML_PATH_REST_AUTH_KEY = 'klevu_configuration/auth_keys/rest_auth_key';

    /**
     * @var ScopeConfigInterface
     */
    private readonly ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param CurrentScopeInterface $scope
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function get(CurrentScopeInterface $scope): ?string
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_REST_AUTH_KEY,
            $scope->getScopeType(),
            $scope->getScopeId(),
        );
    }
}
