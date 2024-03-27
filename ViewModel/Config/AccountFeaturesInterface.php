<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\ViewModel\Config;

use Klevu\Configuration\Model\CurrentScopeInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface as ArgumentInterfaceAlias;

interface AccountFeaturesInterface extends ArgumentInterfaceAlias
{
    /**
     * @param string $feature
     * @param CurrentScopeInterface|null $scope
     *
     * @return bool
     */
    public function isAvailable(string $feature, ?CurrentScopeInterface $scope = null): bool;
}
