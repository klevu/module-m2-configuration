<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action;

use Klevu\Configuration\Service\Provider\Stores\Config\OldAuthKeysCollectionProvider;
use Klevu\Configuration\Service\Provider\Stores\Config\OldAuthKeysCollectionProviderInterface;
use Magento\Config\Model\ResourceModel\Config as ConfigResourceModel;
use Magento\Config\Model\ResourceModel\ConfigFactory as ConfigResourceModelFactory;
use Magento\Framework\App\Config\ValueInterface;
use Psr\Log\LoggerInterface;

class RemoveOldApiKeysAction implements RemoveOldApiKeysActionInterface
{
    /**
     * @var OldAuthKeysCollectionProviderInterface
     */
    private readonly OldAuthKeysCollectionProviderInterface $oldAuthKeysCollectionProvider;
    /**
     * There is no repository for config setting so use the resourceModel instead
     *
     * @var ConfigResourceModel
     */
    private readonly ConfigResourceModel $configResourceModel;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * @param OldAuthKeysCollectionProviderInterface $oldAuthKeysCollectionProvider
     * @param ConfigResourceModelFactory $configResourceModelFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        OldAuthKeysCollectionProviderInterface $oldAuthKeysCollectionProvider,
        ConfigResourceModelFactory $configResourceModelFactory,
        LoggerInterface $logger,
    ) {
        $this->oldAuthKeysCollectionProvider = $oldAuthKeysCollectionProvider;
        $this->configResourceModel = $configResourceModelFactory->create();
        $this->logger = $logger;
    }

    /**
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return void
     */
    public function execute(int $scopeId, string $scopeType): void
    {
        $configCollection = $this->oldAuthKeysCollectionProvider->get(
            filter: [
                OldAuthKeysCollectionProvider::FILTER_SCOPE => $scopeType,
                OldAuthKeysCollectionProvider::FILTER_SCOPE_ID => $scopeId,
            ],
        );
        /** @var ValueInterface $oldAuthKey */
        foreach ($configCollection->getItems() as $oldAuthKey) {
            $this->configResourceModel->deleteConfig(
                // @see docBlock in \Magento\Framework\App\Config\Value for method definition of getPath()
                path: $oldAuthKey->getPath(),
                scope: $scopeType,
                scopeId: $scopeId,
            );
            $this->logRemoval(oldAuthKey: $oldAuthKey);
        }
    }

    /**
     * @param ValueInterface $oldAuthKey
     *
     * @return void
     */
    private function logRemoval(ValueInterface $oldAuthKey): void
    {
        $jsApiKeyPath = OldAuthKeysCollectionProvider::CONFIG_XML_PATH_KLEVU_AUTH_KEYS
            . '/' . OldAuthKeysCollectionProvider::XML_FIELD_JS_API_KEY;
        if ($oldAuthKey->getPath() !== $jsApiKeyPath) {
            return;
        }
        $this->logger->info(
            message: 'Method: {method}, Info: {message}',
            context: [
                'method' => __METHOD__,
                'message' => sprintf(
                    'Klevu JS API Key for indexing v2 "%s" removed.',
                    // @see docBlock in \Magento\Framework\App\Config\Value for method definition of getValue()
                    $oldAuthKey->getValue(),
                ),
            ],
        );
    }
}
