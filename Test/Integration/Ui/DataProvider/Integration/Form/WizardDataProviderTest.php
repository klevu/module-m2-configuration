<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Ui\DataProvider\Integration\Form;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Configuration\Ui\DataProvider\Integration\Form\WizardDataProvider;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\SetAreaTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\User\UserFixturesPool;
use Klevu\TestFixtures\User\UserTrait;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaInterface;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager\ConfigLoader;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Config\Scope;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Application;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers WizardDataProvider::class
 * @magentoAppArea adminhtml
 * @runTestsInSeparateProcesses
 */
class WizardDataProviderTest extends TestCase
{
    use SetAreaTrait;
    use SetAuthKeysTrait;
    use StoreTrait;
    use UserTrait;

    /**
     * @var string|null
     */
    private ?string $implementationFqcn = null;
    /**
     * @var string|null
     */
    private ?string $interfaceFqcn = null;
    /**
     * @var mixed[]|null
     */
    private ?array $constructorArgumentDefaults = null;
    /**
     * @var string|null
     */
    private ?string $implementationForVirtualType = null;
    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line
    /**
     * @var StoreManagerInterface
     */
    private mixed $storeManager = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = WizardDataProvider::class;
        $this->interfaceFqcn = DataProviderInterface::class;
        $this->constructorArgumentDefaults = [
            'name' => 'klevu_integration_wizard',
            'primaryFieldName' => 'scope_id',
            'requestFieldName' => 'scope_id',
        ];
        $this->objectManager = Bootstrap::getObjectManager();
        $this->setArea(Area::AREA_ADMINHTML);

