<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Block\Adminhtml\System\Config;

use Klevu\Configuration\ViewModel\ButtonInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button as ButtonWidget;
use Magento\Config\Block\System\Config\Form\Field as SystemConfigField;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class Button extends SystemConfigField
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
     * Remove scope label
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsetData('scope');
        $element->unsetData('can_use_website_value');
        $element->unsetData('can_use_default_value');

        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     * @throws LocalizedException
     */
    protected function _getElementHtml(
        // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        AbstractElement $element,
    ): string {
        if (!$this->viewModel->isVisible()) {
            return '';
        }
        $layout = $this->getLayout();
        /** @var ButtonWidget $button */
        $button = $layout->createBlock(ButtonWidget::class);
        $button->setData(
            [
                'id' => $this->viewModel->getId(),
                'label' => $this->viewModel->getLabel(),
                'onclick' => $this->viewModel->getAction(),
                'class' => $this->viewModel->getClass(),
                'style' => $this->viewModel->getStyle(),
                'disabled' => $this->viewModel->isDisabled(),
            ],
        );

        return $button->toHtml();
    }
}
