<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action;

use Klevu\PhpSDK\Api\Model\AccountInterface;

interface UpdateEndpointsInterface
{
    /**
     * @param AccountInterface $account
     * @param int $scope
     * @param string $scopeType
     *
     * @return void
     */
    public function execute(
        AccountInterface $account,
        int $scope,
        string $scopeType,
    ): void;
}