        $this->userFixturesPool = $this->objectManager->get(UserFixturesPool::class);
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);

        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);

        /** @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams([
            'scope' => ScopeInterface::SCOPE_STORES,
            'scopeId' => (int)$this->storeManager->getDefaultStoreView()->getId(),
        ]);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->userFixturesPool->rollback();
        $this->storeFixturesPool->rollback();
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetData_ContainsLoggerScopeId_InSingleStoreMode(): void
    {
        ConfigFixture::setGlobal(
            path: StoreManager::XML_PATH_SINGLE_STORE_MODE_ENABLED,
            value: 1,
        );

        $this->createUser(
            userData: [
                'firstname' => 'PHPUnit',
                'lastname' => 'Test',
                'email' => 'noreply@klevu.com',
                'username' => 'phpunit_test_user',
                'password' => 'PHPUnit.Test.123',
                'key' => 'phpunit_test_user',
            ],
        );
        $userFixture = $this->userFixturesPool->get('phpunit_test_user');
        $this->loginUser(user: $userFixture->get());

        /** @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams([
            'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            'scope_id' => Store::DEFAULT_STORE_ID,
        ]);

        $provider = $this->instantiateTestObject();
        $results = $provider->getData();

        $this->assertIsArray($results);
        $result = array_shift($results);

        $this->assertArrayHasKey(key: 'logger_scope_id', array: $result);
        $this->assertSame(
            expected: (int)$this->storeManager->getDefaultStoreView()->getId(),
            actual: $result['logger_scope_id'],
        );

        $this->assertArrayHasKey(key: 'scope_id', array: $result);
        $this->assertSame(
            expected: Store::DEFAULT_STORE_ID,
            actual: $result['scope_id'],
            message: 'scope_id',
        );

        $this->assertArrayHasKey(key: 'scope', array: $result);
        $this->assertSame(
            expected: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            actual: $result['scope'],
            message: 'scope',
        );

        $this->assertArrayHasKey(key: 'store_code', array: $result);
        $this->assertSame(
            expected: $this->storeManager->getDefaultStoreView()->getCode(),
            actual: $result['store_code'],
            message: 'store_code',
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetData_LoggerScopeIdMatchesScopeId_NotInSingleStoreMode(): void
    {
        ConfigFixture::setGlobal(
            path: StoreManager::XML_PATH_SINGLE_STORE_MODE_ENABLED,
            value: 0,
        );

        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');

        $this->createUser(
            userData: [
                'firstname' => 'PHPUnit',
                'lastname' => 'Test',
                'email' => 'noreply@klevu.com',
                'username' => 'phpunit_test_user',
                'password' => 'PHPUnit.Test.123',
                'key' => 'phpunit_test_user',
            ],
        );
        $userFixture = $this->userFixturesPool->get('phpunit_test_user');
        $this->loginUser(user: $userFixture->get());

        /** @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams([
            'scope' => ScopeInterface::SCOPE_STORES,
            'scope_id' => (int)$storeFixture->getId(),
        ]);

        $provider = $this->instantiateTestObject();
        $results = $provider->getData();

        $this->assertIsArray($results);
        $result = array_shift($results);

        $this->assertArrayHasKey(key: 'logger_scope_id', array: $result);
        $this->assertSame(
            expected: (int)$storeFixture->getId(),
            actual: $result['logger_scope_id'],
            message: 'logger_scope_id',
        );

        $this->assertArrayHasKey(key: 'scope', array: $result);
        $this->assertSame(
            expected: ScopeInterface::SCOPE_STORES,
            actual: $result['scope'],
            message: 'scope',
        );

        $this->assertArrayHasKey(key: 'scope_id', array: $result);
        $this->assertSame(
            expected: (int)$storeFixture->getId(),
            actual: $result['scope_id'],
            message: 'scope_id',
        );

        $this->assertArrayHasKey(key: 'store_code', array: $result);
        $this->assertSame(
            expected: $storeFixture->getCode(),
            actual: $result['store_code'],
            message: 'store_code',
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetData_ContainsCurrentApiKeysWhenPresent(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu_js_api_key',
            restAuthKey: 'klevu_rest_auth_key',
        );

        $this->createUser(
            userData: [
                'firstname' => 'PHPUnit',
                'lastname' => 'Test',
                'email' => 'noreply@klevu.com',
                'username' => 'phpunit_test_user',
                'password' => 'PHPUnit.Test.123',
                'key' => 'phpunit_test_user',
            ],
        );
        $userFixture = $this->userFixturesPool->get('phpunit_test_user');
        $this->loginUser(user: $userFixture->get());

        /** @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams([
            'scope' => ScopeInterface::SCOPE_STORES,
            'scope_id' => (int)$storeFixture->getId(),
        ]);

        $provider = $this->instantiateTestObject();
        $results = $provider->getData();
        $this->assertIsArray($results);
        $result = array_shift($results);

        $this->assertArrayHasKey(key: 'js_api_key', array: $result);
        $this->assertSame(
            expected: 'klevu_js_api_key',
            actual: $result['js_api_key'],
            message: 'js_api_key',
        );
        $this->assertArrayHasKey(key: 'js_api_key', array: $result);
        $this->assertSame(
            expected: 'klevu_rest_auth_key',
            actual: $result['rest_auth_key'],
            message: 'rest_auth_key',
        );
        $this->assertArrayHasKey(key: 'messages', array: $result);
        $this->assertEmpty(
            actual: $result['messages'],
            message: 'messages',
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetData_ContainsPreviousApiKeysWhenPresent(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setOldAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu_js_api_key',
            restAuthKey: 'klevu_rest_auth_key',
        );

        $this->createUser(
            userData: [
                'firstname' => 'PHPUnit',
                'lastname' => 'Test',
                'email' => 'noreply@klevu.com',
                'username' => 'phpunit_test_user',
                'password' => 'PHPUnit.Test.123',
                'key' => 'phpunit_test_user',
            ],
        );
        $userFixture = $this->userFixturesPool->get('phpunit_test_user');
        $this->loginUser(user: $userFixture->get());

        /** @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams([
            'scope' => ScopeInterface::SCOPE_STORES,
            'scope_id' => (int)$storeFixture->getId(),
        ]);

        $provider = $this->instantiateTestObject();
        $results = $provider->getData();
        $this->assertIsArray($results);
        $result = array_shift($results);

        $this->assertArrayHasKey(key: 'js_api_key', array: $result);
        $this->assertSame(
            expected: 'klevu_js_api_key',
            actual: $result['js_api_key'],
            message: 'js_api_key',
        );
        $this->assertArrayHasKey(key: 'js_api_key', array: $result);
        $this->assertSame(
            expected: 'klevu_rest_auth_key',
            actual: $result['rest_auth_key'],
            message: 'rest_auth_key',
        );
        $this->assertArrayHasKey(key: 'messages', array: $result);
        $this->assertEquals(
            expected: [
                __('This store is not currently integrated.'),
                __(
                    'The form is pre-populated with Auth Keys used in a previous integration for this store.',
                ),
            ],
            actual: $result['messages'],
            message: 'messages',
        );
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return object
     * @throws \LogicException
     *
     * @todo Reinstate object instantiation and interface traits. Removed as causing serialization of Closure error
     *  in phpunit Standard input code
     */
    private function instantiateTestObject(
        ?array $arguments = null,
    ): object {
        if (!$this->implementationFqcn) {
            throw new \LogicException('Cannot instantiate test object: no implementationFqcn defined');
        }
        if (null === $arguments) {
            $arguments = $this->constructorArgumentDefaults;
        }

        return (null === $arguments)
            ? $this->objectManager->get($this->implementationFqcn)
            : $this->objectManager->create($this->implementationFqcn, $arguments);
    }
}
