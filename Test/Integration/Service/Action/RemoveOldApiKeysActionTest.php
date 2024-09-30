<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integraton\Service\Action;

use Klevu\Configuration\Service\Action\RemoveOldApiKeysAction;
use Klevu\Configuration\Service\Action\RemoveOldApiKeysActionInterface;
use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Configuration\Service\Provider\Stores\Config\OldAuthKeysCollectionProvider;
use Klevu\Configuration\Service\Provider\Stores\Config\OldAuthKeysCollectionProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers RemoveOldApiKeysAction::class
 * @method RemoveOldApiKeysActionInterface instantiateTestObject(?array $arguments = null)
 * @method RemoveOldApiKeysActionInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class RemoveOldApiKeysActionTest extends TestCase
{
    use ObjectInstantiationTrait;
    use StoreTrait;
    use SetAuthKeysTrait;
    use TestImplementsInterfaceTrait;
    use TestInterfacePreferenceTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = RemoveOldApiKeysAction::class;
        $this->interfaceFqcn = RemoveOldApiKeysActionInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();

        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->storeFixturesPool->rollback();
    }

    public function testExecute_RemoveOldApiKeys_ForCurrentStoreOnly(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());
        $this->setOldAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu_js_api_key_1',
            restAuthKey: 'klevu_rest_auth_key_1',
        );

        $this->createStore([
            'key' => 'test_store_2',
            'code' => 'klevu_test_store_2',
        ]);
        $storeFixture2 = $this->storeFixturesPool->get('test_store_2');
        $scopeProvider2 = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider2->setCurrentScope($storeFixture2->get());
        $this->setOldAuthKeys(
            scopeProvider: $scopeProvider2,
            jsApiKey: 'klevu_js_api_key_2',
            restAuthKey: 'klevu_rest_auth_key_2',
            removeApiKeys: false,
        );

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockLogger->expects($this->once())
            ->method('info')
            ->with(
                'Method: {method}, Info: {message}',
                [
                    'method' => 'Klevu\Configuration\Service\Action\RemoveOldApiKeysAction::logRemoval',
                    'message' => 'Klevu JS API Key for indexing v2 "klevu_js_api_key_1" removed.',
                ],
            );

        $action = $this->instantiateTestObject([
            'logger' => $mockLogger,
        ]);
        $action->execute(scopeId: (int)$storeFixture->getId(), scopeType: ScopeInterface::SCOPE_STORES);

        $authKeysProvider = $this->objectManager->get(OldAuthKeysCollectionProviderInterface::class);

        $items1 = $authKeysProvider->get(
            filter: [
                OldAuthKeysCollectionProvider::FILTER_SCOPE => ScopeInterface::SCOPE_STORES,
                OldAuthKeysCollectionProvider::FILTER_SCOPE_ID => $storeFixture->getId(),
            ],
        );
        $this->assertCount(expectedCount: 0, haystack: $items1);

        $items2 = $authKeysProvider->get(
            filter: [
                OldAuthKeysCollectionProvider::FILTER_SCOPE => ScopeInterface::SCOPE_STORES,
                OldAuthKeysCollectionProvider::FILTER_SCOPE_ID => $storeFixture2->getId(),
            ],
        );
        $this->assertCount(expectedCount: 2, haystack: $items2);
    }

    public function testExecute_RemoveOldApiKeys_ForAllStoresUnderIntegratedWebsite(): void
    {
        $this->markTestIncomplete('Complete once support for channels has been implemented in indexing');
    }
}
