<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\WebApi\Integration;

use Klevu\Configuration\Api\IntegrateApiKeysInterface;
use Klevu\Configuration\Service\Action\Sdk\Account\AccountFeaturesActionInterface;
use Klevu\Configuration\Service\Action\Sdk\Account\AccountLookupActionInterface;
use Klevu\Configuration\Service\Action\Sdk\AccountDetailsAction;
use Klevu\Configuration\Service\IntegrateApiKeysService;
use Klevu\Configuration\WebApi\Integration\IntegrateApiKeys;
use Klevu\PhpSDK\Api\Service\Account\AccountFeaturesServiceInterfaceFactory;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Model\Account\AccountFeaturesFactory;
use Klevu\PhpSDK\Model\AccountCredentialsFactory;
use Klevu\PhpSDK\Model\AccountFactory;
use Klevu\PhpSDK\Service\Account\AccountFeaturesService;
use Klevu\PhpSDK\Service\Account\AccountLookupService;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\AttributeApiCallTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Klevu\Configuration\WebApi\Integration\IntegrateApiKeys
 * @runTestsInSeparateProcesses
 */
class IntegrateApiKeysTest extends TestCase
{
    use AttributeApiCallTrait;
    use StoreTrait;

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
        $this->storeFixturesPool = $this->objectManager->create(StoreFixturesPool::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        $this->storeFixturesPool->rollback();
    }

    public function testImplements_IntegrateApiKeysInterface(): void
    {
        $this->assertInstanceOf(
            expected: IntegrateApiKeysInterface::class,
            actual: $this->instantiateIntegrateApiKeys(),
        );
    }

    public function testPreference_ForIntegrateApiKeysInterface(): void
    {
        $this->assertInstanceOf(
            IntegrateApiKeys::class,
            $this->objectManager->create(IntegrateApiKeysInterface::class),
        );
    }

