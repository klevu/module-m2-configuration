<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action\Sdk;

use Klevu\Configuration\Exception\Integration\InactiveAccountException;
use Klevu\Configuration\Exception\Integration\InvalidPlatformException;
use Klevu\Configuration\Service\Action\Sdk\Account\AccountFeaturesActionInterface;
use Klevu\Configuration\Service\Action\Sdk\Account\AccountLookupActionInterface;
use Klevu\Configuration\Service\Action\Sdk\Account\CreateAccountCredentialsActionInterface;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;

class AccountDetailsAction implements AccountDetailsActionInterface
{
    /**
     * @var CreateAccountCredentialsActionInterface
     */
    private readonly CreateAccountCredentialsActionInterface $createAccountCredentialsAction;
    /**
     * @var AccountLookupActionInterface
     */
    private readonly AccountLookupActionInterface $accountLookupAction;
    /**
     * @var AccountFeaturesActionInterface
     */
    private readonly AccountFeaturesActionInterface $accountFeaturesAction;

    /**
     * @param CreateAccountCredentialsActionInterface $createAccountCredentialsAction
     * @param AccountLookupActionInterface $accountLookupAction
     * @param AccountFeaturesActionInterface $accountFeaturesAction
     */
    public function __construct(
        CreateAccountCredentialsActionInterface $createAccountCredentialsAction,
        AccountLookupActionInterface $accountLookupAction,
        AccountFeaturesActionInterface $accountFeaturesAction,
    ) {
        $this->createAccountCredentialsAction = $createAccountCredentialsAction;
        $this->accountLookupAction = $accountLookupAction;
        $this->accountFeaturesAction = $accountFeaturesAction;
    }

    /**
     * @param string $apiKey
     * @param string $authKey
     *
     * @return AccountInterface
     * @throws ValidationException
     * @throws BadRequestException
     * @throws BadResponseException
     * @throws AccountNotFoundException
     * @throws InvalidPlatformException
     * @throws InactiveAccountException
     */
    public function execute(string $apiKey, string $authKey): AccountInterface
    {
        $accountCredentials = $this->createAccountCredentialsAction->execute(
            apiKey: $apiKey,
            authKey: $authKey,
        );
        $account = $this->accountLookupAction->execute(
            accountCredentials: $accountCredentials,
        );
        $accountFeatures = $this->accountFeaturesAction->execute(
            account: $account,
        );
        $account->setAccountFeatures(accountFeatures: $accountFeatures);

        return $account;
    }
}
