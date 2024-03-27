<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action;

use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriter;

class RemoveEndpoints implements RemoveEndpointsInterface
{
    /**
     * @var ScopeConfigWriter
     */
    private readonly ScopeConfigWriter $scopeConfigWriter;
    /**
     * @var string[]
     */
    private array $endpoints = [
        UpdateEndpoints::CONFIG_XML_PATH_URL_ANALYTICS,
        UpdateEndpoints::CONFIG_XML_PATH_URL_CAT_NAV,
        UpdateEndpoints::CONFIG_XML_PATH_URL_INDEXING,
        UpdateEndpoints::CONFIG_XML_PATH_URL_JS,
        UpdateEndpoints::CONFIG_XML_PATH_URL_SEARCH,
        UpdateEndpoints::CONFIG_XML_PATH_URL_TIERS,
    ];

    /**
     * @param ScopeConfigWriter $scopeConfigWriter
     */
    public function __construct(
        ScopeConfigWriter $scopeConfigWriter,
    ) {
        $this->scopeConfigWriter = $scopeConfigWriter;
    }

    /**
     * @param int $scope
     * @param string $scopeType
     *
     * @return void
     */
    public function execute(int $scope, string $scopeType): void
    {
        foreach ($this->endpoints as $xmlPath) {
            $this->scopeConfigWriter->delete(
                $xmlPath,
                $scopeType,
                $scope,
            );
        }
    }
}
