<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Controller\Adminhtml\System\Config;

use Klevu\Configuration\Test\Integration\Controller\Adminhtml\GetAdminFrontNameTrait;
use Magento\Framework\Exception\AuthenticationException;
use Magento\TestFramework\Request as TestFrameworkRequest;
use Magento\TestFramework\Response as TestFrameworkResponse;
use Magento\TestFramework\TestCase\AbstractBackendController as AbstractBackendControllerTestCase;

class RemoveSaveButtonPluginTest extends AbstractBackendControllerTestCase
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
    }

    /**
     * @dataProvider testAfterAddChild_DoesNotRemoveSaveButton_ForOtherSections_DataProvider
     */
    public function testAfterAddChild_DoesNotRemoveSaveButton_ForOtherSections(string $section): void
    {
        /** @var TestFrameworkRequest $request */
        $request = $this->getRequest();
        $request->setParam(key: 'section', value: $section);
        $this->dispatch(uri: $this->uri);
        /** @var TestFrameworkResponse $response */
        $response = $this->getResponse();
        $responseBody = $response->getBody();

        $this->assertSame(expected: 200, actual: $response->getStatusCode());

        $matches = [];
        preg_match(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            pattern: '#<button\ *?id="save".*?title="Save Config".*?type="button".*?>.*?<span>.*?Save Config.*?</span>.*?</button>#s',
            subject: $responseBody,
            matches: $matches,
        );
        $this->assertCount(
            expectedCount: 1,
            haystack: $matches,
            message: 'Show Save Button',
        );
    }

    /**
     * @dataProvider testAfterAddChild_DoesNotRemoveSaveButton_ForOtherSections_DataProvider
     * @magentoConfigFixture default_store general/single_store_mode/enabled 1
     */
    public function testAfterAddChild_DoesNotRemoveSaveButton_ForOtherSections_SingleStoreMode(string $section): void
    {
        /** @var TestFrameworkRequest $request */
        $request = $this->getRequest();
        $request->setParam(key: 'section', value: $section);
        $this->dispatch(uri: $this->uri);
        /** @var TestFrameworkResponse $response */
        $response = $this->getResponse();
        $responseBody = $response->getBody();

        $this->assertSame(expected: 200, actual: $response->getStatusCode());

        $matches = [];
        preg_match(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            pattern: '#<button\ *?id="save".*?title="Save Config".*?type="button".*?>.*?<span>.*?Save Config.*?</span>.*?</button>#s',
            subject: $responseBody,
            matches: $matches,
        );
        $this->assertCount(
            expectedCount: 1,
            haystack: $matches,
            message: 'Show Save Button',
        );
    }

    /**
     * @return string[][]
     */
    public static function testAfterAddChild_DoesNotRemoveSaveButton_ForOtherSections_DataProvider(): array
    {
        return [
            ['general'],
            ['klevu_developer'],
            ['sales'],
        ];
    }

    /**
     * @dataProvider testAfterAddChild_RemovesSaveButton_ForSpecifiedSections_DataProvider
     */
    public function testAfterAddChild_RemovesSaveButton_ForSpecifiedSections(string $section): void
    {
        /** @var TestFrameworkRequest $request */
        $request = $this->getRequest();
        $request->setParam(key: 'section', value: $section);
        $this->dispatch(uri: $this->uri);
        /** @var TestFrameworkResponse $response */
        $response = $this->getResponse();
        $responseBody = $response->getBody();

        $this->assertSame(expected: 200, actual: $response->getStatusCode());

        $matches = [];
        preg_match(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            pattern: '#<button\ *?id="save".*?title="Save Config".*?type="button".*?>.*?<span>.*?Save Config.*?</span>.*?</button>#s',
            subject: $responseBody,
            matches: $matches,
        );
        $this->assertCount(
            expectedCount: 0,
            haystack: $matches,
            message: 'Show Save Button',
        );
    }

    /**
     * @dataProvider testAfterAddChild_RemovesSaveButton_ForSpecifiedSections_DataProvider
     * @magentoConfigFixture default_store general/single_store_mode/enabled 1
     */
    public function testAfterAddChild_RemovesSaveButton_ForSpecifiedSections_SingleStoreMode(string $section): void
    {
        /** @var TestFrameworkRequest $request */
        $request = $this->getRequest();
        $request->setParam(key: 'section', value: $section);
        $this->dispatch(uri: $this->uri);
        /** @var TestFrameworkResponse $response */
        $response = $this->getResponse();
        $responseBody = $response->getBody();

        $this->assertSame(expected: 200, actual: $response->getStatusCode());

        $matches = [];
        preg_match(
        // phpcs:ignore Generic.Files.LineLength.TooLong
            pattern: '#<button\ *?id="save".*?title="Save Config".*?type="button".*?>.*?<span>.*?Save Config.*?</span>.*?</button>#s',
            subject: $responseBody,
            matches: $matches,
        );
        $this->assertCount(
            expectedCount: 0,
            haystack: $matches,
            message: 'Show Save Button',
        );
    }

    /**
     * @return string[][]
     */
    public static function testAfterAddChild_RemovesSaveButton_ForSpecifiedSections_DataProvider(): array
    {
        return [
            ['klevu_information'],
            ['klevu_integration'],
        ];
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
