<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Klevu\Configuration\Model\CurrentScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ApiKeyProvider implements ApiKeyProviderInterface
{
    public const CONFIG_XML_PATH_JS_API_KEY = 'klevu_configuration/auth_keys/js_api_key';

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
     */
    public function get(CurrentScopeInterface $scope): ?string
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_JS_API_KEY,
            $scope->getScopeType(),
            $scope->getScopeId(),
        );
    }
}
