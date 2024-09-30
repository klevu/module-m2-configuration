<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Ui\DataProvider\Integration\Form;

use Klevu\Configuration\Service\GetBearerTokenInterface;
use Klevu\Configuration\Service\Provider\AuthKeyProvider;
use Klevu\Configuration\Service\Provider\Stores\Config\AuthKeysCollectionProvider;
use Klevu\Configuration\Service\Provider\Stores\Config\AuthKeysCollectionProviderInterface;
use Klevu\Configuration\Service\Provider\Stores\Config\OldAuthKeysCollectionProvider;
use Klevu\Configuration\Service\Provider\Stores\Config\OldAuthKeysCollectionProviderInterface;
use Klevu\Configuration\Validator\ValidatorInterface;
use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ValueInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Psr\Log\LoggerInterface;

class WizardDataProvider extends AbstractDataProvider
{
    private const PARAM_LOGGER_SCOPE_ID = 'logger_scope_id';
    private const PARAM_SCOPE_ID = 'scope_id';
    private const PARAM_SCOPE = 'scope';
    private const PARAM_DISPLAY_SCOPE = 'display_scope';
    private const BEARER = 'bearer';
    private const MULTI_STORE_MODE = 'multi_store_mode';
    private const MESSAGES = 'messages';

    /**
     * @var OldAuthKeysCollectionProviderInterface
     */
    private readonly OldAuthKeysCollectionProviderInterface $oldAuthKeysCollectionProvider;
    /**
     * @var RequestInterface
     */
    private readonly RequestInterface $request;
    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var GetBearerTokenInterface
     */
    private readonly GetBearerTokenInterface $getBearerToken;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $scopeIdValidator;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $scopeTypeValidator;
    /**
     * @var string|null
     */
    private ?string $currentScope = null;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param AuthKeysCollectionProviderInterface $authKeysCollectionProvider
     * @param OldAuthKeysCollectionProviderInterface $oldAuthKeysCollectionProvider
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param GetBearerTokenInterface $getBearerToken
     * @param ValidatorInterface $scopeIdValidator
     * @param ValidatorInterface $scopeTypeValidator
     * @param mixed[] $meta
     * @param mixed[] $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        AuthKeysCollectionProviderInterface $authKeysCollectionProvider,
        OldAuthKeysCollectionProviderInterface $oldAuthKeysCollectionProvider,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        GetBearerTokenInterface $getBearerToken,
        ValidatorInterface $scopeIdValidator,
        ValidatorInterface $scopeTypeValidator,
        array $meta = [],
        array $data = [],
    ) {
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->getBearerToken = $getBearerToken;
        $this->scopeIdValidator = $scopeIdValidator;
        $this->scopeTypeValidator = $scopeTypeValidator;
        $this->collection = $authKeysCollectionProvider->get(
            filter: $this->getFilter(),
        );

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

        $this->prepareUpdateUrl();
        $this->oldAuthKeysCollectionProvider = $oldAuthKeysCollectionProvider;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed[]
     * @throws LocalizedException
     */
    public function getData(): array
    {
        $scopeId = $this->getScopeId();
        if (null === $scopeId) {
            return [
                'totalRecords' => 0,
                'items' => [],
            ];
        }
        $items = $this->getApiKeys(scopeId: $scopeId);
        $oldItems = [];
        if (!$items) {
            $oldItems = $this->getOldApiKeys(scopeId: $scopeId);
        }

        $return = [];
        $return[$scopeId][self::MESSAGES] = [];
        foreach ($items as $item) {
            $key = str_replace(
                search: AuthKeysCollectionProvider::CONFIG_XML_PATH_KLEVU_AUTH_KEYS . '/',
                replace: '',
                // @see docBlock in \Magento\Framework\App\Config\Value for method definition of getPath()
                subject: $item->getPath(),
            );
            // @see docBlock in \Magento\Framework\App\Config\Value for method definition of getValue()
            $return[$scopeId][$key] = $item->getValue();
        }
        foreach ($oldItems as $item) {
            $key = str_replace(
                search: OldAuthKeysCollectionProvider::CONFIG_XML_PATH_KLEVU_AUTH_KEYS . '/',
                replace: '',
                // @see docBlock in \Magento\Framework\App\Config\Value for method definition of getPath()
                subject: $item->getPath(),
            );
            if ($key === OldAuthKeysCollectionProvider::XML_FIELD_REST_API_KEY) {
                $key = AuthKeyProvider::XML_FIELD_REST_AUTH_KEY;
            }
            // @see docBlock in \Magento\Framework\App\Config\Value for method definition of getValue()
            $return[$scopeId][$key] = $item->getValue();

            $return[$scopeId][self::MESSAGES] = [
                __('This store is not currently integrated.'),
                __(
                    'The form is pre-populated with Auth Keys used in a previous integration for this store.',
                ),
            ];
        }
        $return[$scopeId][self::PARAM_LOGGER_SCOPE_ID] = $this->getLoggerScopeId();
        $return[$scopeId][self::PARAM_SCOPE_ID] = $scopeId;
        $return[$scopeId][self::PARAM_SCOPE] = $this->currentScope;
        $return[$scopeId][self::BEARER] = $this->getBearerToken->execute();
        $return[$scopeId][self::MULTI_STORE_MODE] = !(bool)$this->storeManager->isSingleStoreMode();
        $return[$scopeId][self::PARAM_DISPLAY_SCOPE] = ucwords(rtrim($this->currentScope, 's'))
            . ' ID: ' . $scopeId;

        return $return;
    }

    /**
     * @return mixed[]
     */
    private function getFilter(): array
    {
        $scopeId = $this->getScopeId();

        return [
            AuthKeysCollectionProvider::FILTER_SCOPE_ID => $scopeId,
            AuthKeysCollectionProvider::FILTER_SCOPE => $this->currentScope,
        ];
    }

    /**
     * ID used to save value in core config data
     *
     * @return int|null
     */
    private function getScopeId(): ?int
    {
        $return = null;
        $scope = $this->request->getParam(key: self::PARAM_SCOPE);
        $scopeId = $this->request->getParam(key: self::PARAM_SCOPE_ID);
        if (!$this->isScopeValid(scope: $scope) || !$this->isScopeIdValid(scopeId: $scopeId)) {
            return $return;
        }
        $isStoreScope = in_array(
            needle: $scope,
            haystack: [ScopeInterface::SCOPE_STORES, ScopeInterface::SCOPE_STORE],
            strict: true,
        );
        try {
            if ($this->storeManager->isSingleStoreMode()) {
                $return = Store::DEFAULT_STORE_ID;
                $this->currentScope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            } elseif ($isStoreScope) {
                $store = $this->storeManager->getStore(storeId: $scopeId);
                $return = (int)$store->getId();
                $this->currentScope = ScopeInterface::SCOPE_STORES;
            } else {
                $website = $this->storeManager->getWebsite(websiteId: $scopeId);
                $return = (int)$website->getId();
                $this->currentScope = ScopeInterface::SCOPE_WEBSITES;
            }
        } catch (LocalizedException $exception) {
            $this->logger->error(
                message: 'Method: {method} - Error: {message}',
                context: [
                    'method' => __METHOD__,
                    'message' => $exception->getMessage(),
                ],
            );
        }

        return $return;
    }

    /**
     * ID used to log data to var/log/klevu/<store_code>
     *
     * @return int|null
     */
    private function getLoggerScopeId(): ?int
    {
        if ($this->storeManager->isSingleStoreMode()) {
            $store = $this->storeManager->getDefaultStoreView();

            return (int)$store->getId();
        }

        return $this->getScopeId();
    }

    /**
     * Passes filter_url_params param to ajax call that populates form, in this case scope and scope_id
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
                $paramValue = $this->request->getParam(key: $paramName);
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
     * @param mixed $scope
     *
     * @return bool
     */
    private function isScopeValid(mixed $scope): bool
    {
        if ($this->scopeTypeValidator->isValid(value: $scope)) {
            return true;
        }
        $this->logger->error(
            message: 'Method: {method} - Error: {message}',
            context: [
                'method' => __METHOD__,
                'message' => implode(': ', $this->scopeIdValidator->getMessages()),
            ],
        );

        return false;
    }

    /**
     * @param mixed $scopeId
     *
     * @return bool
     */
    private function isScopeIdValid(mixed $scopeId): bool
    {
        if ($this->scopeIdValidator->isValid(value: $scopeId)) {
            return true;
        }
        $this->logger->error(
            message: 'Method: {method} - Error: {message}',
            context: [
                'method' => __METHOD__,
                'message' => implode(': ', $this->scopeIdValidator->getMessages()),
            ],
        );

        return false;
    }

    /**
     * @param int $scopeId
     *
     * @return array<ValueInterface|DataObject>
     */
    private function getApiKeys(int $scopeId): array
    {
        return array_filter(
            array: $this->collection->getItems(),
            callback: fn (ValueInterface | DataObject $item): bool => (
                $item->getScope() === $this->currentScope
                    && (int)$item->getScopeId() === $scopeId
            ),
        );
    }

    /**
     * @param int $scopeId
     *
     * @return array<ValueInterface|DataObject>
     */
    private function getOldApiKeys(int $scopeId): array
    {
        /** @var ConfigCollection $oldAuthKeysCollection */
        $oldAuthKeysCollection = $this->oldAuthKeysCollectionProvider->get(
            filter: $this->getFilter(),
        );

        return array_filter(
            array: $oldAuthKeysCollection->getItems(),
            callback: fn (ValueInterface | DataObject $item): bool => (
                $item->getScope() === $this->currentScope
                    && (int)$item->getScopeId() === $scopeId
            ),
        );
    }
}
