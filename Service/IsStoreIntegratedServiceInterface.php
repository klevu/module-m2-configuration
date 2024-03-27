<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service;

interface IsStoreIntegratedServiceInterface
{
    /**
     * @return bool
     */
    public function execute(): bool;
}
