<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\ViewModel\Config\Integration\Removal;

use Klevu\Configuration\Service\Provider\Stores\Config\AuthKeysCollectionProviderInterface;
use Klevu\Configuration\ViewModel\MessageInterface;
use Magento\Framework\App\RequestInterface;
// @todo remove phpcs suppression when channels available
use Magento\Framework\Exception\NoSuchEntityException; // phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse,Generic.Files.LineLength.TooLong
use Magento\Framework\Phrase;
use Magento\Store\Model\ScopeInterface; // phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse,Generic.Files.LineLength.TooLong
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Messages implements MessageInterface
{
    /**
     * @var RequestInterface
     */
    private readonly RequestInterface $request;
    /**
     * @var AuthKeysCollectionProviderInterface
     */
    // phpcs:ignore Generic.Files.LineLength.TooLong
    private readonly AuthKeysCollectionProviderInterface $authKeysCollectionProvider; // @phpstan-ignore-line Remove phpstan ignore when channels available
    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;
    /**
     * @var LoggerInterface
     */
    // phpcs:ignore Generic.Files.LineLength.TooLong
    private readonly LoggerInterface $logger; // @phpstan-ignore-line Remove phpstan ignore when channels available
    /**
     * @var Phrase[][]
     */
    private array $messages = [];

    /**
     * @param RequestInterface $request
     * @param AuthKeysCollectionProviderInterface $authKeysCollectionProvider
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        AuthKeysCollectionProviderInterface $authKeysCollectionProvider,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
    ) {
        $this->request = $request;
        $this->authKeysCollectionProvider = $authKeysCollectionProvider;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @return Phrase[][]
     */
    public function getMessages(): array
    {
        $scope = $this->request->getParam(key: 'scope');
        // $scopeId = $this->request->getParam(key: 'scope_id'); // @TODO add when channels are available

        if (!$this->storeManager->isSingleStoreMode()) {
            $this->messages['warning'][] = __(
                "Warning: This action will remove your integration at '%1' scope.",
                $scope,
            );

            // @TODO add when channels are available
//        if ($this->isStoreScope(scope: $scope)) {
//            if (!$this->isWebsiteIntegrated(scopeId: (int)$scopeId)) {
//                $this->messages['warning'][] = __(
//                    "Warning: There are no fallback auth keys set at '%1' scope for this store.",
//                    ScopeInterface::SCOPE_WEBSITES,
//                );
//            } else {
//                $this->messages['info'][] = __(
//                    "Info: The fallback auth keys set at '%1' scope will be used for this store.",
//                    ScopeInterface::SCOPE_WEBSITES,
//                );
//            }
//        }
        }

        return $this->messages;
    }

    /**
     * @param int $scopeId
     *
     * @return bool
     */
    // @todo reinstate when channels are available
//    private function isWebsiteIntegrated(int $scopeId): bool
//    {
//        try {
//            $store = $this->storeManager->getStore($scopeId);
//        } catch (NoSuchEntityException $exception) {
//            $this->logger->error(
//                message: 'Method: {method} - Error: {message}',
//                context: [
//                    'method' => __METHOD__,
//                    'message' => $exception->getMessage(),
//                ],
//            );
//
//            return false;
//        }
//        $collection = $this->authKeysCollectionProvider->get(
//            filter: [
//                'scope' => ScopeInterface::SCOPE_WEBSITES,
//                'scope_id' => $store->getWebsiteId(),
//            ],
//        );
//
//        return $collection->getSize() >= 2;
//    }

    /**
     * @param mixed $scope
     *
     * @return bool
     */
    // @todo reinstate when channels are available
//    private function isStoreScope(mixed $scope): bool
//    {
//        return in_array(
//            needle: $scope,
//            haystack: [ScopeInterface::SCOPE_STORE, ScopeInterface::SCOPE_STORES],
//            strict: true,
//        );
//    }
}
