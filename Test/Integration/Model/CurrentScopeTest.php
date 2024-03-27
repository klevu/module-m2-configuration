<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Model;

use Klevu\Configuration\Model\CurrentScope;
use Klevu\Configuration\Model\CurrentScopeInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Model\CurrentScope
 */
class CurrentScopeTest extends TestCase
{
    use StoreTrait;
    use WebsiteTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->storeFixturesPool = $this->objectManager->create(StoreFixturesPool::class);
        $this->websiteFixturesPool = $this->objectManager->create(WebsiteFixturesPool::class);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->storeFixturesPool->rollback();
        $this->websiteFixturesPool->rollback();
    }

    public function testImplements_CurrentScopeInterface(): void
    {
        $this->assertInstanceOf(
            expected: CurrentScopeInterface::class,
            actual: $this->instantiateCurrentScope([
                'scopeObject' => null,
            ]),
        );
    }

    public function testWebsiteScope(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');

        $currentScope = $this->instantiateCurrentScope([
            'scopeObject' => $website->get(),
        ]);

        $this->assertSame(expected: ScopeInterface::SCOPE_WEBSITES, actual: $currentScope->getScopeType());
        $this->assertSame(expected: $website->getId(), actual: $currentScope->getScopeId());
        $this->assertSame(expected: $website->get(), actual: $currentScope->getScopeObject());
    }

    public function testStoreScope(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $currentScope = $this->instantiateCurrentScope([
            'scopeObject' => $store->get(),
        ]);

        $this->assertSame(expected: ScopeInterface::SCOPE_STORES, actual: $currentScope->getScopeType());
        $this->assertSame(expected: $store->getId(), actual: $currentScope->getScopeId());
        $this->assertSame(expected: $store->get(), actual: $currentScope->getScopeObject());
    }

    public function testGlobalScope(): void
    {
        $currentScope = $this->instantiateCurrentScope([
            'scopeObject' => null,
        ]);

        $this->assertSame(expected: ScopeConfigInterface::SCOPE_TYPE_DEFAULT, actual: $currentScope->getScopeType());
        $this->assertNull(actual: $currentScope->getScopeId());
        $this->assertNull(actual: $currentScope->getScopeObject());
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return CurrentScope
     */
    private function instantiateCurrentScope(?array $arguments = []): CurrentScope
    {
        return $this->objectManager->create(
            type: CurrentScope::class,
            arguments: $arguments,
        );
    }
}
