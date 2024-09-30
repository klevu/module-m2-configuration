<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Setup\Traits;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;

trait MigrateLegacyConfigurationSettingsTrait
{
    /**
     * @var WriterInterface
     */
    private readonly WriterInterface $configWriter;
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;
    /**
     * @var mixed[]|null
     */
    private ?array $legacyConfigSettings = null;

    /**
     * @param string $fromPath
     * @param string $toPath
     * @param mixed[]|null $mapValues
     *
     * @return void
     */
    private function renameConfigValue(
        string $fromPath,
        string $toPath,
        ?array $mapValues = [],
    ): void {
        if (!$this->configWriter) { // @phpstan-ignore-line
            throw new \LogicException(message: 'Cannot rename config value: configWriter not set');
        }
        $legacyConfigSettings = $this->getLegacyConfigSettings(path: $fromPath);
        if (!($legacyConfigSettings[$fromPath] ?? null)) {
            return;
        }

        foreach ($legacyConfigSettings[$fromPath] as $scope => $scopeValues) {
            foreach ($scopeValues as $scopeId => $value) {
                $this->configWriter->save(
                    path: $toPath,
                    value: $mapValues[$value] ?? $value,
                    scope: $scope,
                    scopeId: $scopeId,
                );
            }
        }
    }

    /**
     * @param string $path
     *
     * @return mixed[]
     * @throws \LogicException
     */
    private function getLegacyConfigSettings(string $path): array
    {
        if (!$this->resourceConnection) { // @phpstan-ignore-line
            throw new \LogicException(message: 'Cannot rename config value: resourceConnection not set');
        }
        if (!($this->legacyConfigSettings[$path] ?? null)) {
            $configTableName = $this->resourceConnection->getTableName(modelEntity: 'core_config_data');

            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select();
            $select->from(name: $configTableName);
            $select->where(
                cond: 'path IN (?)',
                value: [$path],
            );

            $this->legacyConfigSettings = [];
            $result = $connection->fetchAssoc(sql: $select);
            foreach ($result as $row) {
                $this->legacyConfigSettings[$row['path']][$row['scope']][$row['scope_id']] = $row['value'];
            }
        }

        return $this->legacyConfigSettings ?? [];
    }
}
