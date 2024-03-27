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
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface GetAccountFeaturesServiceInterface
{
    public const ACCOUNT_FEATURE_CAT_NAV = 'smartCategoryMerchandising';
    public const ACCOUNT_FEATURE_RECS = 'smartRecommendations';
    public const ACCOUNT_FEATURE_PL = 'preserveLayout';

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
    public function execute(CurrentScopeInterface $scope): ?AccountFeatures;
}
