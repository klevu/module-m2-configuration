<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Block\Adminhtml\Config;

use Klevu\Configuration\ViewModel\Config\ArticleListInterface as ViewModelArticleListInterface;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session as BackendAuthSession;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Helper\Js as JsHelper;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class ArticleList extends Fieldset
{
    /**
     * @var ViewModelArticleListInterface
     */
    private readonly ViewModelArticleListInterface $viewModel;
    /**
     * @var Escaper
     */
    private readonly Escaper $escaper;
    /**
     * @var string
     */
    private readonly string $blockName;
    /**
     * @var string
     */
    private readonly string $template;

    /**
     * @param Context $context
     * @param BackendAuthSession $authSession
     * @param JsHelper $jsHelper
     * @param ViewModelArticleListInterface $viewModel
     * @param Escaper $escaper
     * @param string $blockName
     * @param string|null $template
     * @param mixed[] $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context $context,
        BackendAuthSession $authSession,
        JsHelper $jsHelper,
        ViewModelArticleListInterface $viewModel,
        Escaper $escaper,
        string $blockName,
        ?string $template = null,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null,
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data, $secureRenderer);

        $this->viewModel = $viewModel;
        $this->escaper = $escaper;
        $this->blockName = $blockName;
        $this->template = $template
            ?: 'Klevu_Configuration::system/config/article_list.phtml';
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     * @throws LocalizedException
     */
    public function render(
        // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        AbstractElement $element,
    ): string {
        $layout = $this->getLayout();
        /** @var Field $block */
        $block = $layout->createBlock(
            type: Field::class,
            name: $this->blockName,
        );
        $block->setTemplate($this->template); //@phpstan-ignore-line
        $block->setData('view_model', $this->viewModel);
        $block->setData('escaper', $this->escaper);

        return $block->toHtml();
    }

    /**
     * @return ViewModelArticleListInterface
     */
    public function getViewModel(): ViewModelArticleListInterface
    {
        return $this->viewModel;
    }

    /**
     * @return Escaper
     */
    public function getEscaper(): Escaper
    {
        return $this->escaper;
    }
}