    public function testExecute_ReturnsError_WhenValidationFails_ApiKeyEmpty(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('error');
        $webApi = $this->instantiateIntegrateApiKeys([
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: '',
            authKey: $this->generateAuthKey(length: 10),
            scopeId: 1,
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
        $webApi = $this->instantiateIntegrateApiKeys([
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $invalidApiKey,
            authKey: $this->generateAuthKey(length: 10),
            scopeId: 1,
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
        $webApi = $this->instantiateIntegrateApiKeys([
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: 'klevu-123456789',
            authKey: '',
            scopeId: 1,
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
        $webApi = $this->instantiateIntegrateApiKeys(arguments: [
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: 'klevu-123456789',
            authKey: $invalidAuthKey,
            scopeId: 1,
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

    /**
     * @dataProvider testExecute_ReturnsError_WhenValidationFails_ScopeTypeSetToWebsite_dataProvider
     */
    public function testExecute_ReturnsError_WhenValidationFails_ScopeTypeSetToWebsite(string $invalidScopeType): void
    {
        // @TODO remove when channels are available
        $this->mockLogger->expects($this->once())
            ->method('error');
        $webApi = $this->instantiateIntegrateApiKeys(arguments: [
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: 'klevu-123456789',
            authKey: 'klevu-test-auth-key',
            scopeId: 1,
            scopeType: $invalidScopeType,
        );

        $this->assertSame(expected: 400, actual: $response->getCode());
        $this->assertSame(expected: 'error', actual: $response->getStatus());
        $this->assertSame(
            expected: [
                sprintf(
                    'Validation Error: Scope Validation Exception: Invalid Scope provided.'
                    . ' Expected one of %s; received %s.',
                    implode(', ', [ScopeInterface::SCOPE_STORE, ScopeInterface::SCOPE_STORES,]),
                    $invalidScopeType,
                ),
            ],
            actual: $response->getMessages(),
        );
    }

    /**
     * @return string[][]
     */
    public function testExecute_ReturnsError_WhenValidationFails_ScopeTypeSetToWebsite_dataProvider(): array
    {
        // @TODO remove when channels are available
        return [
            [ScopeInterface::SCOPE_WEBSITE],
            [ScopeInterface::SCOPE_WEBSITES],
        ];
    }

    /**
     * @dataProvider testExecute_ReturnsError_WhenValidationFails_ScopeTypeInvalid_dataProvider
     */
    public function testExecute_ReturnsError_WhenValidationFails_ScopeTypeInvalid(string $invalidScopeType): void
    {
        $this->mockLogger->expects($this->once())
            ->method('error');
        $webApi = $this->instantiateIntegrateApiKeys(arguments: [
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: 'klevu-123456789',
            authKey: 'klevu-test-auth-key',
            scopeId: 1,
            scopeType: $invalidScopeType,
        );

        $this->assertSame(expected: 500, actual: $response->getCode());
        $this->assertSame(expected: 'error', actual: $response->getStatus());
        $this->assertSame(
            expected: ['Internal error: See log for details'],
            actual: $response->getMessages(),
        );
    }

    /**
     * @return string[][]
     */
    public function testExecute_ReturnsError_WhenValidationFails_ScopeTypeInvalid_dataProvider(): array
    {
        return [
            [ScopeInterface::SCOPE_GROUP],
            [ScopeInterface::SCOPE_GROUPS],
            ['default'],
        ];
    }

    public function testExecute_ReturnsInfo_WhenAccountNotFound(): void
    {
        $apiKey = 'klevu-123456789';
        $this->mockLogger->expects($this->once())
            ->method('error');
        $webApi = $this->instantiateIntegrateApiKeys(arguments: [
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $apiKey,
            authKey: $this->generateAuthKey(127),
            scopeId: 1,
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
        $jsApiKey = 'klevu-1234567890' . __LINE__;
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
        $accountDetailsAction = $this->objectManager->create(type: AccountDetailsAction::class, arguments: [
            'accountLookupAction' => $accountLookupAction,
        ]);
        $integrateApiKeysService = $this->objectManager->create(IntegrateApiKeysService::class, [
            'accountDetailsAction' => $accountDetailsAction,
        ]);

        $this->mockLogger->expects($this->once())
            ->method('error');

        $webApi = $this->instantiateIntegrateApiKeys(arguments: [
            'integrateApiKeysService' => $integrateApiKeysService,
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
            scopeId: 1,
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
        $jsApiKey = 'klevu-1234567890' . __LINE__;
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
        $accountDetailsAction = $this->objectManager->create(type: AccountDetailsAction::class, arguments: [
            'accountLookupAction' => $accountLookupAction,
        ]);
        $integrateApiKeysService = $this->objectManager->create(IntegrateApiKeysService::class, [
            'accountDetailsAction' => $accountDetailsAction,
        ]);

        $this->mockLogger->expects($this->once())
            ->method('error');

        $webApi = $this->instantiateIntegrateApiKeys(arguments: [
            'integrateApiKeysService' => $integrateApiKeysService,
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
            scopeId: 1,
        );
        $this->assertSame(expected: 500, actual: $response->getCode());
        $this->assertSame(expected: 'error', actual: $response->getStatus());
        $this->assertSame(
            expected: ['The Klevu API did not respond in an expected manner.'],
            actual: $response->getMessages(),
        );
    }

    public function testExecute_ReturnsError_WhenValidationFails_PlatformIsNotMagento(): void
    {
        $jsApiKey = 'klevu-1234567890' . __LINE__;
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
        ]);

        $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
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

        $accountDetailsAction = $this->objectManager->create(type: AccountDetailsAction::class, arguments: [
            'accountLookupAction' => $accountLookupAction,
        ]);
        $integrateApiKeysService = $this->objectManager->create(IntegrateApiKeysService::class, [
            'accountDetailsAction' => $accountDetailsAction,
        ]);

        $this->mockLogger->expects($this->once())->method('error');
        $this->mockLogger->expects($this->never())->method('info');

        $webApi = $this->instantiateIntegrateApiKeys(arguments: [
            'integrateApiKeysService' => $integrateApiKeysService,
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
            scopeId: 1,
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
        $jsApiKey = 'klevu-1234567890' . __LINE__;
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

        $accountDetailsAction = $this->objectManager->create(type: AccountDetailsAction::class, arguments: [
            'accountLookupAction' => $accountLookupAction,
        ]);
        $integrateApiKeysService = $this->objectManager->create(type: IntegrateApiKeysService::class, arguments: [
            'accountDetailsAction' => $accountDetailsAction,
        ]);

        $this->mockLogger->expects($this->once())->method('error');
        $this->mockLogger->expects($this->never())->method('info');

        $webApi = $this->instantiateIntegrateApiKeys(arguments: [
            'integrateApiKeysService' => $integrateApiKeysService,
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
            scopeId: 1,
        );

        $this->assertSame(expected: 400, actual: $response->getCode());
        $this->assertSame(expected: 'error', actual: $response->getStatus());
        $this->assertSame(
            expected: ['Validation Error: Account can not be integrated as it is inactive.'],
            actual: $response->getMessages(),
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecute_ReturnsExpectedData(): void
    {
        $this->createStore(
            storeData: [
                'code' => 'phpunit_test_integrateapikeys',
                'name' => 'PHPUnit Test Store (Integrate API Keys)',
                'is_active' => true,
                'key' => 'phpunit_test_integrateapikeys',
            ],
        );
        $storeFixture = $this->storeFixturesPool->get('phpunit_test_integrateapikeys');
        $store = $storeFixture->get();

        $this->mockSdkAttributeGetApiCall();

        $jsApiKey = 'klevu-1234567890' . __LINE__;
        $restAuthKey = $this->generateAuthKey(length: 10);

        $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $jsApiKey,
                'restAuthKey' => $restAuthKey,
            ],
        );
        $accountFactory = $this->objectManager->create(AccountFactory::class);
        $account = $accountFactory->create(data: [
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
        $mockSdkAccountLookupService = $this->getMockBuilder(AccountLookupService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSdkAccountLookupService->expects($this->once())
            ->method('execute')
            ->with($accountCredentials)
            ->willReturn($account);

        $accountLookupAction = $this->objectManager->create(type: AccountLookupActionInterface::class, arguments: [
            'accountLookupService' => $mockSdkAccountLookupService,
        ]);

        $accountFeaturesFactory = $this->objectManager->create(AccountFeaturesFactory::class);
        $accountFeatures = $accountFeaturesFactory->create(data: [
            'smartCategoryMerchandising' => false,
            'smartRecommendations' => true,
            'preserveLayout' => true,
        ]);
        $mockSdkAccountFeaturesService = $this->getMockBuilder(AccountFeaturesService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSdkAccountFeaturesService->expects($this->once())
            ->method('execute')
            ->with($accountCredentials)
            ->willReturn($accountFeatures);
        $mockAccountFeaturesServiceFactory = $this->getMockBuilder(AccountFeaturesServiceInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccountFeaturesServiceFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockSdkAccountFeaturesService);

        $accountFeaturesAction = $this->objectManager->create(type: AccountFeaturesActionInterface::class, arguments: [
            'accountFeaturesServiceFactory' => $mockAccountFeaturesServiceFactory,
        ]);
        $accountDetailsAction = $this->objectManager->create(type: AccountDetailsAction::class, arguments: [
            'accountLookupAction' => $accountLookupAction,
            'accountFeaturesAction' => $accountFeaturesAction,
        ]);
        $integrateApiKeysService = $this->objectManager->create(IntegrateApiKeysService::class, [
            'accountDetailsAction' => $accountDetailsAction,
        ]);

        $webApi = $this->instantiateIntegrateApiKeys(arguments: [
            'integrateApiKeysService' => $integrateApiKeysService,
            'logger' => $this->mockLogger,
        ]);
        $response = $webApi->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
            scopeId: (int)$store->getId(),
        );

        $this->assertSame(expected: 'success', actual: $response->getStatus());
        $this->assertSame(expected: 200, actual: $response->getCode());
        $this->assertSame(
            expected: ['Account integrated for Klevu: user@klevu.com. Account is active for Magento.'],
            actual: $response->getMessages(),
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
     * @param mixed[]|null $arguments
     *
     * @return IntegrateApiKeys
     */
    private function instantiateIntegrateApiKeys(?array $arguments = []): IntegrateApiKeys
    {
        return $this->objectManager->create(
            type: IntegrateApiKeys::class,
            arguments: $arguments,
        );
    }
}
