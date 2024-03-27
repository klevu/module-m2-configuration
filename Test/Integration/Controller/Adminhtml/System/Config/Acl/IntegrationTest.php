<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Configuration\Test\Integration\Controller\Adminhtml\System\Config\Acl;

use Klevu\Configuration\Test\Integration\Controller\Adminhtml\GetAdminFrontNameTrait;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Store\Model\Store;
use Magento\TestFramework\TestCase\AbstractBackendController as AbstractBackendControllerTestCase;

class IntegrationTest extends AbstractBackendControllerTestCase
{
    use GetAdminFrontNameTrait;

    /**
     * @return void
     * @throws AuthenticationException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = $this->getAdminFrontName() . '/admin/system_config/edit';
        $this->resource = 'Klevu_Configuration::integration';
        $this->expectedNoAccessResponseCode = 302;
        /** @var Request $request */
        $request = $this->getRequest();
        $request->setParam('section', 'klevu_integration');
        $request->setParam('store', Store::DISTRO_STORE_ID);
    }
}
