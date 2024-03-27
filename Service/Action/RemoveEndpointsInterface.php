<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action;

interface RemoveEndpointsInterface
{
    /**
     * @param int $scope
     * @param string $scopeType
     *
     * @return void
     */
    public function execute(
        int $scope,
        string $scopeType,
    ): void;
}
