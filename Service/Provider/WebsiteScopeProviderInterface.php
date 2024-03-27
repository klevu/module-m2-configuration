<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;

interface WebsiteScopeProviderInterface
{
    /**
     * @return ?WebsiteInterface
     */
    public function getCurrentWebsite(): ?WebsiteInterface;

    /**
     * @param WebsiteInterface $website
     *
     * @return void
     */
    public function setCurrentWebsite(WebsiteInterface $website): void;

    /**
     * @param string $websiteCode
     *
     * @return void
     * @throws LocalizedException
     */
    public function setCurrentWebsiteByCode(string $websiteCode): void;

    /**
     * @param int $websiteId
     *
     * @return void
     * @throws LocalizedException
     */
    public function setCurrentWebsiteById(int $websiteId): void;

    /**
     * @return void
     */
    public function unsetCurrentWebsite(): void;
}
