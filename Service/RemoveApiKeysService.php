<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service;

use Klevu\Configuration\Service\Action\RemoveEndpointsInterface;
use Klevu\Configuration\Service\Provider\ApiKeyProvider;
use Klevu\Configuration\Service\Provider\AuthKeyProvider;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriter;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Psr\Log\LoggerInterface;

class RemoveApiKeysService implements RemoveApiKeysServiceInterface
{
    /**
     * @var ScopeConfigWriter
     */
    private readonly ScopeConfigWriter $scopeConfigWriter;
    /**
     * @var RemoveEndpointsInterface
     */
    private readonly RemoveEndpointsInterface $removeEndpoints;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var ReinitableConfigInterface
     */
    private readonly ReinitableConfigInterface $reinitableConfig;
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;
    /**
     * @var EventManagerInterface
     */
    private readonly EventManagerInterface $eventManager;

    /**
     * @param ScopeConfigWriter $scopeConfigWriter
     * @param RemoveEndpointsInterface $removeEndpoints
     * @param LoggerInterface $logger
     * @param ReinitableConfigInterface $reinitableConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        ScopeConfigWriter $scopeConfigWriter,
        RemoveEndpointsInterface $removeEndpoints,
        LoggerInterface $logger,
        ReinitableConfigInterface $reinitableConfig,
        ScopeConfigInterface $scopeConfig,
        EventManagerInterface $eventManager,
    ) {
        $this->scopeConfigWriter = $scopeConfigWriter;
        $this->removeEndpoints = $removeEndpoints;
        $this->logger = $logger;
        $this->reinitableConfig = $reinitableConfig;
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
    }

    /**
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return void
     */
    public function execute(int $scopeId, string $scopeType): void
    {
        $apiKey = $this->scopeConfig->getValue(
            ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY,
            $scopeType,
            $scopeId,
        );
        $this->removeIntegration($scopeType, $scopeId);

        $this->logger->info(
            message: 'Method: {method} - Info: {message}',
            context: [
                'method' => __METHOD__,
                'message' => sprintf(
                    'Klevu auth keys removed for %s: %s',
                    $scopeType,
                    $scopeId,
                ),
            ],
        );

        $this->eventManager->dispatch(
            'klevu_remove_api_keys_after',
            [
                'apiKey' => $apiKey,
            ],
        );
    }

    /**
     * @param string $scopeType
     * @param int $scopeId
     *
     * @return void
     */
    private function removeIntegration(string $scopeType, int $scopeId): void
    {
        $this->scopeConfigWriter->delete(
            path: ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY,
            scope: $scopeType,
            scopeId: $scopeId,
        );
        $this->scopeConfigWriter->delete(
            path: AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY,
            scope: $scopeType,
            scopeId: $scopeId,
        );
        $this->removeEndpoints->execute(
            scope: $scopeId,
            scopeType: $scopeType,
        );
        $this->reinitableConfig->reinit();
    }
}
