<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Ui\Component\Control;

use Klevu\Configuration\ViewModel\ButtonInterface;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use Magento\Ui\Component\Control\Button as UiButton;

class Button extends UiButton
{
    /**
     * @var ButtonInterface
     */
    private readonly ButtonInterface $viewModel;

    /**
     * @param Context $context
     * @param ButtonInterface $viewModel
     * @param mixed[] $data
     */
    public function __construct(
        Context $context,
        ButtonInterface $viewModel,
        array $data = [],
    ) {
        parent::__construct($context, $data);

        $this->viewModel = $viewModel;
    }

    /**
     * @return string|null
     */
    public function getOnClick(): ?string
    {
        return $this->viewModel->getAction();
    }

    /**
     * Retrieve attributes html
     *
     * @return string
     */
    public function getAttributesHtml(): string
    {
        if (!$this->viewModel->isVisible()) {
            return '';
        }
        $disabled = $this->getDisabled()
            ? 'disabled'
            : '';
        $title = $this->getLabel();
        $classes = ['action-', 'scalable'];
        if ($this->getClass()) {
            $classes[] = $this->getClass();
        }
        if ($disabled) {
            $classes[] = $disabled;
        }

        return $this->attributesToHtml(
            $this->prepareAttributes(
                title: $title->render(),
                classes: $classes,
                disabled: $disabled,
            ),
        );
    }

    /**
     * @return bool
     */
    public function getDisabled(): bool
    {
        return $this->viewModel->isDisabled();
    }

    /**
     * @return Phrase
     */
    public function getLabel(): Phrase
    {
        return $this->viewModel->getLabel();
    }

    /**
     * @return string|null
     */
    public function getClass(): ?string
    {
        return $this->viewModel->getClass();
    }
}
