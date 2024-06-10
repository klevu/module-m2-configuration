<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service;

use Klevu\Configuration\Exception\Integration\InvalidScopeException;
use Klevu\Configuration\Service\Action\UpdateEndpoints;
use Klevu\Configuration\Service\IntegrateApiKeysService;
use Klevu\Configuration\Service\IntegrateApiKeysServiceInterface;
use Klevu\Configuration\Service\Provider\ApiKeyProvider;
use Klevu\Configuration\Service\Provider\AuthKeyProvider;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountFeaturesServiceInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountFeaturesServiceInterfaceFactory;
use Klevu\PhpSDK\Api\Service\Account\AccountLookupServiceInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Klevu\PhpSDK\Model\Account\AccountFeaturesFactory;
use Klevu\PhpSDK\Model\AccountFactory;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\Writer as ConfigWriter;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\IntegrateApiKeysService
 */
class IntegrateApiKeysServiceTest extends TestCase
{
    use StoreTrait;

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
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->storeFixturesPool->rollback();
    }

    public function testImplements_CheckApiKeysServiceInterface(): void
    {
        $this->assertInstanceOf(
            expected: IntegrateApiKeysServiceInterface::class,
            actual: $this->instantiateIntegrateApiKeysService(),
        );
    }

    public function testPreference_ForIntegrateApiKeysServiceInterface(): void
    {
        $this->assertInstanceOf(
            expected: IntegrateApiKeysService::class,
            actual: $this->objectManager->create(IntegrateApiKeysServiceInterface::class),
        );
    }

    public function testExecute_ThrowsException_WhenApiKeyEmpty(): void
    {
        try {
            $service = $this->instantiateIntegrateApiKeysService();
            $service->execute(
                apiKey: '',
                authKey: $this->generateAuthKey(length: 10),
                scopeId: Store::DISTRO_STORE_ID,
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
            $service = $this->instantiateIntegrateApiKeysService();
            $service->execute(
                apiKey: $invalidApiKey,
                authKey: $this->generateAuthKey(length: 10),
                scopeId: Store::DISTRO_STORE_ID,
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
            $service = $this->instantiateIntegrateApiKeysService();
            $service->execute(
                apiKey: 'klevu-1234567890',
                authKey: '',
                scopeId: Store::DISTRO_STORE_ID,
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
            $service = $this->instantiateIntegrateApiKeysService();
            $service->execute(
                apiKey: 'klevu-1234567890',
                authKey: $invalidAuthKey,
                scopeId: Store::DISTRO_STORE_ID,
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

        $service = $this->instantiateIntegrateApiKeysService();
        $service->execute(
            apiKey: 'klevu-1234567890',
            authKey: $this->generateAuthKey(length: 10),
            scopeId: Store::DISTRO_STORE_ID,
        );
    }

    /**
     * @dataProvider dataProvider_testExecute_ThrowsException_WhenScopeIsInvalid
     */
    public function testExecute_ThrowsException_WhenScopeIsInvalid(string $invalidScope): void
    {
        $this->expectException(InvalidScopeException::class);

        $service = $this->instantiateIntegrateApiKeysService();
        $service->execute(
            apiKey: 'klevu-1234567890',
            authKey: $this->generateAuthKey(length: 10),
            scopeId: Store::DISTRO_STORE_ID,
            scopeType: $invalidScope,
        );
    }

    /**
     * @return string[][]
     */
    public function dataProvider_testExecute_ThrowsException_WhenScopeIsInvalid(): array
    {
        return [
            [ScopeInterface::SCOPE_GROUP],
            [ScopeInterface::SCOPE_GROUPS],
            ["default"],
            [ScopeInterface::SCOPE_WEBSITE], // @TODO remove when channels are available
            [ScopeInterface::SCOPE_WEBSITES], // @TODO remove when channels are available
        ];
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testExecute_SavesAccountData_ForStore(): void
    {
        /** @var ConfigWriter $configWriter */
        $configWriter = $this->objectManager->get(ConfigWriter::class);

        $this->createStore();
        $store = $this->storeFixturesPool->get(key: 'test_store');
        $paths = [
            ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY => null,
            AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_ANALYTICS => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_CAT_NAV => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_INDEXING => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_JS => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_SEARCH => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_TIERS => null, // default setting
        ];
        foreach (array_keys($paths) as $path) {
            $configWriter->delete(
                path: $path,
                scope: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                scopeId: 0,
            );
            $configWriter->delete(
                path: $path,
                scope: ScopeInterface::SCOPE_WEBSITES,
                scopeId: $store->getWebsiteId(),
            );
            $configWriter->delete(
                path: $path,
                scope: ScopeInterface::SCOPE_STORES,
                scopeId: $store->getId(),
            );
        }

        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();
        foreach ($paths as $path => $expectedValue) {
            $actualValue = $scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_WEBSITES,
                $store->getWebsiteId(),
            );
            $this->assertSame(expected: $expectedValue, actual: $actualValue);
        }

        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);
        $accountData = [
            'jsApiKey' => $jsApiKey,
            'restAuthKey' => $restAuthKey,
            'platform' => 'magento',
            'active' => true,
            'companyName' => 'Klevu',
            'email' => 'user@klevu.com',
            'analyticsUrl' => 'analytics.url',
            'indexingUrl' => 'indexing.url',
            'jsUrl' => 'js.url',
            'searchUrl' => 'search.url',
            'smartCategoryMerchandisingUrl' => 'catnav.url',
            'tiersUrl' => 'tiers.klevu.com',
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

        $mockEventManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMock();
        $mockEventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'klevu_integrate_api_keys_after',
                [
                    'apiKey' => $jsApiKey,
                ],
            );

        $service = $this->instantiateIntegrateApiKeysService([
            'eventManager' => $mockEventManager,
        ]);
        $account = $service->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
            scopeId: $store->getId(),
        );
        // returned data
        $this->assertSame(expected: $jsApiKey, actual: $account->getJsApiKey());
        $this->assertSame(expected: $restAuthKey, actual: $account->getRestAuthKey());
        $this->assertSame(expected: 'user@klevu.com', actual: $account->getEmail());
        $this->assertSame(
            expected: 'catnav.url',
            actual: $account->getSmartCategoryMerchandisingUrl(),
        );
        $accountFeatures = $account->getAccountFeatures();
        $this->assertTrue(
            condition: $accountFeatures->smartCategoryMerchandising,
            message: 'Smart Category Merchandising',
        );
        $this->assertTrue(condition: $accountFeatures->preserveLayout, message: 'Preserve Layout');

        $initialConfigValues = [
            ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY => $jsApiKey,
            AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY => $restAuthKey,
            UpdateEndpoints::CONFIG_XML_PATH_URL_ANALYTICS => 'analytics.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_CAT_NAV => 'catnav.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_INDEXING => 'indexing.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_JS => 'js.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_SEARCH => 'search.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_TIERS => 'tiers.klevu.com',
        ];
        foreach ($initialConfigValues as $path => $value) {
            $actualValue = $scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORES,
                $store->getId(),
            );
            $this->assertSame(expected: $value, actual: $actualValue);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testExecute_SavesAccountData_ForWebsite(): void
    {
        // @TODO add when channels are available
        $this->markTestSkipped('Skipped until channels are available');
        /** @var ConfigWriter $configWriter */
        $configWriter = $this->objectManager->get(ConfigWriter::class);

        $this->createStore();
        $store = $this->storeFixturesPool->get(key: 'test_store');

        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $paths = [
            ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY => null,
            AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_ANALYTICS => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_CAT_NAV => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_INDEXING => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_JS => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_SEARCH => null,
            UpdateEndpoints::CONFIG_XML_PATH_URL_TIERS => 'tiers.klevu.com', // default setting
        ];
        foreach (array_keys($paths) as $path) {
            $configWriter->delete(
                path: $path,
                scope: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                scopeId: 0,
            );
            $configWriter->delete(
                path: $path,
                scope: ScopeInterface::SCOPE_WEBSITES,
                scopeId: $store->getWebsiteId(),
            );
            $configWriter->delete(
                path: $path,
                scope: ScopeInterface::SCOPE_STORES,
                scopeId: $store->getId(),
            );
        }
        foreach ($paths as $path => $value) {
            $configJsApiKey = $scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_WEBSITES,
                $store->getWebsiteId(),
            );
            $this->assertSame(expected: $value, actual: $configJsApiKey);
        }

        $jsApiKey = 'klevu-0987654321';
        $restAuthKey = $this->generateAuthKey(length: 10);
        $accountData = [
            'jsApiKey' => $jsApiKey,
            'restAuthKey' => $restAuthKey,
            'platform' => 'magento',
            'active' => true,
            'companyName' => 'Klevu Io',
            'email' => 'someone@klevu.com',
            'analyticsUrl' => 'analytics.url',
            'indexingUrl' => 'indexing.url',
            'jsUrl' => 'js.url',
            'searchUrl' => 'search.url',
            'smartCategoryMerchandisingUrl' => 'catnav.url',
            'tiersUrl' => 'tiers.url',
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

        $accountFeatures = [
            'smartCategoryMerchandising' => false,
            'smartRecommendations' => true,
            'preserveLayout' => true,
        ];
        $mockAccountFeatures = $this->createAccountFeatures(accountFeatures: $accountFeatures);
        $mockAccountFeaturesService = $this->getMockBuilder(AccountFeaturesServiceInterface::class)
            ->getMock();
        $mockAccountFeaturesService->expects($this->once())
            ->method('execute')
            ->willReturn($mockAccountFeatures);
        $this->objectManager->addSharedInstance(
            instance: $mockAccountFeaturesService,
            className: AccountFeaturesServiceInterface::class,
            forPreference: true,
        );
        $this->objectManager->addSharedInstance(
            instance: $mockAccountFeaturesService,
            className: 'Klevu\Configuration\Service\Account\AccountFeaturesService', // virtualType
            forPreference: true,
        );

        $mockEventManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMock();
        $mockEventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'klevu_integrate_api_keys_after',
                [
                    'apiKey' => $jsApiKey,
                ],
            );

        $service = $this->instantiateIntegrateApiKeysService([
            'eventManager' => $mockEventManager,
        ]);
        $account = $service->execute(
            apiKey: $jsApiKey,
            authKey: $restAuthKey,
            scopeId: $store->getWebsiteId(),
            scopeType: ScopeInterface::SCOPE_WEBSITES,
        );
        // returned data
        $this->assertSame(expected: $jsApiKey, actual: $account->getJsApiKey());
        $this->assertSame(expected: $restAuthKey, actual: $account->getRestAuthKey());
        $this->assertSame(expected: 'someone@klevu.com', actual: $account->getEmail());
        $this->assertSame(
            expected: 'catnav.url',
            actual: $account->getSmartCategoryMerchandisingUrl(),
        );
        $accountFeatures = $account->getAccountFeatures();
        $this->assertFalse(
            condition: $accountFeatures->smartCategoryMerchandising,
            message: 'Smart Category Merchandising',
        );
        $this->assertTrue(condition: $accountFeatures->preserveLayout, message: 'Preserve Layout');

        $accountProvider = $this->objectManager->get(CachedAccountProvider::class);
        $cachedAccountFeatures = $accountProvider->get(scopeId: $store->getId());
        // cached data
        $this->assertFalse(
            condition: $cachedAccountFeatures->smartCategoryMerchandising,
            message: 'Smart Category Merchandising',
        );
        $this->assertTrue(
            condition: $cachedAccountFeatures->preserveLayout,
            message: 'Preserve Layout',
        );
        $this->assertTrue(
            condition: $cachedAccountFeatures->smartRecommendations,
            message: 'Smart Recommendations',
        );

        $initialConfigValues = [
            ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY => $jsApiKey,
            AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY => $restAuthKey,
            UpdateEndpoints::CONFIG_XML_PATH_URL_ANALYTICS => 'analytics.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_CAT_NAV => 'catnav.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_INDEXING => 'indexing.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_JS => 'js.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_SEARCH => 'search.url',
            UpdateEndpoints::CONFIG_XML_PATH_URL_TIERS => 'tiers.url',
        ];
        foreach ($initialConfigValues as $path => $value) {
            $configValue = $scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORES,
                $store->getId(),
            );
            $this->assertSame(expected: $value, actual: $configValue);
        }
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
     * @return IntegrateApiKeysService
     */
    private function instantiateIntegrateApiKeysService(?array $arguments = []): IntegrateApiKeysService
    {
        return $this->objectManager->create(
            type: IntegrateApiKeysService::class,
            arguments: $arguments,
        );
    }
}
