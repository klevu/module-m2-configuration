<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\ViewModel\Config;

use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Block\ArgumentInterface;

interface FieldsetInterface extends ArgumentInterface
{
    // @TODO split this interface into multiple interfaces, it does too much
    /**
     * @return string[]
     */
    public function getChildBlocks(): array;

    /**
     * Returns array with keys reflecting the css class to be used for the message
     * [
     *     'warning' => [
     *         __('This is a warning message'),
     *     ],
     *     'info' => [
     *        __('This is an info message'),
     *        __('This is another info message')
     *    ],
     * ]
     * allowed keys: notice, info, warning, error, success
     *
     * @return array<string, array<Phrase>>
     */
    public function getMessages(): array;

    /**
     * @return string
     */
    public function getStyles(): string;
}
