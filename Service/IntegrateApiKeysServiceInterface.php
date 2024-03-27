<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service;

use Klevu\Configuration\Exception\AccountCacheScopeException;
use Klevu\Configuration\Exception\Integration\InactiveAccountException;
use Klevu\Configuration\Exception\Integration\InvalidPlatformException;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

interface IntegrateApiKeysServiceInterface
{
    /**
     * @param string $apiKey
     * @param string $authKey
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return AccountInterface
     * @throws AccountCacheScopeException
     * @throws AccountNotFoundException
     * @throws BadRequestException
     * @throws BadResponseException
     * @throws InactiveAccountException
     * @throws InvalidPlatformException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws ValidationException
     */
    public function execute(
        string $apiKey,
        string $authKey,
        int $scopeId,
        string $scopeType = ScopeInterface::SCOPE_STORES,
    ): AccountInterface;
}
