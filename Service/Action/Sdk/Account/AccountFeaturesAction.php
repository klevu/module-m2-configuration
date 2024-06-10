<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action\Sdk\Account;

use Klevu\Configuration\Service\Provider\Sdk\BaseUrlsProviderFactory;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountFeaturesServiceInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountFeaturesServiceInterfaceFactory;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;

class AccountFeaturesAction implements AccountFeaturesActionInterface
{
    /**
     * @var AccountFeaturesServiceInterfaceFactory
     */
    private readonly AccountFeaturesServiceInterfaceFactory $accountFeaturesServiceFactory;
    /**
     * @var CreateAccountCredentialsActionInterface
     */
    private readonly CreateAccountCredentialsActionInterface $createAccountCredentialsAction;
    /**
     * @var BaseUrlsProviderFactory
     */
    private readonly BaseUrlsProviderFactory $baseUrlProviderFactory;

    /**
     * @param AccountFeaturesServiceInterfaceFactory $accountFeaturesServiceFactory
     * @param CreateAccountCredentialsActionInterface $createAccountCredentialsAction
     * @param BaseUrlsProviderFactory $baseUrlProviderFactory
     */
    public function __construct(
        AccountFeaturesServiceInterfaceFactory $accountFeaturesServiceFactory,
        CreateAccountCredentialsActionInterface $createAccountCredentialsAction,
        BaseUrlsProviderFactory $baseUrlProviderFactory,
    ) {
        $this->accountFeaturesServiceFactory = $accountFeaturesServiceFactory;
        $this->createAccountCredentialsAction = $createAccountCredentialsAction;
        $this->baseUrlProviderFactory = $baseUrlProviderFactory;
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
        $accountFeaturesService = $this->getFeaturesService($account);

        return $accountFeaturesService->execute($accountCredentials);
    }

    /**
     * @param AccountInterface $account
     *
     * @return AccountFeaturesServiceInterface
     */
    private function getFeaturesService(AccountInterface $account): AccountFeaturesServiceInterface
    {
        /** @var BaseUrlsProviderInterface $baseUrlProvider */
        $baseUrlProvider = $this->baseUrlProviderFactory->create([
            'account' => $account,
        ]);
        /** @var AccountFeaturesServiceInterface $accountFeaturesService */
        $accountFeaturesService = $this->accountFeaturesServiceFactory->create([
            'baseUrlsProvider' => $baseUrlProvider,
        ]);

        return $accountFeaturesService;
    }
}
