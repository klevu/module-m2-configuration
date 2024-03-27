<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action\Sdk\Account;

use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Model\AccountCredentialsFactory;

class CreateAccountCredentialsAction implements CreateAccountCredentialsActionInterface
{
    /**
     * @var AccountCredentialsFactory
     */
    private readonly AccountCredentialsFactory $accountCredentialsFactory;

    /**
     * @param AccountCredentialsFactory $accountCredentialsFactory
     */
    public function __construct(
        AccountCredentialsFactory $accountCredentialsFactory,
    ) {
        $this->accountCredentialsFactory = $accountCredentialsFactory;
    }

    /**
     * @param string $apiKey
     * @param string $authKey
     *
     * @return AccountCredentials
     * @throws InvalidTypeValidationException
     */
    public function execute(string $apiKey, string $authKey): AccountCredentials
    {
        return $this->accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $apiKey,
                'restAuthKey' => $authKey,
            ],
        );
    }
}
