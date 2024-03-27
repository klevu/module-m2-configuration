<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\ViewModel;

use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Block\ArgumentInterface;

interface ButtonInterface extends ArgumentInterface
{
    /**
     * @return string
     */
    public function getAction(): string;

    /**
     * @return string|null
     */
    public function getClass(): ?string;

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return Phrase
     */
    public function getLabel(): Phrase;

    /**
     * @return string|null
     */
    public function getStyle(): ?string;

    /**
     * @return bool
     */
    public function isDisabled(): bool;

    /**
     * @return bool
     */
    public function isVisible(): bool;
}
