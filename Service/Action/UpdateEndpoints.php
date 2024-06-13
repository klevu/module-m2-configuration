<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action;

use Klevu\Configuration\Exception\Integration\InvalidEndpointException;
use Klevu\Configuration\Service\Provider\Sdk\BaseUrlsProvider;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriter;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class UpdateEndpoints implements UpdateEndpointsInterface
{
    public const CONFIG_XML_PATH_URL_ANALYTICS = BaseUrlsProvider::CONFIG_XML_PATH_URL_ANALYTICS;
    public const CONFIG_XML_PATH_URL_CAT_NAV = BaseUrlsProvider::CONFIG_XML_PATH_URL_CAT_NAV;
    public const CONFIG_XML_PATH_URL_INDEXING = BaseUrlsProvider::CONFIG_XML_PATH_URL_INDEXING;
    public const CONFIG_XML_PATH_URL_JS = BaseUrlsProvider::CONFIG_XML_PATH_URL_JS;
    public const CONFIG_XML_PATH_URL_SEARCH = BaseUrlsProvider::CONFIG_XML_PATH_URL_SEARCH;
    public const CONFIG_XML_PATH_URL_TIERS = BaseUrlsProvider::CONFIG_XML_PATH_URL_TIERS;

    /**
     * @var ScopeConfigWriter
     */
    private readonly ScopeConfigWriter $scopeConfigWriter;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;
    /**
     * @var ReinitableConfigInterface
     */
    private readonly ReinitableConfigInterface $reinitableConfig;
    /**
     * @var string[]
     */
    private array $endpoints = [
        'getAnalyticsUrl' => self::CONFIG_XML_PATH_URL_ANALYTICS,
        'getSmartCategoryMerchandisingUrl' => self::CONFIG_XML_PATH_URL_CAT_NAV,
        'getIndexingUrl' => self::CONFIG_XML_PATH_URL_INDEXING,
        'getJsUrl' => self::CONFIG_XML_PATH_URL_JS,
        'getSearchUrl' => self::CONFIG_XML_PATH_URL_SEARCH,
        'getTiersUrl' => self::CONFIG_XML_PATH_URL_TIERS,
    ];

    /**
     * @param ScopeConfigWriter $scopeConfigWriter
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        ScopeConfigWriter $scopeConfigWriter,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        ReinitableConfigInterface $reinitableConfig,
    ) {
        $this->scopeConfigWriter = $scopeConfigWriter;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * @param AccountInterface $account
     * @param int $scope
     * @param string $scopeType
     *
     * @return void
     */
    public function execute(AccountInterface $account, int $scope, string $scopeType): void
    {
        foreach ($this->endpoints as $method => $xmlPath) {
            try {
                $endpoint = $account->{$method}();
                $this->saveEndpoint($xmlPath, $scope, $scopeType, $endpoint);
            } catch (InvalidEndpointException $exception) {
                $this->logger->error(
                    message: 'Method: {method} - Error: {message}',
                    context: [
                        'method' => __METHOD__,
                        'message' => $exception->getMessage(),
                    ],
                );
            }
        }
        $this->reinitableConfig->reinit();
    }

    /**
     * @param string $xmlPath
     * @param int $scope
     * @param string $scopeType
     * @param string|null $endpoint
     *
     * @return void
     * @throws InvalidEndpointException
     */
    private function saveEndpoint(
        string $xmlPath,
        int $scope,
        string $scopeType,
        ?string $endpoint = null,
    ): void {
        if ($this->storeManager->isSingleStoreMode()) {
            $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scope = 0;
        }
        $this->validateEndpoint($endpoint);
        $this->scopeConfigWriter->save(
            $xmlPath,
            $endpoint,
            $scopeType,
            $scope,
        );
    }

    /**
     * @param string|null $endpoint
     *
     * @return void
     * @throws InvalidEndpointException
     */
    private function validateEndpoint(?string $endpoint): void
    {
        if (null === $endpoint) {
            return;
        }
        $urlToValidate = 'https://' . $endpoint;
        if (filter_var($urlToValidate, FILTER_VALIDATE_URL)) {
            return;
        }
        throw new InvalidEndpointException(
            __(
                'Supplied Endpoint URl is invalid. Received %1',
                $endpoint,
            ),
        );
    }
}
