<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Action\Sdk\Account;

use Klevu\Configuration\Service\Action\Sdk\Account\AccountFeaturesAction;
use Klevu\Configuration\Service\Action\Sdk\Account\AccountFeaturesActionInterface;
use Klevu\Configuration\Service\Action\Sdk\Account\AccountLookupAction;
use Klevu\Configuration\Service\Provider\ApiKeyProvider;
use Klevu\Configuration\Service\Provider\AuthKeyProvider;
use Klevu\Configuration\Service\Provider\Sdk\BaseUrlsProvider;
use Klevu\Configuration\Service\Provider\Sdk\BaseUrlsProviderFactory;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountFeaturesServiceInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountFeaturesServiceInterfaceFactory;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Klevu\PhpSDK\Model\Account\AccountFeaturesFactory;
use Klevu\PhpSDK\Model\AccountCredentialsFactory;
use Klevu\PhpSDK\Model\AccountFactory;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\Configuration\Service\Action\AccountFeaturesAction
 */
class AccountFeaturesActionTest extends TestCase
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

    public function testImplements_AccountFeaturesActionInterface(): void
    {
        $this->assertInstanceOf(
            expected: AccountFeaturesActionInterface::class,
            actual: $this->instantiateAccountFeaturesAction(),
        );
    }

    public function testPreference_ForAccountFeaturesActionInterface(): void
    {
        $this->assertInstanceOf(
            expected: AccountFeaturesAction::class,
            actual: $this->objectManager->get(type: AccountFeaturesActionInterface::class),
        );
    }

    /**
     * @dataProvider testExecute_ThrowsException_WhenApiKeyInvalid_dataProvider
     */
    public function testExecute_ThrowsException_WhenApiKeyInvalid(string $invalidApiKey): void
    {
        $jsApiKey = $invalidApiKey;
        $restAuthKey = $this->generateAuthKey(length: 10);
        try {
            $mockAccount = $this->getMockAccount(jsApiKey: $jsApiKey, restAuthKey: $restAuthKey);

            $action = $this->instantiateAccountFeaturesAction();
            $action->execute(account: $mockAccount);
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
     * @dataProvider testExecute_ThrowsException_WhenAuthKeyInvalid_dataProvider
     */
    public function testExecute_ThrowsException_WhenAuthKeyInvalid(string $invalidAuthKey): void
    {
        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $invalidAuthKey;
        try {
            $mockAccount = $this->getMockAccount(jsApiKey: $jsApiKey, restAuthKey: $restAuthKey);

            $action = $this->instantiateAccountFeaturesAction();
            $action->execute(account: $mockAccount);
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
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('API request rejected by Klevu API');
        $this->expectExceptionCode(200);

        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $mockAccount = $this->getMockAccount(jsApiKey: $jsApiKey, restAuthKey: $restAuthKey);

        $action = $this->instantiateAccountFeaturesAction();
        $action->execute(account: $mockAccount);
    }

    public function testExecute_ReturnsAccountFeatures_ForRealApi(): void
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
         *         <env name="KLEVU_JS_API_KEY" value="" force="true" />
         *         <env name="KLEVU_REST_API_KEY" value="" force="true" />
         *         <env name="KLEVU_API_REST_URL" value="api.ksearchnet.com" force="true" />
         *         // KLEVU_TIERS_URL only required for none production env
         *         <env name="KLEVU_TIERS_URL" value="tiers.klevu.com" force="true" />
         *     </php>
         */
        $restApiKey = getenv('KLEVU_REST_API_KEY');
        $jsApiKey = getenv('KLEVU_JS_API_KEY');
        $restApiUrl = getenv('KLEVU_REST_API_URL');
        $tiersApiUrl = getenv('KLEVU_TIERS_URL');
        if (!$restApiKey || !$jsApiKey || !$restApiUrl || !$tiersApiUrl) {
            $this->markTestSkipped('Klevu API keys are not set in `dev/tests/integration/phpunit.xml`. Test Skipped');
        }

        $this->expectNotToPerformAssertions();

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
        $tiersApiUrl = getenv('KLEVU_TIERS_URL');
        if ($tiersApiUrl) {
            ConfigFixture::setGlobal(
                path: BaseUrlsProvider::CONFIG_XML_PATH_URL_TIERS,
                value: $tiersApiUrl,
            );
        }
        $accountCredentialsFactory = $this->objectManager->get(type: AccountCredentialsFactory::class);
        $accountCredentials = $accountCredentialsFactory->create(
            data: [
                'jsApiKey' => $jsApiKey,
                'restAuthKey' => $restApiKey,
            ],
        );

        $accountLookupAction = $this->objectManager->get(type: AccountLookupAction::class);
        $account = $accountLookupAction->execute(accountCredentials: $accountCredentials);

        $accountFeaturesAction = $this->objectManager->get(type: AccountFeaturesAction::class);

        $accountFeaturesAction->execute(
            account: $account,
        );
    }

    public function testExecute_ReturnsAccountDetails_ForMockApi(): void
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

        $mockAccount = $this->getMockAccount($jsApiKey, $restAuthKey);

        $featuresFactory = new AccountFeaturesFactory();
        /** @var AccountFeatures $mockFeatures */
        $mockFeatures = $featuresFactory->create(data: [
            'smartCategoryMerchandising' => true,
            'smartRecommendations' => true,
            'preserveLayout' => true,
        ]);

        $mockAccountFeaturesService = $this->getMockBuilder(AccountFeaturesServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccountFeaturesService->expects($this->once())
            ->method('execute')
            ->with($accountCredentials)
            ->willReturn($mockFeatures);

        $mockBaseUrlsProvider = $this->getMockBuilder(BaseUrlsProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockBaseUrlsProviderFactory = $this->getMockBuilder(BaseUrlsProviderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockBaseUrlsProviderFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockBaseUrlsProvider);

        $mockAccountFeaturesServiceFactory = $this->getMockBuilder(AccountFeaturesServiceInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccountFeaturesServiceFactory->expects($this->once())
            ->method('create')
            ->with(['baseUrlsProvider' => $mockBaseUrlsProvider])
            ->willReturn($mockAccountFeaturesService);

        $accountFeaturesAction = $this->instantiateAccountFeaturesAction(arguments: [
            'accountFeaturesServiceFactory' => $mockAccountFeaturesServiceFactory,
            'baseUrlProviderFactory' => $mockBaseUrlsProviderFactory,
        ]);
        $accountFeatures = $accountFeaturesAction->execute(account: $mockAccount);

        $this->assertTrue($accountFeatures->smartCategoryMerchandising, 'Smarty Category Merchandising');
        $this->assertTrue($accountFeatures->smartRecommendations, 'Smarty Recommendations');
        $this->assertTrue($accountFeatures->preserveLayout, 'Preserve Layout');
    }

    /**
     * @param string $jsApiKey
     * @param string $restAuthKey
     *
     * @return AccountInterface
     */
    private function getMockAccount(string $jsApiKey, string $restAuthKey): AccountInterface
    {
        $accountFactory = new AccountFactory();
        /** @var AccountInterface $mockAccount */
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
        ]);

        return $mockAccount;
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
     * @return AccountFeaturesAction
     */
    private function instantiateAccountFeaturesAction(?array $arguments = []): AccountFeaturesAction
    {
        return $this->objectManager->create(
            type: AccountFeaturesAction::class,
            arguments: $arguments,
        );
    }
}
