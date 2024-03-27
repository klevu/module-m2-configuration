<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Api;

use Magento\Authorization\Model\Role;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\Rules;
use Magento\Authorization\Model\RulesFactory;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\TestFramework\Bootstrap;
use Magento\TestFramework\Helper\Bootstrap as BootstrapHelper;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * @covers \Klevu\Configuration\WebApi\Integration\CheckApiKeys;
 */
class CheckApiKeysTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/klevu-configuration/check-api-keys';

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager;
    /**
     * @var RoleFactory|null
     */
    private ?RoleFactory $roleFactory;
    /**
     * @var RulesFactory|null
     */
    private ?RulesFactory $rulesFactory;
    /**
     * @var AdminTokenServiceInterface|null
     */
    private ?AdminTokenServiceInterface $adminTokens;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = BootstrapHelper::getObjectManager();
        $this->roleFactory = $this->objectManager->get(type: RoleFactory::class);
        $this->rulesFactory = $this->objectManager->get(type: RulesFactory::class);
        $this->adminTokens = $this->objectManager->get(type: AdminTokenServiceInterface::class);
    }

    /**
     * @dataProvider testErrorReturned_WhenIncorrectHttpMethod_DataProvider
     */
    public function testErrorReturned_WhenIncorrectHttpMethod(string $method): void
    {
        $this->_markTestAsRestOnly();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('{"message":"Request does not match any route.","trace":null}');

        $serviceInfo = [
            WebapiAbstract::ADAPTER_REST => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => $method,
            ],
        ];
        $requestData = [
            'apiKey' => 'klevu-123456789',
            'authKey' => $this->generateAuthKey(length: 10),
        ];
        $this->_webApiCall(serviceInfo: $serviceInfo, arguments: $requestData);
    }

    /**
     * @return string[][]
     */
    public function testErrorReturned_WhenIncorrectHttpMethod_DataProvider(): array
    {
        return [
            [RestRequest::HTTP_METHOD_GET],
            [RestRequest::HTTP_METHOD_PUT],
            [RestRequest::HTTP_METHOD_DELETE],
        ];
    }

    /**
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role_rollback.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testAclNoAccess(): void
    {
        $this->_markTestAsRestOnly();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches(
            '/{' .
            '"message":"The consumer isn\'t authorized to access %resources.",' .
            '"parameters":{"resources":"Klevu_Configuration::integration"' .
            '}.*/',
        );

        $serviceInfo = [
            WebapiAbstract::ADAPTER_REST => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => RestRequest::HTTP_METHOD_POST,
                'token' => $this->getTokenForUserWithRoles(roles: ['Magento_Cms::save']),
            ],
        ];
        $requestData = [
            'apiKey' => 'klevu-123456789',
            'authKey' => $this->generateAuthKey(length: 10),
        ];
        $this->_webApiCall(serviceInfo: $serviceInfo, arguments: $requestData);
    }

    /**
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role_rollback.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testAclHasAccess(): void
    {
        $this->_markTestAsRestOnly();

        $serviceInfo = [
            WebapiAbstract::ADAPTER_REST => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => RestRequest::HTTP_METHOD_POST,
                'token' => $this->getTokenForUserWithRoles(roles: ['Klevu_Configuration::integration']),
            ],
        ];
        $apiKey = 'klevu-123456789';
        $requestData = [
            'apiKey' => $apiKey,
            'authKey' => $this->generateAuthKey(length: 10),
            'scopeId' => 1,
            'scopeType' => 'store',
        ];
        $response = $this->_webApiCall(serviceInfo: $serviceInfo, arguments: $requestData);

        $this->assertIsArray($response, 'Response');
        $this->assertArrayHasKey('status', $response);
        $this->assertSame('error', $response['status']);
        $this->assertArrayHasKey('code', $response);
        $this->assertSame(404, $response['code']);
        $this->assertArrayHasKey('messages', $response);
        $this->assertSame(
            expected: [sprintf('Account Not Found: Klevu account not found for key "%s".', $apiKey)],
            actual: $response['messages'],
        );
    }

    /**
     * @param int $length
     *
     * @return string
     */
    private function generateAuthKey(int $length): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $return = '';
        while (strlen($return) < $length) {
            $return .= substr(
                string: str_shuffle($characters),
                offset: 0,
                length: $length - strlen($return),
            );
        }

        return $return;
    }

    /**
     * @param string[]|null $roles
     *
     * @return string
     * @throws AuthenticationException
     * @throws InputException
     * @throws LocalizedException
     */
    private function getTokenForUserWithRoles(?array $roles = []): string
    {
        /** @var Role $role */
        $role = $this->roleFactory->create();
        // there is no repository for authorisation role
        $role->load('test_custom_role', 'role_name'); // @phpstan-ignore-line
        /** @var Rules $rules */
        $rules = $this->rulesFactory->create();
        $rules->setRoleId($role->getId());
        if ($roles) {
            $rules->setResources($roles);
        }
        $rules->saveRel();

        //Using the admin user with custom role.
        return $this->adminTokens->createAdminAccessToken(
            'customRoleUser',
            Bootstrap::ADMIN_PASSWORD,
        );
    }
}
