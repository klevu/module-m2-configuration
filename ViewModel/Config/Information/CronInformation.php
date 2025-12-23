<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\ViewModel\Config\Information;

use Klevu\Configuration\Service\Provider\CronExecutionDataProviderInterface;
use Magento\Framework\Phrase;

class CronInformation implements CronInformationInterface
{
    /**
     * @var CronExecutionDataProviderInterface
     */
    private readonly CronExecutionDataProviderInterface $cronInformationProvider;

    /**
     * @param CronExecutionDataProviderInterface $cronInformationProvider
     */
    public function __construct(
        CronExecutionDataProviderInterface $cronInformationProvider
    ) {
        $this->cronInformationProvider = $cronInformationProvider;
    }

    /**
     * @return string[]
     */
    public function getChildBlocks(): array
    {
        return [];
    }

    /**
     * @return Phrase[][]
     */
    public function getMessages(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getStyles(): string
    {
        return '';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getCronExecutionData(): array
    {
        return $this->cronInformationProvider->get();
    }
}
