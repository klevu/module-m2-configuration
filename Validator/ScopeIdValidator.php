<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Validator;

use Magento\Framework\Validator\AbstractValidator;

class ScopeIdValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        $this->_clearMessages();

        return $this->validateType($value)
            && $this->validateValue($value);
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private function validateType(mixed $value): bool
    {
        if (is_int($value) || null === $value || is_string($value)) {
            return true;
        }
        $this->_addMessages([
            __(
                'Invalid Scope ID provided. Expected string, int or null; received %1.',
                get_debug_type($value),
            )->render(),
        ]);

        return false;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private function validateValue(mixed $value): bool
    {
        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
        if ((int)$value == $value) { // intentionally used weak comparison to remove floats
            return true;
        }
        $this->_addMessages([
            __(
                'Invalid Scope ID provided. Expected numeric value or null; received %1 (%2).',
                $value,
                get_debug_type($value),
            )->render(),
        ]);

        return false;
    }
}
