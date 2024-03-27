<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Klevu\Configuration\Service\Provider\Stores\Config\AuthKeysCollectionProviderInterface;
use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\DataObject;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class OtherIntegratedScopesProvider implements OtherIntegratedScopesProviderInterface
{
    /**
     * @var AuthKeysCollectionProviderInterface
     */
    private readonly AuthKeysCollectionProviderInterface $authKeysCollectionProvider;
    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;

    /**
     * @param AuthKeysCollectionProviderInterface $authKeysCollectionProvider
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AuthKeysCollectionProviderInterface $authKeysCollectionProvider,
        StoreManagerInterface $storeManager,
    ) {
        $this->authKeysCollectionProvider = $authKeysCollectionProvider;
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $apiKey
     * @param string $authKey
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return string[]
     */
    public function get(string $apiKey, string $authKey, int $scopeId, string $scopeType): array
    {
        $values = $this->getOtherConfigValues($apiKey, $authKey, $scopeId, $scopeType);

        return $this->formatScopes($values);
    }

    /**
     * @param string $apiKey
     * @param string $authKey
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return array<ConfigValue|DataObject>
     */
    private function getOtherConfigValues(
        string $apiKey,
        string $authKey,
        int $scopeId,
        string $scopeType,
    ): array {
        $authKeysCollection = $this->authKeysCollectionProvider->getAll();

        $authKeysWithoutCurrentScope = array_filter(
            array: $authKeysCollection->getItems(),
            callback: static fn (ConfigValue | DataObject $configValue): bool => (
                !($configValue->getScope() === $scopeType && (int)$configValue->getScopeId() === $scopeId)
            ),
        );

        return array_filter(
            array: $authKeysWithoutCurrentScope,
            callback: static fn (ConfigValue | DataObject $configValue): bool => (
                in_array(
                    needle: $configValue->getPath(),
                    haystack: [
                        AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY,
                        ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY,
                    ],
                    strict: true,
                )
                && in_array(
                    needle: $configValue->getValue(),
                    haystack: [
                        $authKey,
                        $apiKey,
                    ],
                    strict: true,
                )
            ),
        );
    }

    /**
     * @param array<ConfigValue|DataObject> $configValues
     *
     * @return string[]
     */
    private function formatScopes(array $configValues): array
    {
        $storeValues = array_filter(
            array: $configValues,
            callback: static fn (ConfigValue | DataObject $configValue): bool => (
                $configValue->getScope() === ScopeInterface::SCOPE_STORES
            ),
        );
        $websiteValues = array_filter(
            array: $configValues,
            callback: static fn (ConfigValue | DataObject $configValue): bool => (
                $configValue->getScope() === ScopeInterface::SCOPE_WEBSITES
            ),
        );

        return array_unique(
            array_merge(
                $this->formatStoreValues(storeValues: $storeValues),
                $this->formatWebsiteValues(websiteValues: $websiteValues),
            ),
        );
    }

    /**
     * @param array<ConfigValue|DataObject> $websiteValues
     *
     * @return string[]
     */
    private function formatWebsiteValues(array $websiteValues): array
    {
        if (!$websiteValues) {
            return [];
        }
        $websites = $this->storeManager->getWebsites();

        return array_map(
            callback: static function (ConfigValue | DataObject $configValue) use ($websites): string {
                $website = array_filter(
                    array: $websites,
                    callback: static fn (WebsiteInterface $website): bool => (
                        (int)$configValue->getScopeId() === (int)$website->getId()
                    ),
                );
                $keys = array_keys(array: $website);
                /** @var WebsiteInterface $website */
                $website = $website[$keys[0]];

                return __(
                    'Website: %1 %2 (%3)',
                    $website->getId(),
                    $website->getName(),
                    $website->getCode(),
                )->render();
            },
            array: $websiteValues,
        );
    }

    /**
     * @param array<ConfigValue|DataObject> $storeValues
     *
     * @return string[]
     */
    private function formatStoreValues(array $storeValues): array
    {
        if (!$storeValues) {
            return [];
        }
        $stores = $this->storeManager->getStores();

        return array_map(
            callback: static function (ConfigValue | DataObject $configValue) use ($stores): string {
                $store = array_filter(
                    array: $stores,
                    callback: static fn (StoreInterface $store): bool => (
                        (int)$configValue->getScopeId() === (int)$store->getId()
                    ),
                );
                $keys = array_keys(array: $store);
                /** @var StoreInterface $store */
                $store = $store[$keys[0]];

                return __(
                    'Store: %1 %2 (%3)',
                    $store->getId(),
                    $store->getName(),
                    $store->getCode(),
                )->render();
            },
            array: $storeValues,
        );
    }
}
