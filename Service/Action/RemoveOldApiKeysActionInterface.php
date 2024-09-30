<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action;

interface RemoveOldApiKeysActionInterface
{
    /**
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return void
     */
    public function execute(int $scopeId, string $scopeType): void;
}
