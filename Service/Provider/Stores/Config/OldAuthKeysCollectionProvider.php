<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider\Stores\Config;

use Klevu\Configuration\Validator\ValidatorInterface;
use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigCollection;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Select;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class OldAuthKeysCollectionProvider implements OldAuthKeysCollectionProviderInterface
{
    public const CONFIG_XML_PATH_KLEVU_AUTH_KEYS = 'klevu_search/general';
    public const FILTER_SCOPE = 'scope';
    public const FILTER_SCOPE_ID = 'scope_id';
    public const XML_FIELD_REST_API_KEY = 'rest_api_key';
    public const XML_FIELD_JS_API_KEY = 'js_api_key';

    /**
     * @var ConfigCollectionFactory
     */
    private readonly ConfigCollectionFactory $configCollectionFactory;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $scopeIdValidator;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $scopeTypeValidator;
    /**
     * @var bool
     */
    private readonly bool $isSingleStoreMode;
    /**
     * @var string[]
     */
    private array $validScopes = [
        ScopeInterface::SCOPE_STORE,
        ScopeInterface::SCOPE_STORES,
        ScopeInterface::SCOPE_WEBSITE,
        ScopeInterface::SCOPE_WEBSITES,
    ];

    /**
     * @param ConfigCollectionFactory $configCollectionFactory
     * @param ValidatorInterface $scopeIdValidator
     * @param ValidatorInterface $scopeTypeValidator
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ConfigCollectionFactory $configCollectionFactory,
        ValidatorInterface $scopeIdValidator,
        ValidatorInterface $scopeTypeValidator,
        StoreManagerInterface $storeManager,
    ) {
        $this->configCollectionFactory = $configCollectionFactory;
        $this->scopeIdValidator = $scopeIdValidator;
        $this->scopeTypeValidator = $scopeTypeValidator;
        $this->isSingleStoreMode = $storeManager->isSingleStoreMode();
    }

    /**
     * @param string[] $filter ['scope' => 'Some scope', 'scope_id' => 'some id']
     * @param bool $load
     *
     * @return ConfigCollection
     * @throws \InvalidArgumentException
     */
    public function get(array $filter = [], bool $load = true): ConfigCollection
    {
        $configCollection = $this->configCollectionFactory->create();
        $this->removeSelect(collection: $configCollection);
        $this->filterByPath(collection: $configCollection);
        $this->filterByScope(collection: $configCollection, filter: $filter);

        if ($load && !$configCollection->isLoaded()) {
            $configCollection->load();
        }

        return $configCollection;
    }

    /**
     * @param ConfigCollection $collection
     *
     * @return void
     */
    private function removeSelect(ConfigCollection $collection): void
    {
        $select = $collection->getSelect();
        $select->reset(part: Select::WHERE);
    }

    /**
     * @param ConfigCollection $collection
     *
     * @return void
     */
    private function filterByPath(ConfigCollection $collection): void
    {
        $collection->addPathFilter(
            section: static::CONFIG_XML_PATH_KLEVU_AUTH_KEYS,
        );
    }

    /**
     *
     * @param ConfigCollection $collection
     * @param mixed[] $filter
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function filterByScope(ConfigCollection $collection, array $filter = []): void
    {
        if (!$filter) {
            $scope = $this->isSingleStoreMode
                ? [ScopeConfigInterface::SCOPE_TYPE_DEFAULT]
                : $this->validScopes;

            $collection->addFieldToFilter('scope', ['in' => $scope]);
            return;
        }
        if ($this->isSingleStoreMode) {
            $filter[static::FILTER_SCOPE] = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $filter[static::FILTER_SCOPE_ID] = Store::DEFAULT_STORE_ID;
        }
        $this->validateFilter($filter);
        $collection->addFieldToFilter('scope', ['eq' => $filter[static::FILTER_SCOPE]]);
        $collection->addFieldToFilter('scope_id', ['eq' => $filter[static::FILTER_SCOPE_ID]]);
    }

    /**
     * @param mixed[] $filter
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateFilter(array $filter): void
    {
        $this->validateScope($filter);
        $this->validateScopeId($filter);
    }

    /**
     * @param mixed[] $filter
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateScope(array $filter): void
    {
        if ($this->isSingleStoreMode) {
             return;
        }
        if (!isset($filter[static::FILTER_SCOPE])) {
            throw new \InvalidArgumentException(
                __('Filter array is missing "scope" key.')->render(),
            );
        }
        if (!$this->scopeTypeValidator->isValid($filter[static::FILTER_SCOPE])) {
            $messages = '';
            if ($this->scopeTypeValidator->hasMessages()) {
                $messages = implode(': ', $this->scopeTypeValidator->getMessages());
            }
            throw new \InvalidArgumentException(
                __(
                    'Invalid Argument: %1',
                    $messages,
                )->render(),
            );
        }
    }

    /**
     * @param mixed[] $filter
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateScopeId(array $filter): void
    {
        if ($this->isSingleStoreMode) {
            return;
        }
        if (!isset($filter[static::FILTER_SCOPE_ID])) {
            throw new \InvalidArgumentException(
                __('Filter array is missing "scope_id" key.')->render(),
            );
        }
        if (!$this->scopeIdValidator->isValid($filter[static::FILTER_SCOPE_ID])) {
            $messages = '';
            if ($this->scopeIdValidator->hasMessages()) {
                $messages = implode(': ', $this->scopeIdValidator->getMessages());
            }
            throw new \InvalidArgumentException(
                __(
                    'Invalid Argument: %1',
                    $messages,
                )->render(),
            );
        }
    }
}
