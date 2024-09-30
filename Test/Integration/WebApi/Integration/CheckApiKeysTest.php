<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\WebApi\Integration;

use Klevu\Configuration\Api\CheckApiKeysInterface;
use Klevu\Configuration\Service\Action\Sdk\Account\AccountLookupActionInterface;
use Klevu\Configuration\Service\CheckApiKeysService;
use Klevu\Configuration\WebApi\Integration\CheckApiKeys;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Model\AccountCredentialsFactory;
use Klevu\PhpSDK\Model\AccountFactory;
use Klevu\PhpSDK\Service\Account\AccountLookupService;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Klevu\Configuration\WebApi\Integration\CheckApiKeys
 */
class CheckApiKeysTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;
    /**
     * @var LoggerInterface|MockObject|null
     */
    private LoggerInterface|MockObject|null $mockLogger = null; // phpcs:ignore Magento2.Commenting.ClassPropertyPHPDocFormatting.Missing, Generic.Files.LineLength.TooLong

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();
    }

    public function testImplements_CheckApiKeysInterface(): void
    {
        $this->assertInstanceOf(
            expected: CheckApiKeysInterface::class,
            actual: $this->instantiateCheckApiKeysWebApi(),
        );
    }

    public function testPreference_CheckApiKeysInterface(): void
    {
        $this->assertInstanceOf(
            expected: CheckApiKeys::class,
            actual: $this->objectManager->create(CheckApiKeysInterface::class),
        );
    }

    public function testExecute_ReturnsError_WhenValidationFails_ApiKeyEmpty(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('error');
        $webApi = $this->instantiateCheckApiKeysWebApi([
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: '',
            authKey: $this->generateAuthKey(length: 10),
        );

        $this->assertSame(expected: 400, actual: $response->getCode());
        $this->assertSame(expected: 'error', actual: $response->getStatus());
        $this->assertSame(
            expected: ['Validation Errors: JS API Key must not be empty'],
            actual: $response->getMessages(),
        );
    }

    /**
     * @dataProvider testExecute_ReturnsError_WhenValidationFails_ApiKeyInvalid_dataProvider
     */
    public function testExecute_ReturnsError_WhenValidationFails_ApiKeyInvalid(string $invalidApiKey): void
    {
        $this->mockLogger->expects($this->once())
            ->method('error');
        $webApi = $this->instantiateCheckApiKeysWebApi([
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $invalidApiKey,
            authKey: $this->generateAuthKey(length: 10),
        );

        $this->assertSame(expected: 400, actual: $response->getCode());
        $this->assertSame(expected: 'error', actual: $response->getStatus());
        $this->assertSame(
            expected: ['Validation Errors: JS API Key is not valid'],
            actual: $response->getMessages(),
        );
    }

    /**
     * @return string[][]
     */
    public function testExecute_ReturnsError_WhenValidationFails_ApiKeyInvalid_dataProvider(): array
    {
        return [
            ['eyfywuef'],
            ['klevu'],
            ['klevu-none-digits'],
            ['klevu-12345678909876543211234567890'],
        ];
    }

    public function testExecute_ReturnsError_WhenValidationFails_AuthKeyEmpty(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('error');
        $webApi = $this->instantiateCheckApiKeysWebApi([
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: 'klevu-123456789',
            authKey: '',
        );

        $this->assertSame(expected: 400, actual: $response->getCode());
        $this->assertSame(expected: 'error', actual: $response->getStatus());
        $this->assertSame(
            expected: ['Validation Errors: REST AUTH Key must not be empty'],
            actual: $response->getMessages(),
        );
    }

    /**
     * @dataProvider testExecute_ReturnsError_WhenValidationFails_AuthKeyInvalid_dataProvider
     */
    public function testExecute_ReturnsError_WhenValidationFails_AuthKeyInvalid(string $invalidAuthKey): void
    {
        $this->mockLogger->expects($this->once())
            ->method('error');
        $webApi = $this->instantiateCheckApiKeysWebApi(arguments: [
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: 'klevu-123456789',
            authKey: $invalidAuthKey,
        );

        $this->assertSame(expected: 400, actual: $response->getCode());
        $this->assertSame(expected: 'error', actual: $response->getStatus());
        $this->assertSame(
            expected: ['Validation Errors: REST AUTH Key is not valid'],
            actual: $response->getMessages(),
        );
    }

    /**
     * @return string[][]
     */
    public function testExecute_ReturnsError_WhenValidationFails_AuthKeyInvalid_dataProvider(): array
    {
        return [
            [$this->generateAuthKey(length: 9)],
            [$this->generateAuthKey(length: 129)],
            [$this->generateAuthKey(length: 10) . '!'],
            [$this->generateAuthKey(length: 10) . '@'],
            [$this->generateAuthKey(length: 10) . '$'],
        ];
    }

    public function testExecute_ReturnsError_WhenValidationFails_PlatformIsNotMagento(): void
    {
        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $accountFactory = new AccountFactory();
        $mockAccount = $accountFactory->create(data: [
            'jsApiKey' => $jsApiKey,
            'restAuthKey' => $restAuthKey,
            'platform' => 'custom',
            'active' => true,
            'companyName' => 'Klevu',
            'email' => 'user@klevu.com',
            'analyticsUrl' => 'stats.ksearchnet.com',
            'indexingUrl' => 'indexing.ksearchnet.com',
            'jsUrl' => 'js.klevu.com',
            'searchUrl' => 'search.klevu.com',
            'smartCategoryMerchandisingUrl' => 'catnav.klevu.com',
            'tiersUrl' => 'tiers.klevu.com',
            'indexingVersion' => '3',
        ]);

        $accountCredentialsFactory = $this->objectManager->get(AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $jsApiKey,
                'restAuthKey' => $restAuthKey,
            ],
        );

        $mockSdkAccountLookupService = $this->getMockBuilder(AccountLookupService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSdkAccountLookupService->expects($this->once())
            ->method('execute')
            ->with($accountCredentials)
            ->willReturn($mockAccount);

        $accountLookupAction = $this->objectManager->create(type: AccountLookupActionInterface::class, arguments: [
            'accountLookupService' => $mockSdkAccountLookupService,
        ]);
        $checkApiKeysService = $this->objectManager->create(type: CheckApiKeysService::class, arguments: [
            'accountLookupAction' => $accountLookupAction,
        ]);

        $this->mockLogger->expects($this->once())->method('error');
        $this->mockLogger->expects($this->never())->method('info');

        $webApi = $this->instantiateCheckApiKeysWebApi(arguments: [
            'apiKeysService' => $checkApiKeysService,
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
        );

        $this->assertSame(expected: 400, actual: $response->getCode());
        $this->assertSame(expected: 'error', actual: $response->getStatus());
        $this->assertSame(
            expected: [
                'Validation Error: '
                . 'Account can not be integrated with Magento as it is not assigned to the Magento platform.',
            ],
            actual: $response->getMessages(),
        );
    }

    public function testExecute_ReturnsError_WhenValidationFails_AccountInactive(): void
    {
        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $accountFactory = new AccountFactory();
        $mockAccount = $accountFactory->create(data: [
            'jsApiKey' => $jsApiKey,
            'restAuthKey' => $restAuthKey,
            'platform' => 'magento',
            'active' => false,
            'companyName' => 'Klevu',
            'email' => 'user@klevu.com',
            'analyticsUrl' => 'stats.ksearchnet.com',
            'indexingUrl' => 'indexing.ksearchnet.com',
            'jsUrl' => 'js.klevu.com',
            'searchUrl' => 'search.klevu.com',
            'smartCategoryMerchandisingUrl' => 'catnav.klevu.com',
            'tiersUrl' => 'tiers.klevu.com',
            'indexingVersion' => '3',
        ]);

        $accountCredentialsFactory = $this->objectManager->get(AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $jsApiKey,
                'restAuthKey' => $restAuthKey,
            ],
        );

        $mockSdkAccountLookupService = $this->getMockBuilder(AccountLookupService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSdkAccountLookupService->expects($this->once())
            ->method('execute')
            ->with($accountCredentials)
            ->willReturn($mockAccount);

        $accountLookupAction = $this->objectManager->create(type: AccountLookupActionInterface::class, arguments: [
            'accountLookupService' => $mockSdkAccountLookupService,
        ]);
        $checkApiKeysService = $this->objectManager->create(type: CheckApiKeysService::class, arguments: [
            'accountLookupAction' => $accountLookupAction,
        ]);

        $this->mockLogger->expects($this->once())->method('error');
        $this->mockLogger->expects($this->never())->method('info');

        $webApi = $this->instantiateCheckApiKeysWebApi(arguments: [
            'apiKeysService' => $checkApiKeysService,
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
        );

        $this->assertSame(expected: 400, actual: $response->getCode());
        $this->assertSame(expected: 'error', actual: $response->getStatus());
        $this->assertSame(
            expected: ['Validation Error: Account can not be integrated as it is inactive.'],
            actual: $response->getMessages(),
        );
    }

    public function testExecute_ReturnsError_WhenValidationFails_AccountIndexingVersionInvalid(): void
    {
        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $accountFactory = new AccountFactory();
        $mockAccount = $accountFactory->create(data: [
            'jsApiKey' => $jsApiKey,
            'restAuthKey' => $restAuthKey,
            'platform' => 'magento',
            'active' => true,
            'companyName' => 'Klevu',
            'email' => 'user@klevu.com',
            'analyticsUrl' => 'stats.ksearchnet.com',
            'indexingUrl' => 'indexing.ksearchnet.com',
            'jsUrl' => 'js.klevu.com',
            'searchUrl' => 'search.klevu.com',
            'smartCategoryMerchandisingUrl' => 'catnav.klevu.com',
            'tiersUrl' => 'tiers.klevu.com',
            'indexingVersion' => '2',
        ]);

        $accountCredentialsFactory = $this->objectManager->get(AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $jsApiKey,
                'restAuthKey' => $restAuthKey,
            ],
        );

        $mockSdkAccountLookupService = $this->getMockBuilder(AccountLookupService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSdkAccountLookupService->expects($this->once())
            ->method('execute')
            ->with($accountCredentials)
            ->willReturn($mockAccount);

        $accountLookupAction = $this->objectManager->create(type: AccountLookupActionInterface::class, arguments: [
            'accountLookupService' => $mockSdkAccountLookupService,
        ]);
        $checkApiKeysService = $this->objectManager->create(type: CheckApiKeysService::class, arguments: [
            'accountLookupAction' => $accountLookupAction,
        ]);

        $this->mockLogger->expects($this->once())->method('error');
        $this->mockLogger->expects($this->never())->method('info');

        $webApi = $this->instantiateCheckApiKeysWebApi(arguments: [
            'apiKeysService' => $checkApiKeysService,
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
        );

        $this->assertSame(expected: 400, actual: $response->getCode());
        $this->assertSame(expected: 'error', actual: $response->getStatus());
        $this->assertSame(
            expected: [
                'Validation Error: Account can not be integrated as it used XML indexing. JSON indexing is required. '
                . 'Please contact support to upgrade your account https://help.klevu.com/',
            ],
            actual: $response->getMessages(),
        );
    }

    public function testExecute_ReturnsInfo_WhenAccountNotFound(): void
    {
        $apiKey = 'klevu-123456789';
        $this->mockLogger->expects($this->once())
            ->method('error');
        $webApi = $this->instantiateCheckApiKeysWebApi(arguments: [
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $apiKey,
            authKey: $this->generateAuthKey(127),
        );

        $this->assertSame(expected: 404, actual: $response->getCode());
        $this->assertSame(expected: 'error', actual: $response->getStatus());
        $this->assertSame(
            expected: [sprintf('Account Not Found: Klevu account not found for key "%s".', $apiKey)],
            actual: $response->getMessages(),
        );
    }

    public function testExecute_ReturnsError_ForBadRequest(): void
    {
        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $jsApiKey,
                'restAuthKey' => $restAuthKey,
            ],
        );

        $exception = new BadRequestException(
            message: 'Bad Request',
            code: 400,
        );

        $mockSdkAccountLookupService = $this->getMockBuilder(AccountLookupService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSdkAccountLookupService->expects($this->once())
            ->method('execute')
            ->with($accountCredentials)
            ->willThrowException($exception);

        $accountLookupAction = $this->objectManager->create(type: AccountLookupActionInterface::class, arguments: [
            'accountLookupService' => $mockSdkAccountLookupService,
        ]);
        $checkApiKeysService = $this->objectManager->create(CheckApiKeysService::class, [
            'accountLookupAction' => $accountLookupAction,
        ]);

        $this->mockLogger->expects($this->once())
            ->method('error');

        $webApi = $this->instantiateCheckApiKeysWebApi(arguments: [
            'apiKeysService' => $checkApiKeysService,
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
        );
        $this->assertSame(expected: 400, actual: $response->getCode());
        $this->assertSame(expected: 'error', actual: $response->getStatus());
        $this->assertSame(
            expected: ['The request is invalid and was rejected by Klevu.'],
            actual: $response->getMessages(),
        );
    }

    public function testExecute_ReturnsError_ForBadResponse(): void
    {
        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $jsApiKey,
                'restAuthKey' => $restAuthKey,
            ],
        );

        $exception = new BadResponseException(
            message: 'Bad Response',
            code: 400,
        );
        $mockSdkAccountLookupService = $this->getMockBuilder(AccountLookupService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSdkAccountLookupService->expects($this->once())
            ->method('execute')
            ->with($accountCredentials)
            ->willThrowException($exception);

        $accountLookupAction = $this->objectManager->create(type: AccountLookupActionInterface::class, arguments: [
            'accountLookupService' => $mockSdkAccountLookupService,
        ]);
        $checkApiKeysService = $this->objectManager->create(CheckApiKeysService::class, [
            'accountLookupAction' => $accountLookupAction,
        ]);

        $this->mockLogger->expects($this->once())
            ->method('error');

        $webApi = $this->instantiateCheckApiKeysWebApi(arguments: [
            'apiKeysService' => $checkApiKeysService,
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
        );
        $this->assertSame(expected: 500, actual: $response->getCode());
        $this->assertSame(expected: 'error', actual: $response->getStatus());
        $this->assertSame(
            expected: ['The Klevu API did not respond in an expected manner.'],
            actual: $response->getMessages(),
        );
    }

    public function testExecute_ReturnsApiKeys(): void
    {
        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $accountFactory = new AccountFactory();
        $mockAccount = $accountFactory->create(data: [
            'jsApiKey' => $jsApiKey,
            'restAuthKey' => $restAuthKey,
            'platform' => 'magento',
            'active' => true,
            'companyName' => 'Klevu',
            'email' => 'user@klevu.com',
            'analyticsUrl' => 'stats.ksearchnet.com',
            'indexingUrl' => 'indexing.ksearchnet.com',
            'jsUrl' => 'js.klevu.com',
            'searchUrl' => 'search.klevu.com',
            'smartCategoryMerchandisingUrl' => 'catnav.klevu.com',
            'tiersUrl' => 'tiers.klevu.com',
            'indexingVersion' => '3',
        ]);

        $accountCredentialsFactory = $this->objectManager->get(AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $jsApiKey,
                'restAuthKey' => $restAuthKey,
            ],
        );

        $mockSdkAccountLookupService = $this->getMockBuilder(AccountLookupService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSdkAccountLookupService->expects($this->once())
            ->method('execute')
            ->with($accountCredentials)
            ->willReturn($mockAccount);

        $accountLookupAction = $this->objectManager->create(type: AccountLookupActionInterface::class, arguments: [
            'accountLookupService' => $mockSdkAccountLookupService,
        ]);
        $checkApiKeysService = $this->objectManager->create(type: CheckApiKeysService::class, arguments: [
            'accountLookupAction' => $accountLookupAction,
        ]);

        $this->mockLogger->expects($this->never())->method('error');
        $this->mockLogger->expects($this->never())->method('info');

        $webApi = $this->instantiateCheckApiKeysWebApi(arguments: [
            'apiKeysService' => $checkApiKeysService,
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
        );
        $this->assertSame(expected: 200, actual: $response->getCode());
        $this->assertSame(expected: 'success', actual: $response->getStatus());
        $this->assertSame(
            expected: ['Account retrieved for Klevu: user@klevu.com. Account is active for Magento.'],
            actual: $response->getMessages(),
        );
        $data = $response->getData();
        $this->assertIsArray(actual: $data);
        $this->assertArrayHasKey(key: 'account', array: $data);
        $account = $data['account'];
        $this->assertIsArray(actual: $account);
        $this->assertArrayHasKey(key: 'active', array: $account);
        $this->assertTrue(condition: $account['active'], message: "Is Active");
        $this->assertArrayHasKey(key: 'platform', array: $account);
        $this->assertSame(expected: 'magento', actual: strtolower($account['platform']));
        $this->assertArrayHasKey(key: 'apiKey', array: $account);
        $this->assertSame(expected: $jsApiKey, actual: $account['apiKey']);
        $this->assertArrayHasKey(key: 'authKey', array: $account);
        $this->assertSame(expected: $restAuthKey, actual: $account['authKey']);
        $this->assertArrayHasKey(key: 'company', array: $account);
        $this->assertSame(expected: 'Klevu', actual: $account['company']);
        $this->assertArrayHasKey(key: 'email', array: $account);
        $this->assertSame(expected: 'user@klevu.com', actual: $account['email']);
        $this->assertArrayHasKey(key: 'indexingVersion', array: $account);
        $this->assertSame(expected: '3', actual: $account['indexingVersion']);
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
        while (strlen(string: $return) < $length) {
            $return .= substr(
                string: str_shuffle(string: $characters),
                offset: 0,
                length: $length - strlen(string: $return),
            );
        }

        return $return;
    }

    /**
     * @param mixed[] $arguments
     *
     * @return CheckApiKeys
     */
    private function instantiateCheckApiKeysWebApi(?array $arguments = []): CheckApiKeys
    {
        return $this->objectManager->create(
            type: CheckApiKeys::class,
            arguments: $arguments,
        );
    }
}
