<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Controller\Adminhtml\System\Config\Acl;

use Klevu\Configuration\Test\Integration\Controller\Adminhtml\GetAdminFrontNameTrait;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use Magento\TestFramework\TestCase\AbstractBackendController as AbstractBackendControllerTestCase;

/**
 * @runTestsInSeparateProcesses
 */
class InformationTest extends AbstractBackendControllerTestCase
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
        $this->resource = 'Klevu_Configuration::configuration';
        $this->expectedNoAccessResponseCode = 302;
        /** @var Request $request */
        $request = $this->getRequest();
        $request->setParam('section', 'klevu_information');
    }

    public function testModuleListContainsConfigurationModule(): void
    {
        $this->dispatch($this->uri);
        /** @var Response $response */
        $response = $this->getResponse();
        $responseBody = $response->getBody();

        $matches = [];
        preg_match(
            '#<legend>\s?Klevu Modules\s?</legend>#',
            $responseBody,
            $matches,
        );
        $this->assertCount(1, $matches, 'Klevu Modules Fieldset Header');

        $matches = [];
        preg_match(
            '#All enabled Klevu modules and the currently installed version.#',
            $responseBody,
            $matches,
        );
        $this->assertCount(1, $matches, 'Klevu Modules Footer Text');
    }
}
