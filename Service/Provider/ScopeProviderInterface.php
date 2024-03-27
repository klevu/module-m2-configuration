<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Klevu\Configuration\Model\CurrentScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;

interface ScopeProviderInterface
{
    /**
     * @return CurrentScopeInterface
     */
    public function getCurrentScope(): CurrentScopeInterface;

    /**
     * @param WebsiteInterface|StoreInterface $scope
     *
     * @return void
     */
    public function setCurrentScope(WebsiteInterface|StoreInterface $scope): void;

    /**
     * @param string $scopeCode
     * @param string $scopeType
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setCurrentScopeByCode(string $scopeCode, string $scopeType): void;

    /**
     * @param int $scopeId
     * @param string $scopeType
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setCurrentScopeById(int $scopeId, string $scopeType): void;

    /**
     * @return void
     */
    public function unsetCurrentScope(): void;
}
