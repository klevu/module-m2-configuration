<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider\Stores\Config;

use Klevu\Configuration\Validator\ValidatorInterface;
use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigCollection;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Select;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class AuthKeysCollectionProvider implements AuthKeysCollectionProviderInterface
{
    public const CONFIG_XML_PATH_KLEVU_AUTH_KEYS = 'klevu_configuration/auth_keys';
    public const FILTER_SCOPE = 'scope';
    public const FILTER_SCOPE_ID = 'scope_id';

    /**
     * @var ConfigCollectionFactory
     */
    private readonly CollectionFactory $configCollectionFactory;
    /**
     * @var ConfigCollection
     */
    private ConfigCollection $configCollection;
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
     * @param CollectionFactory $configCollectionFactory
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
     * @param bool $load
     *
     * @return ConfigCollection
     */
    public function getAll(bool $load = true): ConfigCollection
    {
        $this->configCollection = $this->configCollectionFactory->create();
        $this->removeSelect();
        $this->filterByPath();
        $this->filterByScopeType();
        if ($load && !$this->configCollection->isLoaded()) {
            $this->configCollection->load();
        }

        return $this->configCollection;
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
        $this->configCollection = $this->configCollectionFactory->create();
        $this->removeSelect();
        $this->filterByPath();
        $this->filterByScope(filter: $filter);
        if ($load && !$this->configCollection->isLoaded()) {
            $this->configCollection->load();
        }

        return $this->configCollection;
    }

    /**
     * @return void
     */
    private function removeSelect(): void
    {
        $select = $this->configCollection->getSelect();
        $select->reset(part: Select::WHERE);
    }

    /**
     * @return void
     */
    private function filterByPath(): void
    {
        $this->configCollection->addPathFilter(
            section: static::CONFIG_XML_PATH_KLEVU_AUTH_KEYS,
        );
    }

    /**
     * @return void
     */
    private function filterByScopeType(): void
    {
        $scope = $this->isSingleStoreMode
            ? [ScopeConfigInterface::SCOPE_TYPE_DEFAULT]
            : $this->validScopes;

        $this->configCollection->addFieldToFilter('scope', ['in' => $scope]);
    }

    /**
     * @param mixed[] $filter
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function filterByScope(array $filter): void
    {
        if ($this->isSingleStoreMode) {
            $filter[static::FILTER_SCOPE] = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $filter[static::FILTER_SCOPE_ID] = Store::DEFAULT_STORE_ID;
        }
        $this->validateFilter($filter);
        $this->configCollection->addFieldToFilter('scope', ['eq' => $filter[static::FILTER_SCOPE]]);
        $this->configCollection->addFieldToFilter('scope_id', ['eq' => $filter[static::FILTER_SCOPE_ID]]);
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
