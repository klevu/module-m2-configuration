<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Config\ScopeInterface as ConfigScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;

class WebsiteScopeProvider implements WebsiteScopeProviderInterface
{
    private const ADMIN_WEBSITE_ID_PARAM = 'website_id';

    /**
     * @var WebsiteInterface|null
     */
    private ?WebsiteInterface $currentWebsite;
    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;
    /**
     * @var AppState
     */
    private readonly AppState $appState;
    /**
     * @var ConfigScopeInterface
     */
    private readonly ConfigScopeInterface $configScope;
    /**
     * @var RequestInterface
     */
    private readonly RequestInterface $request;

    /**
     * @param StoreManagerInterface $storeManager
     * @param AppState $appState
     * @param ConfigScopeInterface $configScope
     * @param RequestInterface $request
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        AppState $appState,
        ConfigScopeInterface $configScope,
        RequestInterface $request,
    ) {
        $this->storeManager = $storeManager;
        $this->appState = $appState;
        $this->configScope = $configScope;
        $this->request = $request;
    }

    /**
     * @return WebsiteInterface|null
     */
    public function getCurrentWebsite(): ?WebsiteInterface
    {
        return $this->currentWebsite ??= $this->resolveCurrentWebsite();
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return void
     */
    public function setCurrentWebsite(WebsiteInterface $website): void
    {
        $this->currentWebsite = $website;
    }

    /**
     * @param string $websiteCode
     *
     * @return void
     * @throws LocalizedException
     */
    public function setCurrentWebsiteByCode(string $websiteCode): void
    {
        $this->setCurrentWebsite(
            $this->storeManager->getWebsite($websiteCode),
        );
    }

    /**
     * @param int $websiteId
     *
     * @return void
     * @throws LocalizedException
     */
    public function setCurrentWebsiteById(int $websiteId): void
    {
        $this->setCurrentWebsite(
            $this->storeManager->getWebsite($websiteId),
        );
    }

    /**
     * @return void
     */
    public function unsetCurrentWebsite(): void
    {
        $this->currentWebsite = null;
    }

    /**
     * @return WebsiteInterface|null
     */
    private function resolveCurrentWebsite(): ?WebsiteInterface
    {
        try {
            $areaCode = $this->appState->getAreaCode();
            if (!$areaCode) {
                $areaCode = $this->configScope->getCurrentScope();
            }
        } catch (LocalizedException) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            // cannot log error here as this class is used in the logger
        }

        $website = null;
        try {
            switch ($areaCode ?? Area::AREA_FRONTEND) {
                case Area::AREA_CRONTAB:
                case Area::AREA_GLOBAL:
                case Area::AREA_GRAPHQL:
                case Area::AREA_WEBAPI_REST:
                case Area::AREA_WEBAPI_SOAP:
                    break;

                case Area::AREA_ADMINHTML:
                    $website = $this->resolveCurrentAdminhtmlWebsite();
                    break;

                default:
                    $website = $this->storeManager->getWebsite();

                    break;
            }
        } catch (LocalizedException) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            // cannot log error here as this class is used in the logger
        }

        return $website;
    }

    /**
     * @return WebsiteInterface|null
     * @throws LocalizedException
     */
    private function resolveCurrentAdminhtmlWebsite(): ?WebsiteInterface
    {
        $websiteId = $this->getWebsiteIdFromRequestParams();
        if (!$websiteId) {
            return null;
        }

        return $this->storeManager->getWebsite($websiteId);
    }

    /**
     * @return int|null
     */
    private function getWebsiteIdFromRequestParams(): ?int
    {
        $paramKeys = array_unique([
            'website',
            self::ADMIN_WEBSITE_ID_PARAM,
        ]);
        $params = $this->request->getParams();
        $websiteParams = array_filter(
            array: $params,
            callback: static fn (string $param): bool => in_array($param, $paramKeys, true),
            mode: ARRAY_FILTER_USE_KEY,
        );
        if (!$websiteParams) {
            return null;
        }
        $keys = array_keys($websiteParams);

        return $websiteParams[$keys[0]]
            ? (int)$websiteParams[$keys[0]]
            : null;
    }
}
