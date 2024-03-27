<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Block\Adminhtml\Config;

use Klevu\Configuration\ViewModel\MessageInterface as MessageViewModelInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\BlockInterface;

class Messages implements BlockInterface
{
    /**
     * @var Escaper $escaper
     */
    private readonly Escaper $escaper;
    /**
     * @var MessageViewModelInterface
     */
    private readonly MessageViewModelInterface $messageViewModel;

    /**
     * @param Escaper $escaper
     * @param MessageViewModelInterface $messageViewModel
     */
    public function __construct(
        Escaper $escaper,
        MessageViewModelInterface $messageViewModel,
    ) {
        $this->escaper = $escaper;
        $this->messageViewModel = $messageViewModel;
    }

    /**
     * @return string
     */
    public function toHtml(): string
    {
        $return = '';
        foreach ($this->messageViewModel->getMessages() as $level => $message) {
            foreach ($message as $html) {
                $return .= '<div class="message message-' . $this->escaper->escapeHtmlAttr($level) . '">'
                    . $this->escaper->escapeHtml($html->render()) .
                    '</div>';
            }
        }

        return $return;
    }
}
