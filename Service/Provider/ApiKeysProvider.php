<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Klevu\Configuration\Model\CurrentScopeFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ApiKeysProvider implements ApiKeysProviderInterface
{
    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;
    /**
     * @var ApiKeyProviderInterface
     */
    private readonly ApiKeyProviderInterface $apiKeyProvider;
    /**
     * @var CurrentScopeFactory
     */
    private readonly CurrentScopeFactory $currentScopeFactory;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ApiKeyProviderInterface $apiKeyProvider
     * @param CurrentScopeFactory $currentScopeFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ApiKeyProviderInterface $apiKeyProvider,
        CurrentScopeFactory $currentScopeFactory,
        LoggerInterface $logger,
    ) {
        $this->storeManager = $storeManager;
        $this->apiKeyProvider = $apiKeyProvider;
        $this->currentScopeFactory = $currentScopeFactory;
        $this->logger = $logger;
    }

    /**
     * @param int[]|null $storeIds
     *
     * @return string[]
     */
    public function get(?array $storeIds = null): array
    {
        if (null === $storeIds) {
            return [];
        }
        $apiKeys = [];
        foreach ($this->storeManager->getStores() as $store) {
            if ($storeIds && !in_array((int)$store->getId(), $storeIds, true)) {
                continue;
            }
            try {
                $apiKeys[] = $this->apiKeyProvider->get(
                    scope: $this->currentScopeFactory->create(data: ['scopeObject' => $store]),
                );
            } catch (NoSuchEntityException $exception) {
                $this->logger->error(
                    message: 'Method: {method} - Error: {message}',
                    context: [
                        'method' => __METHOD__,
                        'message' => $exception->getMessage(),
                    ],
                );
            }
        }

        return array_filter(
            array_unique($apiKeys),
        );
    }
}
