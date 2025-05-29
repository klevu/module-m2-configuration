<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Controller\Adminhtml\System\Config;

use Klevu\Configuration\Test\Integration\Controller\Adminhtml\GetAdminFrontNameTrait;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Store\Model\Store;
use Magento\TestFramework\Request as TestFrameworkRequest;
use Magento\TestFramework\Response as TestFrameworkResponse;
use Magento\TestFramework\TestCase\AbstractBackendController as AbstractBackendControllerTestCase;

/**
 * @runTestsInSeparateProcesses
 */
class RemoveInheritCheckboxPluginTest extends AbstractBackendControllerTestCase
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
        $this->resource = 'Klevu_Configuration::developer_integration';
    }

    /**
     * @magentoConfigFixture default_store klevu_configuration/developer/url_indexing indexing.url
     */
    public function testBeforeRender_RemovesInheritCheckbox_ForLabels_InInformationSections(): void
    {
        /** @var TestFrameworkRequest $request */
        $request = $this->getRequest();
        $request->setParam(key: 'section', value: 'klevu_information');
        $request->setParam(key: 'store', value: Store::DISTRO_STORE_ID);
        $this->dispatch(uri: $this->uri);
        /** @var TestFrameworkResponse $response */
        $response = $this->getResponse();
        $responseBody = $response->getBody();

        $this->assertSame(expected: 200, actual: $response->getStatusCode());

        $matches = [];
        preg_match(
            pattern: '#<tr id="row_klevu_information_endpoints_url_indexing">' .
            '<td class="label"><label for="klevu_information_endpoints_url_indexing">' .
            '<span data-config-scope="\[STORE VIEW\]">Indexing URL</span></label></td>' .
            '<td class="value"><div class="control-value">indexing\.url</div>' .
            '<p class="note"><span>Indexing URL is used for syncing data to Klevu\.</span></p></td>' .
            '<td class="use-default">#',
            subject: $responseBody,
            matches: $matches,
        );
        $this->assertCount(
            expectedCount: 0,
            haystack: $matches,
            message: 'Show Use Default',
        );
    }

    /**
     * Test ACL configuration for action working is not required here.
     * @see \Klevu\Configuration\Test\Integration\Controller\Adminhtml\System\Config\Acl\IntegrationTest
     */
    public function testAclHasAccess(): void
    {
        // Test ACL configuration for action working is not required here.
    }

    /**
     * Test ACL actually denying access is not required here.
     * @see \Klevu\Configuration\Test\Integration\Controller\Adminhtml\System\Config\Acl\IntegrationTest
     */
    public function testAclNoAccess(): void
    {
        // Test ACL actually denying access is not required here.
    }
}
