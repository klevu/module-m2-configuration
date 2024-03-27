<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Traits;

trait EnumTrait
{
    /**
     * @return array<string, string>
     */
    public static function nameValueArray(): array
    {
        return array_combine(self::names(), self::values());
    }

    /**
     * @return array<string, string>
     */
    public static function valueNameArray(): array
    {
        return array_combine(self::values(), self::names());
    }

    /**
     * @return string[]
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
