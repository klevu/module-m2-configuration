<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action\Sdk\Account;

use Klevu\Configuration\Exception\Integration\InactiveAccountException;
use Klevu\Configuration\Exception\Integration\InvalidPlatformException;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountLookupServiceInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Model\Platforms;

class AccountLookupAction implements AccountLookupActionInterface
{
    private const VALID_INDEXING_VERSIONS = [
        '',
        '3',
    ];

    /**
     * @var AccountLookupServiceInterface
     */
    private readonly AccountLookupServiceInterface $accountLookupService;

    /**
     * @param AccountLookupServiceInterface $accountLookupService
     */
    public function __construct(
        AccountLookupServiceInterface $accountLookupService,
    ) {
        $this->accountLookupService = $accountLookupService;
    }

    /**
     * @param AccountCredentials $accountCredentials
     *
     * @return AccountInterface
     * @throws AccountNotFoundException
     * @throws BadRequestException
     * @throws BadResponseException
     * @throws InactiveAccountException
     * @throws InvalidPlatformException
     * @throws ValidationException
     */
    public function execute(AccountCredentials $accountCredentials): AccountInterface
    {
        $account = $this->accountLookupService->execute(accountCredentials: $accountCredentials);
        $this->validateAccount($account);

        return $account;
    }

    /**
     * @param AccountInterface $account
     *
     * @return void
     * @throws InvalidPlatformException
     * @throws InactiveAccountException
     */
    private function validateAccount(AccountInterface $account): void
    {
        $this->validateIsMagentoAccount($account);
        $this->validateAccountIsActive($account);
        $this->validateIsValidIndexingVersion($account);
    }

    /**
     * @param AccountInterface $account
     *
     * @return void
     * @throws InvalidPlatformException
     */
    private function validateIsMagentoAccount(AccountInterface $account): void
    {
        if (Platforms::tryFrom(value: $account->getPlatform())?->isMagento()) {
            return;
        }
        throw new InvalidPlatformException(
            phrase: __(
                'Account can not be integrated with Magento as it is not assigned to the Magento platform.',
            ),
        );
    }

    /**
     * @param AccountInterface $account
     *
     * @return void
     * @throws InactiveAccountException
     */
    private function validateAccountIsActive(AccountInterface $account): void
    {
        if ($account->isActive()) {
            return;
        }
        throw new InactiveAccountException(
            phrase: __(
                'Account can not be integrated as it is inactive.',
            ),
        );
    }

    /**
     * @param AccountInterface $account
     *
     * @return void
     * @throws InactiveAccountException
     */
    private function validateIsValidIndexingVersion(AccountInterface $account): void
    {
        if (in_array($account->getIndexingVersion(), self::VALID_INDEXING_VERSIONS, true)) {
            return;
        }
        throw new InactiveAccountException(
            phrase: __(
                'Account can not be integrated as it used XML indexing. JSON indexing is required. '
                . 'Please contact support to upgrade your account https://help.klevu.com/',
            ),
        );
    }
}
