<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service;

use Klevu\Configuration\Model\CurrentScopeInterface;
use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Configuration\Service\Provider\Stores\Config\AuthKeysCollectionProvider;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class IsStoreIntegratedService implements IsStoreIntegratedServiceInterface
{
    /**
     * @var AuthKeysCollectionProvider
     */
    private readonly AuthKeysCollectionProvider $authKeysCollectionProvider;
    /**
     * @var ScopeProviderInterface
     */
    private readonly ScopeProviderInterface $scopeProvider;
    /**
     * @var bool
     */
    private readonly bool $isSingleStoreMode;

    /**
     * @param AuthKeysCollectionProvider $authKeysCollectionProvider
     * @param ScopeProviderInterface $scopeProvider
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AuthKeysCollectionProvider $authKeysCollectionProvider,
        ScopeProviderInterface $scopeProvider,
        StoreManagerInterface $storeManager,
    ) {
        $this->authKeysCollectionProvider = $authKeysCollectionProvider;
        $this->scopeProvider = $scopeProvider;
        $this->isSingleStoreMode = $storeManager->isSingleStoreMode();
    }

    /**
     * @return bool
     */
    public function execute(): bool
    {
        $scope = $this->scopeProvider->getCurrentScope();

        return $this->isStoreScope($scope)
            && ($this->isStoreIntegrated($scope) || $this->isWebsiteIntegrated($scope));
    }

    /**
     * @param CurrentScopeInterface $scope
     *
     * @return bool
     */
    private function isStoreScope(CurrentScopeInterface $scope): bool
    {
        if ($this->isSingleStoreMode) {
            return true;
        }
        return in_array(
            needle: $scope->getScopeType(),
            haystack: [ScopeInterface::SCOPE_STORE, ScopeInterface::SCOPE_STORES],
            strict: true,
        );
    }

    /**
     * @param CurrentScopeInterface $scope
     *
     * @return bool
     */
    private function isStoreIntegrated(CurrentScopeInterface $scope): bool
    {
        $authKeys = $this->authKeysCollectionProvider->get([
            'scope' => $scope->getScopeType(),
            'scope_id' => $scope->getScopeId(),
        ]);

        return $authKeys->count() > 1;
    }

    /**
     * @param CurrentScopeInterface $scope
     *
     * @return bool
     */
    private function isWebsiteIntegrated(CurrentScopeInterface $scope): bool // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter, Generic.Files.LineLength.TooLong
    {
        return false; // @TODO remove when channels are available
//        $store = $scope->getScopeObject();
//        $websiteId = $this->getWebsiteId($store);
//        if (null === $websiteId) {
//            return false;
//        }
//        $authKeys = $this->authKeysCollectionProvider->get([
//            'scope' => ScopeInterface::SCOPE_WEBSITES,
//            'scope_id' => $websiteId,
//        ]);
//
//        return $authKeys->count() > 1;
    }

    /**
     * @param StoreInterface|WebsiteInterface|null $store
     *
     * @return int|null
     */
    // @todo Reinstate when channels are available
//    private function getWebsiteId(
//        StoreInterface|WebsiteInterface|null $store,
//    ): ?int {
//        return method_exists($store, 'getWebsiteId')
//            ? (int)$store->getWebsiteId()
//            : null;
//    }
}
