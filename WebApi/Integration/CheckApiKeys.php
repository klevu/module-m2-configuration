<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\WebApi\Integration;

use Klevu\Configuration\Api\CheckApiKeysInterface;
use Klevu\Configuration\Api\Data\ApiResponseInterface;
use Klevu\Configuration\Api\Data\ApiResponseInterfaceFactory;
use Klevu\Configuration\Exception\Integration\InactiveAccountException;
use Klevu\Configuration\Exception\Integration\InvalidPlatformException;
use Klevu\Configuration\Service\CheckApiKeysServiceInterface;
use Klevu\Configuration\Service\Provider\OtherIntegratedScopesProviderInterface;
use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Psr\Log\LoggerInterface;

class CheckApiKeys implements CheckApiKeysInterface
{
    /**
     * @var CheckApiKeysServiceInterface
     */
    private readonly CheckApiKeysServiceInterface $apiKeysService;
    /**
     * @var ApiResponseInterfaceFactory
     */
    private readonly ApiResponseInterfaceFactory $responseFactory;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var OtherIntegratedScopesProviderInterface
     */
    private readonly OtherIntegratedScopesProviderInterface $otherIntegratedScopesProvider;
    /**
     * @var ScopeProviderInterface
     */
    private ScopeProviderInterface $scopeProvider;

    /**
     * @param CheckApiKeysServiceInterface $apiKeysService
     * @param ApiResponseInterfaceFactory $responseFactory
     * @param LoggerInterface $logger
     * @param OtherIntegratedScopesProviderInterface $otherIntegratedScopesProvider
     * @param ScopeProviderInterface $scopeProvider
     */
    public function __construct(
        CheckApiKeysServiceInterface $apiKeysService,
        ApiResponseInterfaceFactory $responseFactory,
        LoggerInterface $logger,
        OtherIntegratedScopesProviderInterface $otherIntegratedScopesProvider,
        ScopeProviderInterface $scopeProvider,
    ) {
        $this->apiKeysService = $apiKeysService;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
        $this->otherIntegratedScopesProvider = $otherIntegratedScopesProvider;
        $this->scopeProvider = $scopeProvider;
    }

    /**
     * @param string $apiKey
     * @param string $authKey
     * @param int|null $scopeId
     * @param string|null $scopeType
     * @param int|null $loggerScopeId
     *
     * @return ApiResponseInterface
     */
    public function execute(
        string $apiKey,
        string $authKey,
        ?int $scopeId = null,
        ?string $scopeType = null,
        ?int $loggerScopeId = null,
    ): ApiResponseInterface {
        try {
            $loggerScopeId = $loggerScopeId ?: $scopeId;
            if ((null !== $loggerScopeId && null !== $scopeType)) {
                $this->scopeProvider->setCurrentScopeById($loggerScopeId, $scopeType);
            }
            $account = $this->apiKeysService->execute(apiKey: $apiKey, authKey: $authKey);
            $return = $this->getSuccessReturn($account);
            if ((null !== $scopeId && null !== $scopeType)) {
                $return = $this->addAlreadyIntegratedWarning($scopeId, $scopeType, $authKey, $apiKey, $return);
            }
        } catch (AccountNotFoundException $exception) {
            $return = [
                'status' => 'error',
                'messages' => [__('Account Not Found: %1.', $exception->getMessage())],
                'code' => 404,
            ];
        } catch (InvalidPlatformException | InactiveAccountException $exception) {
            $return = [
                'status' => 'error',
                'messages' => [__('Validation Error: %1', $exception->getMessage())],
                'code' => 400,
            ];
        } catch (ValidationException $exception) {
            $errorsString = implode(separator: '; ', array: $exception->getErrors());
            $return = [
                'status' => 'error',
                'messages' => [__('Validation Errors: %1', $errorsString)],
                'code' => 400,
            ];
        } catch (BadRequestException $exception) {
            $return = [
                'status' => 'error',
                'messages' => [__('The request is invalid and was rejected by Klevu.')],
                'code' => 400,
            ];
        } catch (BadResponseException $exception) {
            $return = [
                'status' => 'error',
                'messages' => [__('The Klevu API did not respond in an expected manner.')],
                'code' => 500,
            ];
        } catch (\Exception $exception) {
            $return = [
                'status' => 'error',
                'message' => __('Internal error: See log for details'),
                'code' => 500,
            ];
        } finally {
            if (isset($exception)) {
                $this->logError($exception);
            }
        }

        return $this->createResponse(data: $return);
    }

    /**
     * @param mixed[] $data
     *
     * @return ApiResponseInterface
     */
    private function createResponse(array $data): ApiResponseInterface
    {
        /** @var ApiResponseInterface $response */
        $response = $this->responseFactory->create();
        $response->setStatus(status: $data['status'] ?? '');
        $response->setCode(code: $data['code'] ?? '500');
        $response->setData(data: $data['data'] ?? []);
        $response->setMessages(messages: $data['messages'] ?? []);

        return $response;
    }

    /**
     * @param int $scopeId
     * @param string $scopeType
     * @param string $authKey
     * @param string $apiKey
     * @param mixed[] $return
     *
     * @return mixed[]
     */
    private function addAlreadyIntegratedWarning(
        int $scopeId,
        string $scopeType,
        string $authKey,
        string $apiKey,
        array $return,
    ): array {
        $integratedScopes = $this->otherIntegratedScopesProvider->get(
            apiKey: $apiKey,
            authKey: $authKey,
            scopeId: $scopeId,
            scopeType: $scopeType,
        );
        if ($integratedScopes) {
            $return['messages'][] = __(
                'Auth Key is already integrated at the following scopes: %1',
                implode(', ', $integratedScopes),
            );
        }

        return $return;
    }

    /**
     * @param \Exception $exception
     *
     * @return void
     */
    private function logError(
        \Exception $exception,
    ): void {
        $errors = method_exists(object_or_class: $exception, method: 'getErrors')
            ? $exception->getErrors()
            : [];
        $errorsString = $errors
            ? sprintf(': Errors [%s]', implode(separator: '; ', array: $errors))
            : '';

        $this->logger->error(
            message: 'Method: {method} - Error: {message}',
            context: [
                'exception' => $exception::class,
                'method' => __METHOD__,
                'message' => $exception->getMessage() . $errorsString,
            ],
        );
    }

    /**
     * @param AccountInterface $account
     *
     * @return mixed[]
     */
    private function getSuccessReturn(AccountInterface $account): array
    {
        return [
            'status' => 'success',
            'data' => [
                'account' => [
                    'email' => $account->getEmail(),
                    'company' => $account->getCompanyName(),
                    'active' => $account->isActive(),
                    'platform' => ucwords($account->getPlatform()),
                    'apiKey' => $account->getJsApiKey(),
                    'authKey' => $account->getRestAuthKey(),
                    'indexingVersion' => $account->getIndexingVersion(),
                ],
            ],
            'messages' => [
                __(
                    'Account retrieved for %1: %2. Account is %3 for %4.',
                    $account->getCompanyName(),
                    $account->getEmail(),
                    $account->isActive()
                        ? __('active')
                        : __('disabled'),
                    ucwords(str_replace(['-', '_'], ' ', $account->getPlatform())),
                ),
            ],
            'code' => 200,
        ];
    }
}
