<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider\Sdk;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\Indexing\IndexingVersions;
use Magento\Framework\App\Config\ScopeConfigInterface;

class BaseUrlsProvider implements BaseUrlsProviderInterface
{
    public const CONFIG_XML_PATH_URL_ANALYTICS = 'klevu_configuration/developer/url_analytics';
    public const CONFIG_XML_PATH_URL_API = 'klevu_configuration/developer/url_api';
    public const CONFIG_XML_PATH_URL_CAT_NAV = 'klevu_configuration/developer/url_cat_nav';
    public const CONFIG_XML_PATH_URL_HOSTNAME = 'klevu_configuration/developer/url_hostname';
    public const CONFIG_XML_PATH_URL_INDEXING = 'klevu_configuration/developer/url_indexing';
    public const CONFIG_XML_PATH_URL_JS = 'klevu_configuration/developer/url_js';
    public const CONFIG_XML_PATH_URL_SEARCH = 'klevu_configuration/developer/url_search';
    public const CONFIG_XML_PATH_URL_TIERS = 'klevu_configuration/developer/url_tiers';

    /**
     * @var BaseUrlsProviderInterface
     */
    private readonly BaseUrlsProviderInterface $fallbackBaseUrlsProvider;
    /**
     * @var ScopeProviderInterface
     */
    private readonly ScopeProviderInterface $scopeProvider;
    /**
     * @var ScopeConfigInterface
     */
    private readonly ScopeConfigInterface $scopeConfig;

    /**
     * @param BaseUrlsProviderInterface $fallbackBaseUrlsProvider
     * @param ScopeProviderInterface $scopeProvider
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        BaseUrlsProviderInterface $fallbackBaseUrlsProvider,
        ScopeProviderInterface $scopeProvider,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->fallbackBaseUrlsProvider = $fallbackBaseUrlsProvider;
        $this->scopeProvider = $scopeProvider;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        $scope = $this->scopeProvider->getCurrentScope();

        return $this->scopeConfig->getValue(
            static::CONFIG_XML_PATH_URL_API,
            $scope->getScopeType(),
            $scope->getScopeId(),
        ) ?: $this->fallbackBaseUrlsProvider->getApiUrl();
    }

    /**
     * @return string
     */
    public function getAnalyticsUrl(): string
    {
        $scope = $this->scopeProvider->getCurrentScope();

        return $this->scopeConfig->getValue(
            static::CONFIG_XML_PATH_URL_ANALYTICS,
            $scope->getScopeType(),
            $scope->getScopeId(),
        ) ?: $this->fallbackBaseUrlsProvider->getAnalyticsUrl();
    }

    /**
     * @return string|null
     */
    public function getSmartCategoryMerchandisingUrl(): ?string
    {
        $scope = $this->scopeProvider->getCurrentScope();

        return $this->scopeConfig->getValue(
            static::CONFIG_XML_PATH_URL_CAT_NAV,
            $scope->getScopeType(),
            $scope->getScopeId(),
        ) ?: $this->fallbackBaseUrlsProvider->getSmartCategoryMerchandisingUrl();
    }

    /**
     * @return string
     */
    public function getMerchantCenterUrl(): string
    {
        $scope = $this->scopeProvider->getCurrentScope();

        return $this->scopeConfig->getValue(
            static::CONFIG_XML_PATH_URL_HOSTNAME,
            $scope->getScopeType(),
            $scope->getScopeId(),
        ) ?: $this->fallbackBaseUrlsProvider->getMerchantCenterUrl();
    }

    /**
     * @param IndexingVersions $version
     * @return string
     */
    public function getIndexingUrl(IndexingVersions $version = IndexingVersions::XML): string
    {
        $scope = $this->scopeProvider->getCurrentScope();

        $indexingUrl = $this->scopeConfig->getValue(
            static::CONFIG_XML_PATH_URL_INDEXING,
            $scope->getScopeType(),
            $scope->getScopeId(),
        ) ?: $this->fallbackBaseUrlsProvider->getIndexingUrl(
            version: $version,
        );

        $urlRoutePrefix = $version->getUrlRoutePrefix();
        if (!str_ends_with($indexingUrl, $urlRoutePrefix)) {
            $indexingUrl .= $urlRoutePrefix;
        }

        return $indexingUrl;
    }

    /**
     * @return string
     */
    public function getJsUrl(): string
    {
        $scope = $this->scopeProvider->getCurrentScope();

        return $this->scopeConfig->getValue(
            static::CONFIG_XML_PATH_URL_JS,
            $scope->getScopeType(),
            $scope->getScopeId(),
        ) ?: $this->fallbackBaseUrlsProvider->getJsUrl();
    }

    /**
     * @return string|null
     */
    public function getSearchUrl(): ?string
    {
        $scope = $this->scopeProvider->getCurrentScope();

        return $this->scopeConfig->getValue(
            static::CONFIG_XML_PATH_URL_SEARCH,
            $scope->getScopeType(),
            $scope->getScopeId(),
        ) ?: $this->fallbackBaseUrlsProvider->getSearchUrl();
    }

    /**
     * @return string
     */
    public function getTiersUrl(): string
    {
        $scope = $this->scopeProvider->getCurrentScope();

        return $this->scopeConfig->getValue(
            static::CONFIG_XML_PATH_URL_TIERS,
            $scope->getScopeType(),
            $scope->getScopeId(),
        ) ?: $this->fallbackBaseUrlsProvider->getTiersUrl();
    }
}
