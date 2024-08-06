<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Config\ScopeInterface as ConfigScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class StoreScopeProvider implements StoreScopeProviderInterface
{
    private const ADMIN_STORE_ID_PARAM = 'store_id';

    /**
     * @var StoreInterface|null
     */
    private ?StoreInterface $currentStore;
    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;
    /**
     * @var AppState
     */
    private readonly AppState $appState;
    /**
     * @var ConfigScopeInterface
     */
    private readonly ConfigScopeInterface $configScope;
    /**
     * @var RequestInterface
     */
    private readonly RequestInterface $request;

    /**
     * @param StoreManagerInterface $storeManager
     * @param AppState $appState
     * @param ConfigScopeInterface $configScope
     * @param RequestInterface $request
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        AppState $appState,
        ConfigScopeInterface $configScope,
        RequestInterface $request,
    ) {
        $this->storeManager = $storeManager;
        $this->appState = $appState;
        $this->configScope = $configScope;
        $this->request = $request;
    }

    /**
     * @return ?StoreInterface
     */
    public function getCurrentStore(): ?StoreInterface
    {
        // @TODO do not catch exceptions in resolveCurrentStore allow them to bubble up
        return $this->currentStore ??= $this->resolveCurrentStore();
    }

    /**
     * @param StoreInterface $store
     *
     * @return void
     */
    public function setCurrentStore(StoreInterface $store): void
    {
        $this->currentStore = $store;
    }

    /**
     * @param string $storeCode
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function setCurrentStoreByCode(string $storeCode): void
    {
        $this->setCurrentStore(
            $this->storeManager->getStore($storeCode),
        );
    }

    /**
     * @param int $storeId
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function setCurrentStoreById(int $storeId): void
    {
        $this->setCurrentStore(
            $this->storeManager->getStore($storeId),
        );
    }

    /**
     * @return void
     */
    public function unsetCurrentStore(): void
    {
        $this->currentStore = null;
    }

    /**
     * @return StoreInterface|null
     */
    private function resolveCurrentStore(): ?StoreInterface
    {
        try {
            $areaCode = $this->appState->getAreaCode();
            if (!$areaCode) {
                $areaCode = $this->configScope->getCurrentScope();
            }
        } catch (LocalizedException) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            // cannot log error here as this class is used in the logger
        }

        $store = null;
        try {
            switch ($areaCode ?? Area::AREA_FRONTEND) {
                case Area::AREA_CRONTAB:
                case Area::AREA_GLOBAL:
                case Area::AREA_GRAPHQL:
                case Area::AREA_WEBAPI_REST:
                case Area::AREA_WEBAPI_SOAP:
                    break;

                case Area::AREA_ADMINHTML:
                    $store = $this->resolveCurrentAdminhtmlStore();
                    break;

                default:
                    $store = $this->storeManager->getStore();
                    break;
            }
        } catch (NoSuchEntityException) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            // cannot log error here as this class is used in the logger
        }

        return $store;
    }

    /**
     * @return StoreInterface|null
     * @throws NoSuchEntityException
     */
    private function resolveCurrentAdminhtmlStore(): ?StoreInterface
    {
        if ($this->storeManager->isSingleStoreMode()) {
            return $this->storeManager->getDefaultStoreView();
        }

        $storeId = $this->getStoreIdFromRequestParams();
        if (null === $storeId) {
            return null;
        }

        return $this->storeManager->getStore($storeId);
    }

    /**
     * @return int|null
     */
    private function getStoreIdFromRequestParams(): ?int
    {
        if ($this->storeManager->isSingleStoreMode()) {
            $defaultStore = $this->storeManager->getDefaultStoreView();

            return $defaultStore->getId();
        }
        $paramKeys = array_unique([
            'store',
            self::ADMIN_STORE_ID_PARAM,
        ]);
        $params = $this->request->getParams();
        $storeParams = array_filter(
            array: $params,
            callback: static fn (string $param): bool => in_array($param, $paramKeys, true),
            mode: ARRAY_FILTER_USE_KEY,
        );
        if (!count($storeParams)) {
            return null;
        }
        $keys = array_keys($storeParams);

        return $storeParams[$keys[0]]
            ? (int)$storeParams[$keys[0]]
            : null;
    }
}
