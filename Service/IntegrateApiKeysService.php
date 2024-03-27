<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service;

use Klevu\Configuration\Exception\AccountCacheScopeException;
use Klevu\Configuration\Exception\Integration\InactiveAccountException;
use Klevu\Configuration\Exception\Integration\InvalidPlatformException;
use Klevu\Configuration\Exception\Integration\InvalidScopeException;
use Klevu\Configuration\Service\Action\CacheAccountActionInterface;
use Klevu\Configuration\Service\Action\Sdk\AccountDetailsActionInterface;
use Klevu\Configuration\Service\Action\UpdateEndpointsInterface;
use Klevu\Configuration\Service\Provider\ApiKeyProvider;
use Klevu\Configuration\Service\Provider\AuthKeyProvider;
use Klevu\Configuration\Validator\ValidatorInterface;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriter;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class IntegrateApiKeysService implements IntegrateApiKeysServiceInterface
{
    /**
     * @var AccountDetailsActionInterface
     */
    private readonly AccountDetailsActionInterface $accountDetailsAction;
    /**
     * @var CacheAccountActionInterface
     */
    private readonly CacheAccountActionInterface $cacheAccountAction;
    /**
     * @var ScopeConfigWriter
     */
    private readonly ScopeConfigWriter $scopeConfigWriter;
    /**
     * @var UpdateEndpointsInterface
     */
    private readonly UpdateEndpointsInterface $updateEndPoints;
    /**
     * @var ReinitableConfigInterface
     */
    private readonly ReinitableConfigInterface $reinitableConfig;
    /**
     * @var EventManagerInterface
     */
    private readonly EventManagerInterface $eventManager;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $scopeValidator;

    /**
     * @param AccountDetailsActionInterface $accountDetailsAction
     * @param CacheAccountActionInterface $cacheAccountAction
     * @param ScopeConfigWriter $scopeConfigWriter
     * @param UpdateEndpointsInterface $updateEndPoints
     * @param ReinitableConfigInterface $reinitableConfig
     * @param EventManagerInterface $eventManager
     * @param ValidatorInterface $scopeValidator
     */
    public function __construct(
        AccountDetailsActionInterface $accountDetailsAction,
        CacheAccountActionInterface $cacheAccountAction,
        ScopeConfigWriter $scopeConfigWriter,
        UpdateEndpointsInterface $updateEndPoints,
        ReinitableConfigInterface $reinitableConfig,
        EventManagerInterface $eventManager,
        ValidatorInterface $scopeValidator,
    ) {
        $this->accountDetailsAction = $accountDetailsAction;
        $this->cacheAccountAction = $cacheAccountAction;
        $this->scopeConfigWriter = $scopeConfigWriter;
        $this->updateEndPoints = $updateEndPoints;
        $this->reinitableConfig = $reinitableConfig;
        $this->eventManager = $eventManager;
        $this->scopeValidator = $scopeValidator;
    }

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
    ): AccountInterface {
        $this->validateScope(scopeType: $scopeType);
        $account = $this->accountDetailsAction->execute(
            apiKey: $apiKey,
            authKey: $authKey,
        );
        $this->saveKeys(
            account: $account,
            scopeId: $scopeId,
            scopeType: $scopeType,
        );
        $this->updateEndPoints->execute(
            account: $account,
            scope: $scopeId,
            scopeType: $scopeType,
        );
        $this->cacheAccountAction->execute(
            accountFeatures: $account->getAccountFeatures(),
            scopeId: $scopeId,
            scopeType: $scopeType,
        );
        $this->eventManager->dispatch(
            'klevu_integrate_api_keys_after',
            [
                'apiKey' => $apiKey,
            ],
        );

        return $account;
    }

    /**
     * @param AccountInterface $account
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return void
     */
    private function saveKeys(
        AccountInterface $account,
        int $scopeId,
        string $scopeType,
    ): void {
        $this->scopeConfigWriter->save(
            path: ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY,
            value: $account->getJsApiKey(),
            scope: $scopeType,
            scopeId: $scopeId,
        );
        $this->scopeConfigWriter->save(
            path: AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY,
            value: $account->getRestAuthKey(),
            scope: $scopeType,
            scopeId: $scopeId,
        );
        $this->reinitableConfig->reinit();
    }

    /**
     * @param string $scopeType
     *
     * @return void
     * @throws InvalidScopeException
     */
    private function validateScope(string $scopeType): void
    {
        if ($this->scopeValidator->isValid($scopeType)) {
            return;
        }
        throw new InvalidScopeException(
            phrase: __(
                'Scope Validation Exception: %1',
                implode(separator: '; ', array: $this->scopeValidator->getMessages()),
            ),
        );
    }
}
