<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service;

use Klevu\Configuration\Exception\Integration\InactiveAccountException;
use Klevu\Configuration\Exception\Integration\InvalidPlatformException;
use Klevu\Configuration\Service\Action\Sdk\Account\AccountLookupAction;
use Klevu\Configuration\Service\CheckApiKeysService;
use Klevu\Configuration\Service\CheckApiKeysServiceInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountLookupServiceInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Model\AccountCredentialsFactory;
use Klevu\PhpSDK\Model\AccountFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\CheckApiKeysService
 */
class CheckApiKeysTest extends TestCase
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

    public function testImplements_CheckApiKeysServiceInterface(): void
    {
        $this->assertInstanceOf(
            expected: CheckApiKeysServiceInterface::class,
            actual: $this->instantiateCheckApiKeysService(),
        );
    }

    public function testPreference_ForCheckApiKeysServiceInterface(): void
    {
        $this->assertInstanceOf(
            expected: CheckApiKeysService::class,
            actual: $this->objectManager->get(type: CheckApiKeysServiceInterface::class),
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecute_ThrowsException_WhenApiKeyEmpty(): void
    {
        try {
            $service = $this->instantiateCheckApiKeysService();
            $service->execute(
                apiKey: '',
                authKey: $this->generateAuthKey(length: 10),
            );
        } catch (InvalidDataValidationException $exception) {
            $this->assertSame(expected: 'Data is not valid', actual: $exception->getMessage());
            $this->assertContains(needle: 'JS API Key must not be empty', haystack: $exception->getErrors());
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @dataProvider testExecute_ThrowsException_WhenApiKeyInvalid_dataProvider
     */
    public function testExecute_ThrowsException_WhenApiKeyInvalid(string $invalidApiKey): void
    {
        try {
            $service = $this->instantiateCheckApiKeysService();
            $service->execute(
                apiKey: $invalidApiKey,
                authKey: $this->generateAuthKey(length: 10),
            );
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

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecute_ThrowsException_WhenAuthKeyEmpty(): void
    {
        try {
            $service = $this->instantiateCheckApiKeysService();
            $service->execute(
                apiKey: 'klevu-1234567890',
                authKey: '',
            );
        } catch (InvalidDataValidationException $exception) {
            $this->assertSame(expected: 'Data is not valid', actual: $exception->getMessage());
            $this->assertContains(needle: 'REST AUTH Key must not be empty', haystack: $exception->getErrors());
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @dataProvider testExecute_ThrowsException_WhenAuthKeyInvalid_dataProvider
     */
    public function testExecute_ThrowsException_WhenAuthKeyInvalid(string $invalidAuthKey): void
    {
        try {
            $service = $this->instantiateCheckApiKeysService();
            $service->execute(
                apiKey: 'klevu-1234567890',
                authKey: $invalidAuthKey,
            );
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

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecute_ThrowsException_WhenAccountDoesNotExist(): void
    {
        $this->expectException(AccountNotFoundException::class);

        $service = $this->instantiateCheckApiKeysService();
        $service->execute(
            apiKey: 'klevu-1234567890',
            authKey: $this->generateAuthKey(length: 10),
        );
    }

    public function testExecute_ThrowsException_WhenPlatformIsNotMagento(): void
    {
        $this->expectException(InvalidPlatformException::class);
        $this->expectExceptionMessage(
            'Account can not be integrated with Magento as it is not assigned to the Magento platform.',
        );

        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $accountFactory = new AccountFactory();
        $mockAccount = $accountFactory->create([
            'jsApiKey' => $jsApiKey,
            'restAuthKey' => $restAuthKey,
            'platform' => 'bigcommerce',
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

        $accountLookupAction = $this->objectManager->create(type: AccountLookupAction::class, arguments: [
            'accountLookupService' => $mockAccountLookupService,
        ]);

        $service = $this->instantiateCheckApiKeysService(arguments: [
            'accountLookupAction' => $accountLookupAction,
        ]);
        $service->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
        );
    }

    public function testExecute_ThrowsException_WhenAccountIsInactive(): void
    {
        $this->expectException(InactiveAccountException::class);
        $this->expectExceptionMessage(
            'Account can not be integrated as it is inactive.',
        );

        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $accountFactory = new AccountFactory();
        $mockAccount = $accountFactory->create([
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

        $accountLookupAction = $this->objectManager->create(type: AccountLookupAction::class, arguments: [
            'accountLookupService' => $mockAccountLookupService,
        ]);

        $service = $this->instantiateCheckApiKeysService(arguments: [
            'accountLookupAction' => $accountLookupAction,
        ]);
        $service->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
        );
    }

    public function testExecute_ReturnsAccount(): void
    {
        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $accountFactory = new AccountFactory();
        $mockAccount = $accountFactory->create([
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

        $accountLookupAction = $this->objectManager->create(type: AccountLookupAction::class, arguments: [
            'accountLookupService' => $mockAccountLookupService,
        ]);

        $service = $this->instantiateCheckApiKeysService(arguments: [
            'accountLookupAction' => $accountLookupAction,
        ]);
        $account = $service->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
        );

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
     * @return CheckApiKeysService
     */
    private function instantiateCheckApiKeysService(?array $arguments = []): CheckApiKeysService
    {
        return $this->objectManager->create(
            type: CheckApiKeysService::class,
            arguments: $arguments,
        );
    }
}
