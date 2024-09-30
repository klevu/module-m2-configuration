<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Action\Sdk;

use Klevu\Configuration\Service\Action\Sdk\AccountDetailsAction;
use Klevu\Configuration\Service\Action\Sdk\AccountDetailsActionInterface;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountFeaturesServiceInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountFeaturesServiceInterfaceFactory;
use Klevu\PhpSDK\Api\Service\Account\AccountLookupServiceInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Klevu\PhpSDK\Model\Account\AccountFeaturesFactory;
use Klevu\PhpSDK\Model\AccountFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class AccountDetailsActionTest extends TestCase
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

    public function testImplements_AccountDetailsActionInterface(): void
    {
        $this->assertInstanceOf(
            expected: AccountDetailsActionInterface::class,
            actual: $this->instantiateAccountDetailsAction(),
        );
    }

    public function testPreference_ForAccountDetailsActionInterface(): void
    {
        $this->assertInstanceOf(
            expected: AccountDetailsAction::class,
            actual: $this->objectManager->get(type: AccountDetailsActionInterface::class),
        );
    }

    public function testExecute_ThrowsException_WhenApiKeyEmpty(): void
    {
        try {
            $action = $this->instantiateAccountDetailsAction();
            $action->execute(
                apiKey: '',
                authKey: $this->generateAuthKey(length: 10),
            );
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
            $action = $this->instantiateAccountDetailsAction();
            $action->execute(
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

    public function testExecute_ThrowsException_WhenAuthKeyEmpty(): void
    {
        try {
            $action = $this->instantiateAccountDetailsAction();
            $action->execute(
                apiKey: 'klevu-1234567890',
                authKey: '',
            );
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
            $action = $this->instantiateAccountDetailsAction();
            $action->execute(
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

        $action = $this->instantiateAccountDetailsAction();
        $action->execute(
            apiKey: 'klevu-1234567890',
            authKey: $this->generateAuthKey(length: 10),
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecute_ReturnsAccountData(): void
    {
        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);
        $accountData = [
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
        ];
        $mockAccount = $this->createAccount(accountData: $accountData);
        $mockAccountLookup = $this->getMockBuilder(AccountLookupServiceInterface::class)
            ->getMock();
        $mockAccountLookup->expects($this->once())
            ->method('execute')
            ->willReturn($mockAccount);
        $this->objectManager->addSharedInstance(
            instance: $mockAccountLookup,
            className: AccountLookupServiceInterface::class,
            forPreference: true,
        );
        $this->objectManager->addSharedInstance(
            instance: $mockAccountLookup,
            className: 'Klevu\Configuration\Service\Account\AccountLookupService', // virtualType
            forPreference: true,
        );

        $accountFeatures = [
            'smartCategoryMerchandising' => true,
            'smartRecommendations' => true,
            'preserveLayout' => true,
        ];
        $mockAccountFeatures = $this->createAccountFeatures(accountFeatures: $accountFeatures);
        $mockAccountFeaturesService = $this->getMockBuilder(AccountFeaturesServiceInterface::class)
            ->getMock();
        $mockAccountFeaturesService->expects($this->once())
            ->method('execute')
            ->willReturn($mockAccountFeatures);
        $mockAccountFeaturesServiceFactory = $this->getMockBuilder(AccountFeaturesServiceInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccountFeaturesServiceFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockAccountFeaturesService);

        $this->objectManager->addSharedInstance(
            instance: $mockAccountFeaturesServiceFactory,
            className: AccountFeaturesServiceInterfaceFactory::class,
            forPreference: true,
        );
        $this->objectManager->addSharedInstance(
            instance: $mockAccountFeaturesService,
            className: 'Klevu\Configuration\Service\Account\AccountFeaturesService', // virtualType
            forPreference: true,
        );

        $action = $this->instantiateAccountDetailsAction();
        $account = $action->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
        );
        // returned data
        $this->assertSame(expected: $jsApiKey, actual: $account->getJsApiKey());
        $this->assertSame(expected: $restAuthKey, actual: $account->getRestAuthKey());
        $this->assertSame(expected: 'user@klevu.com', actual: $account->getEmail());
        $this->assertSame(
            expected: 'catnav.klevu.com',
            actual: $account->getSmartCategoryMerchandisingUrl(),
        );
        $accountFeatures = $account->getAccountFeatures();
        $this->assertTrue(
            condition: $accountFeatures->smartCategoryMerchandising,
            message: 'Smart Category Merchandising',
        );
        $this->assertTrue(
            condition: $accountFeatures->preserveLayout,
            message: 'Preserve Layout',
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
     * @param mixed[] $accountData
     *
     * @return AccountInterface
     */
    private function createAccount(array $accountData): AccountInterface
    {
        $accountFactory = new AccountFactory();

        return $accountFactory->create(data: $accountData);
    }

    /**
     * @param mixed[] $accountFeatures
     *
     * @return AccountFeatures
     */
    private function createAccountFeatures(array $accountFeatures): AccountFeatures
    {
        $accountFeaturesFactory = new AccountFeaturesFactory();

        return $accountFeaturesFactory->create(data: $accountFeatures);
    }

    /**
     * @param mixed[] $arguments
     *
     * @return AccountDetailsAction
     */
    private function instantiateAccountDetailsAction(array $arguments = []): AccountDetailsAction
    {
        return $this->objectManager->create(
            type: AccountDetailsAction::class,
            arguments: $arguments,
        );
    }
}
