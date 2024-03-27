<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

interface ScopeConfigProviderInterface
{
    /**
     * @return bool|int|float|string|null
     */
    public function get(): bool|int|float|string|null;
}
