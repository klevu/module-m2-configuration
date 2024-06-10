<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Klevu\Configuration\Model\CurrentScopeFactory;
use Klevu\Configuration\Model\CurrentScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ScopeProvider implements ScopeProviderInterface
{
    /**
     * @var StoreScopeProviderInterface
     */
    private readonly StoreScopeProviderInterface $storeScopeProvider;
    /**
     * @var WebsiteScopeProviderInterface
     */
    private readonly WebsiteScopeProviderInterface $websiteScopeProvider;
    /**
     * @var CurrentScopeFactory
     */
    private readonly CurrentScopeFactory $currentScopeFactory;
    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;
    /**
     * @var StoreInterface|WebsiteInterface|null
     */
    private WebsiteInterface|StoreInterface|null $currentScope = null;

    /**
     * @param StoreScopeProviderInterface $storeScopeProvider
     * @param WebsiteScopeProviderInterface $websiteScopeProvider
     * @param CurrentScopeFactory $currentScopeFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreScopeProviderInterface $storeScopeProvider,
        WebsiteScopeProviderInterface $websiteScopeProvider,
        CurrentScopeFactory $currentScopeFactory,
        StoreManagerInterface $storeManager,
    ) {
        $this->storeScopeProvider = $storeScopeProvider;
        $this->websiteScopeProvider = $websiteScopeProvider;
        $this->currentScopeFactory = $currentScopeFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @return CurrentScopeInterface
     */
    public function getCurrentScope(): CurrentScopeInterface
    {
        if ($this->currentScope) {
            return $this->currentScopeFactory->create([
                'scopeObject' => $this->currentScope,
            ]);
        }
        $scope = $this->storeScopeProvider->getCurrentStore();
        if (!$scope) {
            $scope = $this->websiteScopeProvider->getCurrentWebsite();
        }

        return $this->currentScopeFactory->create([
            'scopeObject' => $scope,
        ]);
    }

    /**
     * @param WebsiteInterface|StoreInterface $scope
     *
     * @return void
     */
    public function setCurrentScope(WebsiteInterface|StoreInterface $scope): void
    {
        $this->unsetCurrentScope();
        $this->currentScope = $scope;
        if ($scope instanceof WebsiteInterface) {
            $this->websiteScopeProvider->setCurrentWebsite($scope);
            return;
        }
        $this->storeScopeProvider->setCurrentStore($scope);
    }

    /**
     * @param string $scopeCode
     * @param string $scopeType
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setCurrentScopeByCode(string $scopeCode, string $scopeType): void
    {
        if ($scopeType === ScopeConfigInterface::SCOPE_TYPE_DEFAULT && $this->storeManager->isSingleStoreMode()) {
            $scopeType = ScopeInterface::SCOPE_STORES;
        }
        $this->unsetCurrentScope();
        switch ($scopeType) {
            case ScopeInterface::SCOPE_WEBSITE:
            case ScopeInterface::SCOPE_WEBSITES:
                $this->websiteScopeProvider->setCurrentWebsiteByCode($scopeCode);
                $this->storeScopeProvider->unsetCurrentStore();
                break;

            case ScopeInterface::SCOPE_STORE:
            case ScopeInterface::SCOPE_STORES:
                $this->storeScopeProvider->setCurrentStoreByCode($scopeCode);
                $this->websiteScopeProvider->unsetCurrentWebsite();
                break;

            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        "Invalid scopeType provided. Expected one of %s, Received %s",
                        implode(', ', [ScopeInterface::SCOPE_WEBSITES, ScopeInterface::SCOPE_STORES]),
                        $scopeType,
                    ),
                );
        }
    }

    /**
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setCurrentScopeById(int $scopeId, string $scopeType): void
    {
        if ($scopeType === ScopeConfigInterface::SCOPE_TYPE_DEFAULT && $this->storeManager->isSingleStoreMode()) {
            $scopeType = ScopeInterface::SCOPE_STORES;
        }
        $this->unsetCurrentScope();
        switch ($scopeType) {
            case ScopeInterface::SCOPE_WEBSITE:
            case ScopeInterface::SCOPE_WEBSITES:
                $this->websiteScopeProvider->setCurrentWebsiteById($scopeId);
                $this->storeScopeProvider->unsetCurrentStore();
                break;

            case ScopeInterface::SCOPE_STORE:
            case ScopeInterface::SCOPE_STORES:
                $this->storeScopeProvider->setCurrentStoreById($scopeId);
                $this->websiteScopeProvider->unsetCurrentWebsite();
                break;

            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        "Invalid scopeType provided. Expected one of %s, Received %s",
                        implode(', ', [ScopeInterface::SCOPE_WEBSITES, ScopeInterface::SCOPE_STORES]),
                        $scopeType,
                    ),
                );
        }
    }

    /**
     * @return void
     */
    public function unsetCurrentScope(): void
    {
        $this->currentScope = null;
        $this->storeScopeProvider->unsetCurrentStore();
        $this->websiteScopeProvider->unsetCurrentWebsite();
    }
}
