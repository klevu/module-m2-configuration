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
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class StoresProvider implements StoresProviderInterface
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
     * @var array<ConfigValue|DataObject>|null
     */
    private ?array $configItems = null;
    /**
     * @var mixed[][]
     */
    private array $cachedConfigItemFilter = [];

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
     * @param string|null $apiKey
     *
     * @return StoreInterface[]
     */
    public function get(?string $apiKey): array
    {
        $return = [];
        $configItems = $this->getConfigItems();
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            if (!$this->isStoreIntegratedWithApiKey($configItems, $store, $apiKey)) {
                continue;
            }
            $return[$store->getId()] = $store;
        }

        return $return;
    }

    /**
     * @return array<string, StoreInterface[]>
     */
    public function getAllIntegratedStores(): array
    {
        $return = [];
        $configItems = $this->getConfigItems();
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $apiKey = $this->getApiKeyForStore(configItems: $configItems, store: $store);
            if (!$apiKey) {
                continue;
            }
            $return[$apiKey][] = $store;
        }

        return $return;
    }

    /**
     * @return array<ConfigValue|DataObject>
     */
    private function getConfigItems(): array
    {
        if (null === $this->configItems) {
            $collection = $this->authKeysCollectionProvider->getAll();
            $this->configItems = $collection->getItems();
        }

        return $this->configItems;
    }

    /**
     * @param array<ConfigValue|DataObject> $configItems
     * @param StoreInterface $store
     * @param string $apiKey
     *
     * @return bool
     */
    private function isStoreIntegratedWithApiKey(array $configItems, StoreInterface $store, string $apiKey): bool
    {
        $filteredConfig = $this->filterConfigByStoreScope(configItems: $configItems, store: $store);
        if (!$filteredConfig) {
            $filteredConfig = $this->filterConfigByWebsiteScope(configItems: $configItems, store: $store);
        }
        $configItem = array_shift($filteredConfig);

        return $apiKey === $configItem?->getValue();
    }

    /**
     * @param array<ConfigValue|DataObject> $configItems
     * @param StoreInterface $store
     *
     * @return string|null
     */
    private function getApiKeyForStore(array $configItems, StoreInterface $store): ?string
    {
        $filteredConfig = $this->filterConfigByStoreScope(configItems: $configItems, store: $store);
        if (!$filteredConfig) {
            $filteredConfig = $this->filterConfigByWebsiteScope(configItems: $configItems, store: $store);
        }
        $configItem = array_shift($filteredConfig);

        return $configItem?->getValue();
    }

    /**
     * @param array<ConfigValue|DataObject> $configItems
     * @param StoreInterface $store
     *
     * @return array<ConfigValue|DataObject>
     */
    private function filterConfigByStoreScope(array $configItems, StoreInterface $store): array
    {
        if (isset($this->cachedConfigItemFilter[ScopeInterface::SCOPE_STORES][$store->getId()])) {
            return $this->cachedConfigItemFilter[ScopeInterface::SCOPE_STORES][$store->getId()];
        }
        $storeItems = array_filter(
            array: $configItems,
            callback: function (ConfigValue|DataObject $configItem) use ($store): bool {
                $storeFilter = $this->storeManager->isSingleStoreMode()
                    || (
                        in_array(
                            needle: $configItem->getScope(),
                            haystack: [ScopeInterface::SCOPE_STORES, ScopeInterface::SCOPE_STORE],
                            strict: true,
                        )
                        && (int)$configItem->getScopeId() === (int)$store->getId()
                    );

                return $storeFilter
                    && null !== $configItem->getValue()
                    && ($configItem->getPath() === ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY
                        || $configItem->getPath() === AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY);
            },
        );

        // must have both keys
        $items = (count($storeItems) >= 2)
            ? array_filter(
                array: $storeItems,
                callback: static function (ConfigValue|DataObject $configItem) {
                    return $configItem->getPath() === ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY;
                },
            )
            : [];
        $this->cachedConfigItemFilter[ScopeInterface::SCOPE_STORES][$store->getId()] = $items;

        return $this->cachedConfigItemFilter[ScopeInterface::SCOPE_STORES][$store->getId()];
    }

    /**
     * @param array<ConfigValue|DataObject> $configItems
     * @param StoreInterface $store
     *
     * @return array<ConfigValue|DataObject>
     */
    private function filterConfigByWebsiteScope(array $configItems, StoreInterface $store): array
    {
        if (isset($this->cachedConfigItemFilter[ScopeInterface::SCOPE_WEBSITES][$store->getWebsiteId()])) {
            return $this->cachedConfigItemFilter[ScopeInterface::SCOPE_WEBSITES][$store->getWebsiteId()];
        }
        $storeItems = array_filter(
            array: $configItems,
            callback: static function (ConfigValue|DataObject $configItem) use ($store): bool {
                $websiteScope = in_array(
                    needle: $configItem->getScope(),
                    haystack: [ScopeInterface::SCOPE_WEBSITES, ScopeInterface::SCOPE_WEBSITE],
                    strict: true,
                );

                return $websiteScope
                    && (int)$configItem->getScopeId() === (int)$store->getWebsiteId()
                    && null !== $configItem->getValue()
                    && ($configItem->getPath() === ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY
                        || $configItem->getPath() === AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY);
            },
        );
        // must have both keys
        $items = (count($storeItems) >= 2)
            ? array_filter(
                array: $storeItems,
                callback: static function (ConfigValue|DataObject $configItem) {
                    return $configItem->getPath() === ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY;
                },
            )
            : [];
        $this->cachedConfigItemFilter[ScopeInterface::SCOPE_WEBSITES][$store->getWebsiteId()] = $items;

        return $this->cachedConfigItemFilter[ScopeInterface::SCOPE_WEBSITES][$store->getWebsiteId()];
    }
}
