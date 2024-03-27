<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;

class CurrentScope implements CurrentScopeInterface
{
    /**
     * @var WebsiteInterface|StoreInterface|null
     */
    private readonly WebsiteInterface|StoreInterface|null $scopeObject; //phpcs:ignore Magento2.Commenting.ClassPropertyPHPDocFormatting.Missing, Generic.Files.LineLength.TooLong

    /**
     * @param WebsiteInterface|StoreInterface|null $scopeObject
     */
    public function __construct(WebsiteInterface|StoreInterface|null $scopeObject)
    {
        $this->scopeObject = $scopeObject;
    }

    /**
     * @return string
     */
    public function getScopeType(): string
    {
        if ($this->scopeObject instanceof StoreInterface) {
            return ScopeInterface::SCOPE_STORES;
        }
        if ($this->scopeObject instanceof WebsiteInterface) {
            return ScopeInterface::SCOPE_WEBSITES;
        }

        return ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
    }

    /**
     * @return int|null
     */
    public function getScopeId(): ?int
    {
        return $this->scopeObject
            ? (int)$this->scopeObject->getId()
            : null;
    }

    /**
     * @return WebsiteInterface|StoreInterface|null
     */
    public function getScopeObject(): WebsiteInterface|StoreInterface|null
    {
        return $this->scopeObject;
    }
}
