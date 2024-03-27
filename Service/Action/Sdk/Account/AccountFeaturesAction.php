<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action\Sdk\Account;

use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountFeaturesServiceInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Account\AccountFeatures;

class AccountFeaturesAction implements AccountFeaturesActionInterface
{
    /**
     * @var AccountFeaturesServiceInterface
     */
    private readonly AccountFeaturesServiceInterface $accountFeaturesService;
    /**
     * @var CreateAccountCredentialsActionInterface
     */
    private readonly CreateAccountCredentialsActionInterface $createAccountCredentialsAction;

    /**
     * @param AccountFeaturesServiceInterface $accountFeaturesService
     * @param CreateAccountCredentialsActionInterface $createAccountCredentialsAction
     */
    public function __construct(
        AccountFeaturesServiceInterface $accountFeaturesService,
        CreateAccountCredentialsActionInterface $createAccountCredentialsAction,
    ) {
        $this->accountFeaturesService = $accountFeaturesService;
        $this->createAccountCredentialsAction = $createAccountCredentialsAction;
    }

    /**
     * @param AccountInterface $account
     *
     * @return AccountFeatures
     * @throws ValidationException
     * @throws BadRequestException
     * @throws BadResponseException
     */
    public function execute(AccountInterface $account): AccountFeatures
    {
        $accountCredentials = $this->createAccountCredentialsAction->execute(
            apiKey: $account->getJsApiKey(),
            authKey: $account->getRestAuthKey(),
        );

        return $this->accountFeaturesService->execute($accountCredentials);
    }
}
