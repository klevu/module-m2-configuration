<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Configuration\Test\Integration\Controller\Adminhtml;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\AreaList;
use Magento\TestFramework\ObjectManager;

trait GetAdminFrontNameTrait
{
    /**
     * Returns configured admin front name for use in dispatching controller requests
     *
     * @return string
     */
    private function getAdminFrontName(): string
    {
        $objectManager = ObjectManager::getInstance();
        /** @var AreaList $areaList */
        $areaList = $objectManager->create(AreaList::class);
        $adminFrontName = $areaList->getFrontName('adminhtml');
        if (!$adminFrontName) {
            /** @var FrontNameResolver $backendFrontNameResolver */
            $backendFrontNameResolver = $objectManager->create(FrontNameResolver::class);
            $adminFrontName = $backendFrontNameResolver->getFrontName(true);
        }

        return (string)$adminFrontName;
    }
}
