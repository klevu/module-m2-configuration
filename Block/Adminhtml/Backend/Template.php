<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Block\Adminhtml\Backend;

use Magento\Backend\Block\Template as BackendTemplate;
use Magento\Backend\Block\Template\Context;

/**
 * Used when creating a virtualType of Magento\Backend\Block\Template to set the name in Layout.
 * Without this an error is thrown in Adobe Commerce here
 * vendor/magento/module-price-permissions/Observer/AdminhtmlBlockHtmlBeforeObserver.php:125
 * as $block->getNameInLayout() returns null and is used directly in stripos() without any checks.
 * Deprecated Functionality: stripos(): Passing null to parameter #1 ($haystack) of type string is deprecated
 */
class Template extends BackendTemplate
{
    /**
     * @param Context $context
     * @param mixed[] $data
     */
    public function __construct(
        Context $context,
        array $data = [],
    ) {
        parent::__construct($context, $data);

        if ($data['name'] ?? []) {
            $this->setNameInLayout($data['name']);
        }
    }
}
