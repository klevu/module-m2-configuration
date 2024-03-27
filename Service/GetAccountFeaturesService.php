<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service;

use Klevu\Configuration\Exception\AccountCacheScopeException;
use Klevu\Configuration\Exception\Integration\InactiveAccountException;
use Klevu\Configuration\Exception\Integration\InvalidPlatformException;
use Klevu\Configuration\Exception\StoreNotIntegratedException;
use Klevu\Configuration\Model\CurrentScopeInterface;
use Klevu\Configuration\Service\Action\CacheAccountActionInterface;
use Klevu\Configuration\Service\Action\Sdk\AccountDetailsActionInterface;
use Klevu\Configuration\Service\Provider\ApiKeyProviderInterface;
use Klevu\Configuration\Service\Provider\AuthKeyProviderInterface;
use Klevu\Configuration\Service\Provider\CachedAccountProviderInterface;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class GetAccountFeaturesService implements GetAccountFeaturesServiceInterface
{
    /**
     * @var ApiKeyProviderInterface
     */
    private readonly ApiKeyProviderInterface $apiKeyProvider;
    /**
     * @var AuthKeyProviderInterface
     */
    private readonly AuthKeyProviderInterface $authKeyProvider;
    /**
     * @var CachedAccountProviderInterface
     */
    private readonly CachedAccountProviderInterface $cachedAccountProvider;
    /**
     * @var AccountDetailsActionInterface
     */
    private readonly AccountDetailsActionInterface $accountDetailsAction;
    /**
     * @var CacheAccountActionInterface
     */
    private readonly CacheAccountActionInterface $cacheAccountAction;

    /**
     * @param ApiKeyProviderInterface $apiKeyProvider
     * @param AuthKeyProviderInterface $authKeyProvider
     * @param CachedAccountProviderInterface $cachedAccountProvider
     * @param AccountDetailsActionInterface $accountDetailsAction
     * @param CacheAccountActionInterface $cacheAccountAction
     */
    public function __construct(
        ApiKeyProviderInterface $apiKeyProvider,
        AuthKeyProviderInterface $authKeyProvider,
        CachedAccountProviderInterface $cachedAccountProvider,
        AccountDetailsActionInterface $accountDetailsAction,
        CacheAccountActionInterface $cacheAccountAction,
    ) {
        $this->apiKeyProvider = $apiKeyProvider;
        $this->authKeyProvider = $authKeyProvider;
        $this->cachedAccountProvider = $cachedAccountProvider;
        $this->accountDetailsAction = $accountDetailsAction;
        $this->cacheAccountAction = $cacheAccountAction;
    }

    /**
     * @param CurrentScopeInterface $scope
     *
     * @return AccountFeatures|null
     * @throws InactiveAccountException
     * @throws InvalidPlatformException
     * @throws NoSuchEntityException
     * @throws StoreNotIntegratedException
     * @throws AccountCacheScopeException
     * @throws LocalizedException
     */
    public function execute(CurrentScopeInterface $scope): ?AccountFeatures
    {
        if (null === $scope->getScopeId()) {
            return null;
        }
        $cachedAccountFeatures = $this->cachedAccountProvider->get(
            scopeId: $scope->getScopeId(),
            scopeType: $scope->getScopeType(),
        );
        if (null !== $cachedAccountFeatures) {
            return $cachedAccountFeatures;
        }
        try {
            $account = $this->accountDetailsAction->execute(
                apiKey: $this->apiKeyProvider->get($scope),
                authKey: $this->authKeyProvider->get($scope),
            );
        } catch (\TypeError) { // @phpstan-ignore-line
            throw new StoreNotIntegratedException(
                __('Store is not Integrated with Klevu. Cannot retrieve account features.'),
            );
        }
        $accountFeatures = $account->getAccountFeatures();

        $this->cacheAccountAction->execute(
            accountFeatures: $accountFeatures,
            scopeId: $scope->getScopeId(),
            scopeType: $scope->getScopeType(),
        );

        return $accountFeatures;
    }
}
