<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Klevu\Configuration\Exception\ApiKeyNotFoundException;
use Klevu\Configuration\Model\CurrentScopeInterface;
use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigCollection;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ValueInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class AuthKeyProvider implements AuthKeyProviderInterface
{
    public const XML_FIELD_REST_AUTH_KEY = 'rest_auth_key';
    public const CONFIG_XML_PATH_REST_AUTH_KEY = 'klevu_configuration/auth_keys/' . self::XML_FIELD_REST_AUTH_KEY;

    /**
     * @var ScopeConfigInterface
     */
    private readonly ScopeConfigInterface $scopeConfig;
    /**
     * @var ConfigCollectionFactory
     */
    private readonly ConfigCollectionFactory $configCollectionFactory;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigCollectionFactory $configCollectionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConfigCollectionFactory $configCollectionFactory,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configCollectionFactory = $configCollectionFactory;
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

    /**
     * @param string $apiKey
     *
     * @return string|null
     * @throws ApiKeyNotFoundException
     */
    public function getForApiKey(string $apiKey): ?string
    {
        [$scopeId, $scopeType] = $this->getApiKeyScope($apiKey);
        $item = $this->getRestAuthKey(scopeId: (int)$scopeId, scopeType: $scopeType);

        // @see docBlock in \Magento\Framework\App\Config\Value for method definition of getValue()
        return $item?->getValue();
    }

    /**
     * @param string $apiKey
     *
     * @return array<int|string>
     * @throws ApiKeyNotFoundException
     */
    private function getApiKeyScope(string $apiKey): array
    {
        /** @var ConfigCollection $configCollection */
        $configCollection = $this->configCollectionFactory->create();
        $configCollection->addFieldToFilter(
            'path',
            ['eq' => ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY],
        );
        $configCollection->addFieldToFilter(
            'value',
            ['eq' => $apiKey],
        );
        if (!$configCollection->getSize()) {
            throw new ApiKeyNotFoundException(
                phrase: __('Requested API Key not found (%1)', $apiKey),
            );
        }
        /** @var ValueInterface $item */
        $item = $configCollection->getFirstItem();

        return [
            // @see docBlock in \Magento\Framework\App\Config\Value for method definition of getScopeId()
            $item->getScopeId(),
            // @see docBlock in \Magento\Framework\App\Config\Value for method definition of getScope()
            $item->getScope(),
        ];
    }

    /**
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return ValueInterface|null
     */
    private function getRestAuthKey(int $scopeId, string $scopeType): ?ValueInterface
    {
        $item = null;
        /** @var ConfigCollection $configCollection */
        $configCollection = $this->configCollectionFactory->create();
        $configCollection->addFieldToFilter(
            'path',
            ['eq' => static::CONFIG_XML_PATH_REST_AUTH_KEY],
        );
        $configCollection->addFieldToFilter(
            'scope_id',
            ['eq' => $scopeId],
        );
        $configCollection->addFieldToFilter(
            'scope',
            ['eq' => $scopeType],
        );
        if ($configCollection->getSize()) {
            /** @var ValueInterface $item */
            $item = $configCollection->getFirstItem();
        }

        return $item;
    }
}
