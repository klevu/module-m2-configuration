<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Ui\DataProvider\Integration\Listing;

use Klevu\Configuration\Service\Provider\ApiKeyProvider;
use Klevu\Configuration\Service\Provider\AuthKeyProvider;
use Klevu\Configuration\Service\Provider\Stores\Config\AuthKeysCollectionProviderInterface;
use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Psr\Log\LoggerInterface;

class StoresDataProvider extends AbstractDataProvider
{
    public const XML_PATH_KLEVU_INTEGRATION_AUTH_KEYS = 'klevu_configuration/auth_keys';

    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;
    /**
     * @var RequestInterface
     */
    private readonly RequestInterface $request;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var DataObject[]|null
     */
    private ?array $configItems = null;
    /**
     * @var mixed[][]
     */
    private array $cachedConfigItemFilter = [];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param AuthKeysCollectionProviderInterface $authKeysCollectionProvider
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param mixed[] $meta
     * @param mixed[] $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        AuthKeysCollectionProviderInterface $authKeysCollectionProvider,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        LoggerInterface $logger,
        array $meta = [],
        array $data = [],
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

        $this->collection = $authKeysCollectionProvider->getAll();
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->logger = $logger;
        $this->prepareUpdateUrl();
    }

    /**
     * @return mixed[]
     */
    public function getData(): array
    {
        $items = [];
        [$storeId, $websiteId] = $this->getRequestParams();
        $configItems = $this->getConfigItems();

        /** @var Store[] $stores */
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            try {
                $website = $store->getWebsite();
            } catch (NoSuchEntityException $exception) {
                $this->logger->error(
                    'Method: {method} - Error: {message}',
                    [
                        'method' => __METHOD__,
                        'message' => $exception->getMessage(),
                    ],
                );
                continue;
            }
            if ($websiteId && $websiteId !== (string)$store->getWebsiteId()) {
                continue;
            }
            if ($storeId && $storeId !== (string)$store->getId()) {
                continue;
            }
            $items[] = [
                'website_id' => (int)$website->getId(),
                'store_id' => (int)$store->getId(),
                'website' => $website->getId() . ': ' . $website->getName() . ' (' . $website->getCode() . ') ',
                'store' => $store->getId() . ': ' . $store->getName() . ' (' . $store->getCode() . ')',
                'integration_message' => $this->getApiKeyMessage(configItems: $configItems, store: $store),
                'store_integrated' => $this->isStoreIntegrated(configItems: $configItems, store: $store),
                'website_integrated' => $this->isWebsiteIntegrated(configItems: $configItems, store: $store),
            ];
        }
        $return = $this->arraySortBy($items, [
            'website_id' => SORT_ASC,
            'store_id' => SORT_ASC,
        ]);

        return [
            'totalRecords' => count($return),
            'items' => $return,
        ];
    }

    /**
     * Passes filter_url_params param to ajax call that populates grid, in this case scope and scope id
     *
     * @return void
     */
    private function prepareUpdateUrl(): void
    {
        if (!is_array($this->data['config']['filter_url_params'] ?? null)) {
            return;
        }
        foreach ($this->data['config']['filter_url_params'] as $paramName => $paramValue) {
            if ('*' === $paramValue) {
                $paramValue = $this->request->getParam($paramName);
            }
            if ($paramValue) {
                $this->data['config']['update_url'] = sprintf(
                    '%s%s/%s/',
                    $this->data['config']['update_url'],
                    $paramName,
                    $paramValue,
                );
            }
        }
    }

    /**
     * @return mixed[]
     */
    private function getRequestParams(): array
    {
        $requestParams = $this->request->getParams();

        return [
            $requestParams['store'] ?? null,
            $requestParams['website'] ?? null,
        ];
    }

    /**
     * @return array<ConfigValue|DataObject>
     */
    private function getConfigItems(): array
    {
        if (null === $this->configItems) {
            $this->configItems = $this->collection->getItems();
        }

        return $this->configItems;
    }

    /**
     * @param array<ConfigValue|DataObject> $configItems
     * @param StoreInterface $store
     *
     * @return Phrase
     */
    private function getApiKeyMessage(array $configItems, StoreInterface $store): Phrase
    {
        $storeScope = true;
        $filteredConfig = $this->filterConfigByStoreScope(configItems: $configItems, store: $store);
        if (!$filteredConfig) {
            $filteredConfig = $this->filterConfigByWebsiteScope(configItems: $configItems, store: $store);
            $storeScope = false;
        }
        $keys = array_keys($filteredConfig);
        $key = $keys[0] ?? 0;
        $configItem = $filteredConfig[$key] ?? null;

        $jsApiKey = $configItem?->getValue();

        if (!$jsApiKey) {
            return __('Not Integrated');
        }

        return $storeScope
            ? __('Integrated at Store Scope (%1)', $jsApiKey)
            : __('Integrated at Website Scope (%1)', $jsApiKey);
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
            callback: static function (ConfigValue|DataObject $configItem) use ($store): bool {
                $storeScope = in_array(
                    needle: $configItem->getScope(),
                    haystack: [ScopeInterface::SCOPE_STORES, ScopeInterface::SCOPE_STORE],
                    strict: true,
                );

                return $storeScope
                    && (int)$configItem->getScopeId() === (int)$store->getId()
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

    /**
     * @param mixed[] $items
     * @param mixed[] $sortBy
     *
     * @return mixed[]
     */
    private function arraySortBy(array $items, array $sortBy): array
    {
        $arguments = [];
        foreach ($sortBy as $field => $order) {
            $dataToSort = [];
            foreach ($items as $key => $item) {
                $dataToSort[$key] = $item[$field];
            }
            $arguments[] = $dataToSort;
            $arguments[] = $order;
        }
        array_multisort($items, ...$arguments);

        return $items;
    }

    /**
     * @param array<ConfigValue|DataObject> $configItems
     * @param StoreInterface $store
     *
     * @return bool
     */
    private function isWebsiteIntegrated(array $configItems, StoreInterface $store): bool
    {
        $items = $this->filterConfigByWebsiteScope(
            configItems: $configItems,
            store: $store,
        );

        return (bool)count($items);
    }

    /**
     * @param array<ConfigValue|DataObject> $configItems
     * @param StoreInterface $store
     *
     * @return bool
     */
    private function isStoreIntegrated(array $configItems, StoreInterface $store): bool
    {
        $items = $this->filterConfigByStoreScope(
            configItems: $configItems,
            store: $store,
        );

        return (bool)count($items);
    }
}
