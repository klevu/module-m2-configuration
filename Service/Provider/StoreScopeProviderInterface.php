<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;

interface StoreScopeProviderInterface
{
    /**
     * @return ?StoreInterface
     */
    public function getCurrentStore(): ?StoreInterface;

    /**
     * @param StoreInterface $store
     *
     * @return void
     */
    public function setCurrentStore(StoreInterface $store): void;

    /**
     * @param string $storeCode
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function setCurrentStoreByCode(string $storeCode): void;

    /**
     * @param int $storeId
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function setCurrentStoreById(int $storeId): void;

    /**
     * @return void
     */
    public function unsetCurrentStore(): void;
}
