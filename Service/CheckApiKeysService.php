<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service;

use Klevu\Configuration\Exception\Integration\InactiveAccountException;
use Klevu\Configuration\Exception\Integration\InvalidPlatformException;
use Klevu\Configuration\Service\Action\Sdk\Account\AccountLookupActionInterface;
use Klevu\Configuration\Service\Action\Sdk\Account\CreateAccountCredentialsActionInterface;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;

class CheckApiKeysService implements CheckApiKeysServiceInterface
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
     * @param CreateAccountCredentialsActionInterface $createAccountCredentialsAction
     * @param AccountLookupActionInterface $accountLookupAction
     */
    public function __construct(
        CreateAccountCredentialsActionInterface $createAccountCredentialsAction,
        AccountLookupActionInterface $accountLookupAction,
    ) {
        $this->createAccountCredentialsAction = $createAccountCredentialsAction;
        $this->accountLookupAction = $accountLookupAction;
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

        return $this->accountLookupAction->execute(accountCredentials: $accountCredentials);
    }
}
