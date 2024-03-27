<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\WebApi\Integration;

use Klevu\Configuration\Api\Data\ApiResponseInterface;
use Klevu\Configuration\Api\Data\ApiResponseInterfaceFactory;
use Klevu\Configuration\Api\IntegrateApiKeysInterface;
use Klevu\Configuration\Exception\Integration\InactiveAccountException;
use Klevu\Configuration\Exception\Integration\InvalidPlatformException;
use Klevu\Configuration\Exception\Integration\InvalidScopeException;
use Klevu\Configuration\Service\IntegrateApiKeysServiceInterface;
use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class IntegrateApiKeys implements IntegrateApiKeysInterface
{
    /**
     * @var IntegrateApiKeysServiceInterface
     */
    private readonly IntegrateApiKeysServiceInterface $integrateApiKeysService;
    /**
     * @var ApiResponseInterfaceFactory
     */
    private readonly ApiResponseInterfaceFactory $responseFactory;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var ScopeProviderInterface
     */
    private ScopeProviderInterface $scopeProvider;

    /**
     * @param IntegrateApiKeysServiceInterface $integrateApiKeysService
     * @param ApiResponseInterfaceFactory $responseFactory
     * @param LoggerInterface $logger
     * @param ScopeProviderInterface $scopeProvider
     */
    public function __construct(
        IntegrateApiKeysServiceInterface $integrateApiKeysService,
        ApiResponseInterfaceFactory $responseFactory,
        LoggerInterface $logger,
        ScopeProviderInterface $scopeProvider,
    ) {
        $this->integrateApiKeysService = $integrateApiKeysService;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
        $this->scopeProvider = $scopeProvider;
    }

    /**
     * @param string $apiKey
     * @param string $authKey
     * @param int $scopeId
     * @param string|null $scopeType
     *
     * @return ApiResponseInterface
     */
    public function execute(
        string $apiKey,
        string $authKey,
        int $scopeId,
        ?string $scopeType = ScopeInterface::SCOPE_STORES,
    ): ApiResponseInterface {
        try {
            $this->scopeProvider->setCurrentScopeById($scopeId, $scopeType);
            $account = $this->integrateApiKeysService->execute(
                apiKey: $apiKey,
                authKey: $authKey,
                scopeId: $scopeId,
                scopeType: $scopeType,
            );
            $return = [
                'status' => 'success',
                'messages' => [
                    __(
                        'Account integrated for %1: %2. Account is %3 for %4.',
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
        } catch (AccountNotFoundException $exception) {
            $return = [
                'status' => 'error',
                'messages' => [__('Account Not Found: %1.', $exception->getMessage())],
                'code' => 404,
            ];
        } catch (InvalidPlatformException | InactiveAccountException | InvalidScopeException $exception) {
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
                'messages' => [__('Internal error: See log for details')],
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
    private function createResponse(array $data,): ApiResponseInterface
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
     * @param \Exception $exception
     *
     * @return void
     */
    private function logError(\Exception $exception): void
    {
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
}
