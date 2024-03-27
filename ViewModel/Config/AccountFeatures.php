<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\ViewModel\Config;

use Klevu\Configuration\Exception\Integration\InactiveAccountException;
use Klevu\Configuration\Exception\Integration\InvalidAccountFeatureException;
use Klevu\Configuration\Exception\Integration\InvalidPlatformException;
use Klevu\Configuration\Exception\StoreNotIntegratedException;
use Klevu\Configuration\Model\CurrentScopeInterface;
use Klevu\Configuration\Service\GetAccountFeaturesServiceInterface;
use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\PhpSDK\Model\Account\AccountFeatures as KlevuSdkAccountFeatures;
use Psr\Log\LoggerInterface;

class AccountFeatures implements AccountFeaturesInterface
{
    /**
     * @var GetAccountFeaturesServiceInterface
     */
    private readonly GetAccountFeaturesServiceInterface $getAccountFeaturesService;
    /**
     * @var ScopeProviderInterface
     */
    private readonly ScopeProviderInterface $scopeProvider;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var KlevuSdkAccountFeatures[]
     */
    private array $accountFeatures = [];
    /**
     * @var string[]
     */
    private array $allowedFeatures = [
        GetAccountFeaturesServiceInterface::ACCOUNT_FEATURE_CAT_NAV,
        GetAccountFeaturesServiceInterface::ACCOUNT_FEATURE_RECS,
        GetAccountFeaturesServiceInterface::ACCOUNT_FEATURE_PL,
    ];

    /**
     * @param GetAccountFeaturesServiceInterface $getAccountFeaturesService
     * @param ScopeProviderInterface $scopeProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetAccountFeaturesServiceInterface $getAccountFeaturesService,
        ScopeProviderInterface $scopeProvider,
        LoggerInterface $logger,
    ) {
        $this->getAccountFeaturesService = $getAccountFeaturesService;
        $this->scopeProvider = $scopeProvider;
        $this->logger = $logger;
    }

    /**
     * @param string $feature
     * @param CurrentScopeInterface|null $scope
     *
     * @return bool
     * @throws InvalidAccountFeatureException
     */
    public function isAvailable(string $feature, ?CurrentScopeInterface $scope = null): bool
    {
        $this->validateFeature($feature);
        $accountFeatures = $this->getAccountFeatures(
            scope: $scope ?? $this->scopeProvider->getCurrentScope(),
        );

        return (bool)$accountFeatures?->{$feature};
    }

    /**
     * @param CurrentScopeInterface $scope
     *
     * @return KlevuSdkAccountFeatures|null
     */
    private function getAccountFeatures(CurrentScopeInterface $scope): ?KlevuSdkAccountFeatures
    {
        $key = $scope->getScopeType() . '-' . $scope->getScopeId();
        if (array_key_exists(key: $key, array: $this->accountFeatures)) {
            return $this->accountFeatures[$key];
        }
        $return = null;
        try {
            $this->accountFeatures[$key] = $this->getAccountFeaturesService->execute(
                scope: $scope,
            );
            $return = $this->accountFeatures[$key];
        } catch (InactiveAccountException | InvalidPlatformException | StoreNotIntegratedException) {
            // no need to log anything here.
            // stores that are not integrated or not active can return null.
            $this->accountFeatures[$key] = null;
        } catch (\Exception $exception) {
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
     * @param string $feature
     *
     * @return void
     * @throws InvalidAccountFeatureException
     */
    private function validateFeature(string $feature): void
    {
        if (in_array(needle: $feature, haystack: $this->allowedFeatures, strict: true)) {
            return;
        }
        throw new InvalidAccountFeatureException(
            __(
                "Requested account feature is invalid. Received '%1', expected one of '%2'.",
                $feature,
                implode(', ', $this->allowedFeatures),
            ),
        );
    }
}
