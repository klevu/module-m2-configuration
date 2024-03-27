<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Plugin\Stores\Config\Information;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;

class RemoveInheritCheckboxPlugin
{
    private const CONFIG_SECTION_KLEVU = 'klevu_';
    private const FIELD_TYPE_LABEL = 'label';

    /**
     * @var RequestInterface
     */
    private readonly RequestInterface $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(
        RequestInterface $request,
    ) {
        $this->request = $request;
    }

    /**
     * @param Field $subject
     * @param AbstractElement $element
     *
     * @return AbstractElement[]
     */
    public function beforeRender(
        Field $subject, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        AbstractElement $element,
    ): array {
        if (
            $this->isElementTypeLabel($element)
            && $this->isKlevuSection()
        ) {
            $element->setData(key: 'can_use_website_value', value: false);
            $element->setData(key: 'can_use_default_value', value: false);
            $element->setData(key: 'can_restore_to_default', value: false);
        }

        return [$element];
    }

    /**
     * @param AbstractElement $element
     *
     * @return bool
     */
    private function isElementTypeLabel(AbstractElement $element): bool
    {
        return $element->getType() === self::FIELD_TYPE_LABEL;
    }

    /**
     * @return bool
     */
    private function isKlevuSection(): bool
    {
        return str_starts_with(
            haystack: $this->request->getParam(key: 'section'),
            needle: self::CONFIG_SECTION_KLEVU,
        );
    }
}
