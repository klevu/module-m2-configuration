<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Magento\Cron\Model\ConfigInterface as CronConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\ExpressionFactory;

class CronExecutionDataProvider implements CronExecutionDataProviderInterface
{
    /**
     * @var CronConfigInterface
     */
    private readonly CronConfigInterface $cronConfig;
    /**
     * @var ScopeConfigInterface
     */
    private readonly ScopeConfigInterface $scopeConfig;
    /**
     * @var ResourceConnection
     */
    private readonly ResourceConnection $resourceConnection;
    /**
     * @var ExpressionFactory
     */
    private readonly ExpressionFactory $expressionFactory;
    /**
     * @var string[]
     */
    private array $jobCodes = [];

    /**
     * @param CronConfigInterface $cronConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConnection $resourceConnection
     * @param ExpressionFactory $expressionFactory
     * @param string[] $jobCodes
     */
    public function __construct(
        CronConfigInterface $cronConfig,
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resourceConnection,
        ExpressionFactory $expressionFactory,
        array $jobCodes = [],
    ) {
        $this->cronConfig = $cronConfig;
        $this->scopeConfig = $scopeConfig;
        $this->resourceConnection = $resourceConnection;
        $this->expressionFactory = $expressionFactory;
        array_walk($jobCodes, [$this, 'addJobCode']);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function get(): array
    {
        $jobs = $this->cronConfig->getJobs();
        $jobCodesToReturn = empty($this->jobCodes)
            ? array_keys($jobs['klevu'] ?? [])
            : $this->jobCodes;

        $return = array_fill_keys(
            keys: $jobCodesToReturn,
            value: [
                'last_successful_execution' => null,
                'next_scheduled_execution' => null,
            ],
        );

        $jobsConfig = array_intersect_key(
            $jobs['klevu'] ?? [],
            array_flip($jobCodesToReturn),
        );

        foreach ($jobsConfig as $jobCode => $jobConfig) {
            $return[$jobCode]['schedule'] = match (true) {
                isset($jobConfig['schedule']) => $jobConfig['schedule'],
                isset($jobConfig['config_path']) => $this->scopeConfig->getValue($jobConfig['config_path']),
                default => null,
            };
        }

        $lastSuccessfulExecutions = $this->getLastSuccessfulExecutions($jobCodesToReturn);
        foreach ($lastSuccessfulExecutions as $execution) {
            $jobCode = (string)$execution['job_code'];
            $return[$jobCode]['last_successful_execution'] = $execution;
        }

        $nextScheduledExecutions = $this->getNextScheduledExecutions($jobCodesToReturn);
        foreach ($nextScheduledExecutions as $execution) {
            $jobCode = (string)$execution['job_code'];
            $return[$jobCode]['next_scheduled_execution'] = $execution;
        }

        return $return;
    }

    /**
     * @param string $jobCode
     *
     * @return void
     */
    private function addJobCode(string $jobCode): void
    {
        if ($jobCode && !in_array($jobCode, $this->jobCodes, true)) {
            $this->jobCodes[] = $jobCode;
        }
    }

    /**
     * @param string[] $jobCodes
     *
     * @return array<string, array<string, mixed>>
     */
    private function getLastSuccessfulExecutions(array $jobCodes): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('cron_schedule');

        $select = $connection->select();
        $select->from(
            name: ['cs' => $tableName],
            cols: [
                'job_code',
                'status',
                'finished_at' => $this->expressionFactory->create([
                    'expression' => 'MAX(cs.finished_at)',
                ]),
            ],
        );
        $select->where('cs.status = ?', 'success');
        $select->where('cs.job_code IN (?)', $jobCodes);
        $select->group('cs.job_code');

        return $connection->fetchAll($select);
    }

    /**
     * @param string[] $jobCodes
     *
     * @return array<string, array<string, mixed>>
     */
    private function getNextScheduledExecutions(array $jobCodes): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('cron_schedule');

        $runningJobsSelect = $connection->select();
        $runningJobsSelect->from(
            name: ['cs_running' => $tableName],
            cols: [
                'job_code',
                'status',
                'started_at' => $this->expressionFactory->create([
                    'expression' => 'MIN(cs_running.executed_at)',
                ]),
            ],
        );
        $runningJobsSelect->where('cs_running.executed_at IS NOT NULL');
        $runningJobsSelect->where('cs_running.finished_at IS NULL');
        $runningJobsSelect->where('cs_running.status != "error"');
        $runningJobsSelect->where('cs_running.job_code IN (?)', $jobCodes);
        $runningJobsSelect->group('cs_running.job_code');

        $runningJobsResult = $connection->fetchAll($runningJobsSelect);

        $foundJobCodes = array_map(
            callback: static fn (array $item): string => (string)$item['job_code'],
            array: $runningJobsResult,
        );

        $pendingJobsSelect = $connection->select();
        $pendingJobsSelect->from(
            name: ['cs' => $tableName],
            cols: [
                'job_code',
                'status',
                'scheduled_at' => $this->expressionFactory->create([
                    'expression' => 'MIN(cs.scheduled_at)',
                ]),
            ],
        );
        $pendingJobsSelect->where('cs.executed_at IS NULL');
        $pendingJobsSelect->where('cs.status = ?', 'pending');
        $pendingJobsSelect->where(
            cond: 'cs.job_code IN (?)',
            value: array_diff($jobCodes, $foundJobCodes),
        );
        $pendingJobsSelect->group('cs.job_code');

        $pendingJobsResult = $connection->fetchAll($pendingJobsSelect);

        return array_merge(
            $runningJobsResult,
            $pendingJobsResult,
        );
    }
}
