<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Setup\Patch\Data;

use Klevu\Configuration\Service\Provider\Sdk\BaseUrlsProvider;
use Klevu\Configuration\Setup\Traits\MigrateLegacyConfigurationSettingsTrait;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class MigrateLegacyConfigurationSettings implements DataPatchInterface
{
    use MigrateLegacyConfigurationSettingsTrait;

    public const XML_PATH_LEGACY_HOSTNAME = 'klevu_search/general/hostname';
    public const XML_PATH_LEGACY_API_URL = 'klevu_search/general/api_url';
    public const XML_PATH_LEGACY_INDEXING_URL = 'klevu_search/general/rest_hostname';
    public const XML_PATH_LEGACY_SEARCH_URL = 'klevu_search/general/cloud_search_v2_url';
    public const XML_PATH_LEGACY_ANALYTICS_URL = 'klevu_search/general/analytics_url';
    public const XML_PATH_LEGACY_JS_URL = 'klevu_search/general/js_url';
    public const XML_PATH_LEGACY_TIERS_URL = 'klevu_search/general/tiers_url';

    /**
     * @param WriterInterface $configWriter
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        WriterInterface $configWriter,
        ResourceConnection $resourceConnection,
    ) {
        $this->configWriter = $configWriter;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return $this
     */
    public function apply(): self
    {
        $this->migrateHostnameUrl();
        $this->migrateApiUrl();
        $this->migrateIndexingUrl();
        $this->migrateSearchUrl();
        $this->migrateAnalyticsUrl();
        $this->migrateJsUrl();
        $this->migrateTiersUrl();

        return $this;
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return void
     */
    private function migrateHostnameUrl(): void
    {
        $this->renameConfigValue(
            fromPath: static::XML_PATH_LEGACY_HOSTNAME,
            toPath: BaseUrlsProvider::CONFIG_XML_PATH_URL_HOSTNAME,
        );
    }

    /**
     * @return void
     */
    private function migrateApiUrl(): void
    {
        $this->renameConfigValue(
            fromPath: static::XML_PATH_LEGACY_API_URL,
            toPath: BaseUrlsProvider::CONFIG_XML_PATH_URL_API,
        );
    }

    /**
     * @return void
     */
    private function migrateIndexingUrl(): void
    {
        $this->renameConfigValue(
            fromPath: static::XML_PATH_LEGACY_INDEXING_URL,
            toPath: BaseUrlsProvider::CONFIG_XML_PATH_URL_INDEXING,
        );
    }

    /**
     * @return void
     */
    private function migrateSearchUrl(): void
    {
        $this->renameConfigValue(
            fromPath: static::XML_PATH_LEGACY_SEARCH_URL,
            toPath: BaseUrlsProvider::CONFIG_XML_PATH_URL_SEARCH,
        );
    }

    /**
     * @return void
     */
    private function migrateAnalyticsUrl(): void
    {
        $this->renameConfigValue(
            fromPath: static::XML_PATH_LEGACY_ANALYTICS_URL,
            toPath: BaseUrlsProvider::CONFIG_XML_PATH_URL_ANALYTICS,
        );
    }

    /**
     * @return void
     */
    private function migrateJsUrl(): void
    {
        $this->renameConfigValue(
            fromPath: static::XML_PATH_LEGACY_JS_URL,
            toPath: BaseUrlsProvider::CONFIG_XML_PATH_URL_JS,
        );
    }

    /**
     * @return void
     */
    private function migrateTiersUrl(): void
    {
        $this->renameConfigValue(
            fromPath: static::XML_PATH_LEGACY_TIERS_URL,
            toPath: BaseUrlsProvider::CONFIG_XML_PATH_URL_TIERS,
        );
    }
}
