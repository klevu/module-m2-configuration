<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\WebApi\Integration;

use Klevu\Configuration\Api\Data\ApiResponseInterface;
use Klevu\Configuration\Api\Data\ApiResponseInterfaceFactory;
use Klevu\Configuration\Api\RemoveApiKeysInterface;
use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Configuration\Service\RemoveApiKeysServiceInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class RemoveApiKeys implements RemoveApiKeysInterface
{
    /**
     * @var RemoveApiKeysServiceInterface
     */
    private RemoveApiKeysServiceInterface $removeApiKeysService;
    /**
     * @var ApiResponseInterfaceFactory
     */
    private ApiResponseInterfaceFactory $responseFactory;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var ScopeProviderInterface
     */
    private ScopeProviderInterface $scopeProvider;

    /**
     * @param RemoveApiKeysServiceInterface $removeApiKeysService
     * @param ApiResponseInterfaceFactory $responseFactory
     * @param LoggerInterface $logger
     * @param ScopeProviderInterface $scopeProvider
     */
    public function __construct(
        RemoveApiKeysServiceInterface $removeApiKeysService,
        ApiResponseInterfaceFactory $responseFactory,
        LoggerInterface $logger,
        ScopeProviderInterface $scopeProvider,
    ) {
        $this->removeApiKeysService = $removeApiKeysService;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
        $this->scopeProvider = $scopeProvider;
    }

    /**
     * @param int $scopeId
     * @param string|null $scopeType
     *
     * @return ApiResponseInterface
     */
    public function execute(
        int $scopeId,
        ?string $scopeType = ScopeInterface::SCOPE_STORES,
    ): ApiResponseInterface {
        try {
            $this->scopeProvider->setCurrentScopeById($scopeId, $scopeType);
            $this->removeApiKeysService->execute(
                scopeId: $scopeId,
                scopeType: $scopeType,
            );
            $return = [
                'status' => 'success',
                'messages' => [
                    __(
                        'Account removed for %1: %2.',
                        $scopeType,
                        $scopeId,
                    ),
                ],
                'code' => 200,
            ];
        } catch (\Throwable $exception) {
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
    private function createResponse(array $data): ApiResponseInterface
    {
        $response = $this->responseFactory->create();
        $response->setStatus(status: $data['status'] ?? '');
        $response->setCode(code: $data['code'] ?? '500');
        $response->setData(data: $data['data'] ?? []);
        $response->setMessages(messages: $data['messages'] ?? []);

        return $response;
    }

    /**
     * @param \Throwable $exception
     *
     * @return void
     */
    private function logError(
        \Throwable $exception,
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
}
