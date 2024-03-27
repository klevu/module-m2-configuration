<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Validator;

use Magento\Framework\Validator\ValidatorInterface as MagentoValidatorInterface;

interface ValidatorInterface extends MagentoValidatorInterface
{
    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function isValid(mixed $value): bool;

    /**
     * Return type omitted due to compatibility with \Magento\Framework\Validator\AbstractValidator::hasMessages
     * @return bool
     */
    public function hasMessages(); //phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
}
