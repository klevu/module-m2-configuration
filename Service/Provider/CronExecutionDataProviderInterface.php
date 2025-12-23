<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

interface CronExecutionDataProviderInterface
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function get(): array;
}
