<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Validator;

use Magento\Framework\Validator\AbstractValidator;
use Magento\Store\Model\ScopeInterface;

class ScopeTypeValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * @var string[]
     */
    private array $allowedScopes = [
        ScopeInterface::SCOPE_STORE,
        ScopeInterface::SCOPE_STORES,
//        ScopeInterface::SCOPE_WEBSITE, // @TODO add when channels are available
//        ScopeInterface::SCOPE_WEBSITES, // @TODO add when channels are available
    ];

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
        if (is_string($value)) {
            return true;
        }
        $this->_addMessages([
            __(
                'Invalid Scope provided. Expected string; received %1.',
                get_debug_type($value),
            )->render(),
        ]);

        return false;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    private function validateValue(string $value): bool
    {
        if (in_array($value, $this->allowedScopes, true)) {
            return true;
        }
        $this->_addMessages([
            __(
                'Invalid Scope provided. Expected one of %1; received %2.',
                implode(', ', $this->allowedScopes),
                $value,
            )->render(),
        ]);

        return false;
    }
}
