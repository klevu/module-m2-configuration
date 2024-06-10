<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Ui\Component\Listing\Integration\Column;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Store extends Column
{
    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param mixed[] $components
     * @param mixed[] $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = [],
    ) {
        $this->storeManager = $storeManager;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function prepare(): void
    {
        $this->_data['config']['componentDisabled'] = $this->isSingleStoreMode();

        parent::prepare();
    }

    /**
     * @return bool
     */
    private function isSingleStoreMode(): bool
    {
        return (bool)$this->storeManager->isSingleStoreMode();
    }
}
