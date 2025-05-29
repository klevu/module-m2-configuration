<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Action\Sdk\Account;

use Klevu\Configuration\Exception\Integration\InactiveAccountException;
use Klevu\Configuration\Exception\Integration\InvalidPlatformException;
use Klevu\Configuration\Service\Action\Sdk\Account\AccountLookupAction;
use Klevu\Configuration\Service\Action\Sdk\Account\AccountLookupActionInterface;
use Klevu\Configuration\Service\Provider\ApiKeyProvider;
use Klevu\Configuration\Service\Provider\AuthKeyProvider;
use Klevu\Configuration\Service\Provider\Sdk\BaseUrlsProvider;
use Klevu\PhpSDK\Api\Service\Account\AccountLookupServiceInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Model\AccountCredentialsFactory;
use Klevu\PhpSDK\Model\AccountFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\Configuration\Service\Action\AccountLookupAction
 */
class AccountLookupActionTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    public function testImplements_AccountLookupActionInterface(): void
    {
        $this->assertInstanceOf(
            expected: AccountLookupActionInterface::class,
            actual: $this->instantiateAccountLookupAction(),
        );
    }

    public function testPreference_ForAccountLookupActionInterface(): void
    {
        $this->assertInstanceOf(
            expected: AccountLookupAction::class,
            actual: $this->objectManager->get(type: AccountLookupActionInterface::class),
        );
    }

    public function testExecute_ThrowsException_WhenApiKeyEmpty(): void
    {
        try {
            $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
            $accountCredentials = $accountCredentialsFactory->create(
                data: [
                    'jsApiKey' => '',
                    'restAuthKey' => $this->generateAuthKey(length: 10),
                ],
            );

            $action = $this->instantiateAccountLookupAction();
            $action->execute(accountCredentials: $accountCredentials);
        } catch (InvalidDataValidationException $exception) {
            $this->assertSame(expected: 'Data is not valid', actual: $exception->getMessage());
            $this->assertContains(needle: 'JS API Key must not be empty', haystack: $exception->getErrors());
        }
    }

    /**
     * @dataProvider testExecute_ThrowsException_WhenApiKeyInvalid_dataProvider
     */
    public function testExecute_ThrowsException_WhenApiKeyInvalid(string $invalidApiKey): void
    {
        try {
            $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
            $accountCredentials = $accountCredentialsFactory->create(
                data: [
                    'jsApiKey' => $invalidApiKey,
                    'restAuthKey' => $this->generateAuthKey(length: 10),
                ],
            );

            $action = $this->instantiateAccountLookupAction();
            $action->execute(accountCredentials: $accountCredentials);
        } catch (InvalidDataValidationException $exception) {
            $this->assertSame(expected: 'Data is not valid', actual: $exception->getMessage());
            $this->assertContains(needle: 'JS API Key is not valid', haystack: $exception->getErrors());
        }
    }

    /**
     * @return string[][]
     */
    public function testExecute_ThrowsException_WhenApiKeyInvalid_dataProvider(): array
    {
        return [
            ['eyfywuef'],
            ['klevu'],
            ['klevu-none-digits'],
            ['klevu-12345678909876543211234567890'],
        ];
    }

    public function testExecute_ThrowsException_WhenAuthKeyEmpty(): void
    {
        try {
            $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
            $accountCredentials = $accountCredentialsFactory->create(
                data: [
                    'jsApiKey' => 'klevu-1234567890',
                    'restAuthKey' => '',
                ],
            );

            $action = $this->instantiateAccountLookupAction();
            $action->execute(accountCredentials: $accountCredentials);
        } catch (InvalidDataValidationException $exception) {
            $this->assertSame(expected: 'Data is not valid', actual: $exception->getMessage());
            $this->assertContains(needle: 'REST AUTH Key must not be empty', haystack: $exception->getErrors());
        }
    }

    /**
     * @dataProvider testExecute_ThrowsException_WhenAuthKeyInvalid_dataProvider
     */
    public function testExecute_ThrowsException_WhenAuthKeyInvalid(string $invalidAuthKey): void
    {
        try {
            $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
            $accountCredentials = $accountCredentialsFactory->create(
                data: [
                    'jsApiKey' => 'klevu-1234567890',
                    'restAuthKey' => $invalidAuthKey,
                ],
            );

            $action = $this->instantiateAccountLookupAction();
            $action->execute(accountCredentials: $accountCredentials);
        } catch (InvalidDataValidationException $exception) {
            $this->assertSame(expected: 'Data is not valid', actual: $exception->getMessage());
            $this->assertContains(needle: 'REST AUTH Key is not valid', haystack: $exception->getErrors());
        }
    }

    /**
     * @return string[][]
     */
    public function testExecute_ThrowsException_WhenAuthKeyInvalid_dataProvider(): array
    {
        return [
            [$this->generateAuthKey(length: 9)],
            [$this->generateAuthKey(length: 129)],
            [$this->generateAuthKey(length: 10) . '!'],
            [$this->generateAuthKey(length: 10) . '@'],
            [$this->generateAuthKey(length: 10) . '$'],
        ];
    }

    public function testExecute_ThrowsException_WhenAccountDoesNotExist(): void
    {
        $this->expectException(AccountNotFoundException::class);

        $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => 'klevu-1234567890',
                'restAuthKey' => $this->generateAuthKey(length: 10),
            ],
        );

        $action = $this->instantiateAccountLookupAction();
        $action->execute(accountCredentials: $accountCredentials);
    }

    public function testExecute_ThrowsException_WhenAccountPlatformIsNotMagento(): void
    {
        $this->expectException(InvalidPlatformException::class);
        $this->expectExceptionMessage(
            'Account can not be integrated with Magento as it is not assigned to the Magento platform.',
        );

        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $accountFactory = new AccountFactory();
        $mockAccount = $accountFactory->create(data: [
            'jsApiKey' => $jsApiKey,
            'restAuthKey' => $restAuthKey,
            'platform' => 'shopify',
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

        $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $jsApiKey,
                'restAuthKey' => $restAuthKey,
            ],
        );

        $mockAccountLookupService = $this->getMockBuilder(AccountLookupServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccountLookupService->expects($this->once())
            ->method('execute')
            ->with($accountCredentials)
            ->willReturn($mockAccount);

        $accountLookupAction = $this->instantiateAccountLookupAction(arguments: [
            'accountLookupService' => $mockAccountLookupService,
        ]);
        $accountLookupAction->execute(accountCredentials: $accountCredentials);
    }

    public function testExecute_ThrowsException_WhenAccountInactive(): void
    {
        $this->expectException(InactiveAccountException::class);
        $this->expectExceptionMessage(
            'Account can not be integrated as it is inactive.',
        );

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

        $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $jsApiKey,
                'restAuthKey' => $restAuthKey,
            ],
        );

        $mockAccountLookupService = $this->getMockBuilder(AccountLookupServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccountLookupService->expects($this->once())
            ->method('execute')
            ->with($accountCredentials)
            ->willReturn($mockAccount);

        $accountLookupAction = $this->instantiateAccountLookupAction(arguments: [
            'accountLookupService' => $mockAccountLookupService,
        ]);
        $accountLookupAction->execute(accountCredentials: $accountCredentials);
    }

    public function testExecute_ThrowsException_WhenIndexingVersionIsInvalid(): void
    {
        $this->expectException(InactiveAccountException::class);
        $this->expectExceptionMessage(
            'Account can not be integrated as it used XML indexing. JSON indexing is required. '
            . 'Please contact support to upgrade your account https://help.klevu.com/',
        );

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

        $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $jsApiKey,
                'restAuthKey' => $restAuthKey,
            ],
        );

        $mockAccountLookupService = $this->getMockBuilder(AccountLookupServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccountLookupService->expects($this->once())
            ->method('execute')
            ->with($accountCredentials)
            ->willReturn($mockAccount);

        $accountLookupAction = $this->instantiateAccountLookupAction(arguments: [
            'accountLookupService' => $mockAccountLookupService,
        ]);
        $accountLookupAction->execute(accountCredentials: $accountCredentials);
    }

    public function testExecute_ReturnsAccountDetails_ForRealApi(): void
    {
        /**
         * This test requires your Klevu API keys
         * These API keys can be set in dev/tests/integration/phpunit.xml
         * <phpunit>
         *     <testsuites>
         *      ...
         *     </testsuites>
         *     <php>
         *         ...
         *         <env name="KLEVU_JS_API_KEY" value="klevu-js-api-key" force="true" />
         *         <env name="KLEVU_REST_API_KEY" value="klevu-rest-auth-key" force="true" />
         *         <env name="KLEVU_API_REST_URL" value="https://api.ksearchnet.com" force="true" />
         *     </php>
         */
        $restApiKey = getenv('KLEVU_REST_API_KEY');
        $jsApiKey = getenv('KLEVU_JS_API_KEY');
        $restApiUrl = getenv('KLEVU_REST_API_URL');
        if (!$restApiKey || !$jsApiKey || !$restApiUrl) {
            $this->markTestSkipped('Klevu API keys are not set in `dev/tests/integration/phpunit.xml`. Test Skipped');
        }

        ConfigFixture::setGlobal(
            path: ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY,
            value: $jsApiKey,
        );
        ConfigFixture::setGlobal(
            path: AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY,
            value: $restApiKey,
        );
        ConfigFixture::setGlobal(
            path: BaseUrlsProvider::CONFIG_XML_PATH_URL_API,
            value: $restApiUrl,
        );

        $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $jsApiKey,
                'restAuthKey' => $restApiKey,
            ],
        );

        /** @var AccountLookupAction $accountLookupAction */
        $accountLookupAction = $this->objectManager->get(type: AccountLookupAction::class);
        $account = $accountLookupAction->execute(accountCredentials: $accountCredentials);

        $this->assertTrue(condition: $account->isActive(), message: "Is Active");
        $this->assertSame(expected: 'magento', actual: strtolower($account->getPlatform()));
        $this->assertSame(expected: $jsApiKey, actual: $account->getJsApiKey());
        $this->assertSame(expected: $restApiKey, actual: $account->getRestAuthKey());
        $this->assertNotNull(actual: $account->getEmail(), message: 'Email');
        $this->assertNotNull(actual: $account->getAnalyticsUrl(), message: 'Analytics URL');
        $this->assertNotNull(actual: $account->getIndexingUrl(), message: 'Indexing URL');
        $this->assertNotNull(actual: $account->getJsUrl(), message: 'Js URL');
        $this->assertNotNull(actual: $account->getSearchUrl(), message: 'Search URL');
        $this->assertNotNull(
            actual: $account->getSmartCategoryMerchandisingUrl(),
            message: 'Smart Category Merchandising URL',
        );
        $this->assertNotNull(actual: $account->getTiersUrl(), message: 'Tiers URL');
        $this->assertSame(expected: '3', actual: $account->getIndexingVersion());
    }

    public function testExecute_ReturnsAccountDetails_ForMockApi(): void
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

        $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $jsApiKey,
                'restAuthKey' => $restAuthKey,
            ],
        );

        $mockAccountLookupService = $this->getMockBuilder(AccountLookupServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccountLookupService->expects($this->once())
            ->method('execute')
            ->with($accountCredentials)
            ->willReturn($mockAccount);

        $accountLookupAction = $this->instantiateAccountLookupAction(arguments: [
            'accountLookupService' => $mockAccountLookupService,
        ]);
        $account = $accountLookupAction->execute(accountCredentials: $accountCredentials);

        $this->assertTrue(condition: $account->isActive(), message: "Is Active");
        $this->assertSame(expected: 'magento', actual: strtolower($account->getPlatform()));
        $this->assertSame(expected: $jsApiKey, actual: $account->getJsApiKey());
        $this->assertSame(expected: $restAuthKey, actual: $account->getRestAuthKey());
        $this->assertSame(expected: 'Klevu', actual: $account->getCompanyName());
        $this->assertSame(expected: 'user@klevu.com', actual: $account->getEmail());
        $this->assertSame(expected: 'stats.ksearchnet.com', actual: $account->getAnalyticsUrl());
        $this->assertSame(expected: 'indexing.ksearchnet.com', actual: $account->getIndexingUrl());
        $this->assertSame(expected: 'js.klevu.com', actual: $account->getJsUrl());
        $this->assertSame(expected: 'search.klevu.com', actual: $account->getSearchUrl());
        $this->assertSame(expected: 'catnav.klevu.com', actual: $account->getSmartCategoryMerchandisingUrl());
        $this->assertSame(expected: 'tiers.klevu.com', actual: $account->getTiersUrl());
        $this->assertSame(expected: '3', actual: $account->getIndexingVersion());
    }

    public function testExecute_ReturnsAccountDetails_ForMockApi_IndexingVersionNotSet(): void
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
            'indexingVersion' => null,
            'defaultCurrency' => null,
        ]);

        $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $jsApiKey,
                'restAuthKey' => $restAuthKey,
            ],
        );

        $mockAccountLookupService = $this->getMockBuilder(AccountLookupServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccountLookupService->expects($this->once())
            ->method('execute')
            ->with($accountCredentials)
            ->willReturn($mockAccount);

        $accountLookupAction = $this->instantiateAccountLookupAction(arguments: [
            'accountLookupService' => $mockAccountLookupService,
        ]);
        $account = $accountLookupAction->execute(accountCredentials: $accountCredentials);

        $this->assertTrue(condition: $account->isActive(), message: "Is Active");
        $this->assertSame(expected: 'magento', actual: strtolower($account->getPlatform()));
        $this->assertSame(expected: $jsApiKey, actual: $account->getJsApiKey());
        $this->assertSame(expected: $restAuthKey, actual: $account->getRestAuthKey());
        $this->assertSame(expected: 'Klevu', actual: $account->getCompanyName());
        $this->assertSame(expected: 'user@klevu.com', actual: $account->getEmail());
        $this->assertSame(expected: 'stats.ksearchnet.com', actual: $account->getAnalyticsUrl());
        $this->assertSame(expected: 'indexing.ksearchnet.com', actual: $account->getIndexingUrl());
        $this->assertSame(expected: 'js.klevu.com', actual: $account->getJsUrl());
        $this->assertSame(expected: 'search.klevu.com', actual: $account->getSearchUrl());
        $this->assertSame(expected: 'catnav.klevu.com', actual: $account->getSmartCategoryMerchandisingUrl());
        $this->assertSame(expected: 'tiers.klevu.com', actual: $account->getTiersUrl());
        $this->assertSame(expected: '', actual: $account->getIndexingVersion());
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
     * @return \Klevu\Configuration\Service\Action\Sdk\Account\AccountLookupAction
     */
    private function instantiateAccountLookupAction(?array $arguments = []): AccountLookupAction
    {
        return $this->objectManager->create(
            type: AccountLookupAction::class,
            arguments: $arguments,
        );
    }
}
