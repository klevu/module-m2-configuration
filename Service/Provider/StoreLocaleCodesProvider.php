<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Magento\Config\Model\Config\Backend\Admin\Custom as ConfigBackendCustom;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

class StoreLocaleCodesProvider implements StoreLocaleCodesProviderInterface
{
    /**
     * @var StoresProviderInterface
     */
    private StoresProviderInterface $storesProvider;
    /**
     * @var ScopeConfigInterface
     */
    private readonly ScopeConfigInterface $scopeConfig;
    /**
     * @var string[]|null
     */
    private ?array $storeLocales = null;

    /**
     * @param StoresProviderInterface $storesProvider
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        StoresProviderInterface $storesProvider,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->storesProvider = $storesProvider;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string $apiKey
     *
     * @return string[]
     */
    public function get(string $apiKey): array
    {
        if (null !== $this->storeLocales) {
            return $this->storeLocales;
        }
        $stores = $this->storesProvider->get($apiKey);
        foreach ($stores as $store) {
            $this->storeLocales[$store->getId()] = $this->getByStore($store);
        }

        return $this->storeLocales ?? [];
    }

    /**
     * @param StoreInterface $store
     *
     * @return string
     */
    public function getByStore(StoreInterface $store): string
    {
        $magentoLocale = $this->scopeConfig->getValue(
            ConfigBackendCustom::XML_PATH_GENERAL_LOCALE_CODE,
            ScopeInterface::SCOPE_STORES,
            $store->getId(),
        );
        $locale = $this->convertLocaleToIsoStandard($magentoLocale);

        return $locale . '-' . $store->getCode();
    }

    /**
     * @param string $magentoLocale
     *
     * @return string
     */
    private function convertLocaleToIsoStandard(string $magentoLocale): string
    {
        return str_replace('_', '-', $magentoLocale);
    }
}
