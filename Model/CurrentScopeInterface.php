<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Model;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;

interface CurrentScopeInterface
{
    /**
     * @return string
     */
    public function getScopeType(): string;

    /**
     * @return int|null
     */
    public function getScopeId(): ?int;

    /**
     * @return WebsiteInterface|StoreInterface|null
     */
    public function getScopeObject(): WebsiteInterface|StoreInterface|null;
}
