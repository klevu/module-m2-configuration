<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\ViewModel\Config\Information;

use Klevu\Configuration\ViewModel\Config\FieldsetInterface;

interface ModuleVersionsInterface extends FieldsetInterface
{
    /**
     * @return bool
     */
    public function hasVersions(): bool;

    /**
     * @return array<string, string>|null
     */
    public function getVersions(): ?array;

    /**
     * @return array<string, string>|null
     */
    public function getLibraryVersions(): ?array;
}
