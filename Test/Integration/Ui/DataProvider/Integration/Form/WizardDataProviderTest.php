<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Ui\DataProvider\Integration\Form;

use Klevu\Configuration\Ui\DataProvider\Integration\Form\WizardDataProvider;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\User\UserFixturesPool;
use Klevu\TestFixtures\User\UserTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers WizardDataProvider::class
 * @method DataProviderInterface instantiateTestObject(?array $arguments = null)
 * @method DataProviderInterface instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea adminhtml
 */
class WizardDataProviderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;
    use UserTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line
    /**
     * @var StoreManagerInterface
     */
    private mixed $storeManager;

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

    public function testGetData_ContainsLoggerScopeId_InSingleStoreMode(): void
    {
        ConfigFixture::setGlobal(
            path: StoreManager::XML_PATH_SINGLE_STORE_MODE_ENABLED,
            value: 1,
        );

        $this->createUser();
        $userFixture = $this->userFixturesPool->get('test_user');
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
        );

        $this->assertArrayHasKey(key: 'scope', array: $result);
        $this->assertSame(
            expected: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            actual: $result['scope'],
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

        $this->createUser();
        $userFixture = $this->userFixturesPool->get('test_user');
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
    }
}
