<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Unit\Service\Provider;

use Klevu\Configuration\Model\CurrentScope;
use Klevu\Configuration\Model\CurrentScopeInterface;
use Klevu\Configuration\Service\Provider\ApiKeyProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers ApiKeyProvider
 */
class ApiKeyProviderTest extends TestCase
{
    public function testGet_ReturnsString(): void
    {
        $mockStore1 = $this->getMockBuilder(StoreInterface::class)
            ->getMock();
        $mockStore1->method('getId')
            ->willReturn(1);
        $mockStore2 = $this->getMockBuilder(StoreInterface::class)
            ->getMock();
        $mockStore2->method('getId')
            ->willReturn(2);

        $mockScopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();
        $mockScopeConfig->method('getValue')
            ->willReturnMap([
                ['klevu_configuration/auth_keys/js_api_key', 'stores', 1, 'klevu-js-api-key'],
                ['klevu_configuration/auth_keys/js_api_key', 'stores', 2, null],
            ]);

        $apiKeyProvider = new ApiKeyProvider(
            scopeConfig: $mockScopeConfig,
        );
        $currentScope1 = $this->createCurrentScope($mockStore1);
        $currentScope2 = $this->createCurrentScope($mockStore2);

        $this->assertSame('klevu-js-api-key', $apiKeyProvider->get($currentScope1));
        $this->assertNull($apiKeyProvider->get($currentScope2));
    }

    /**
     * @param StoreInterface|MockObject $scope
     *
     * @return CurrentScopeInterface
     */
    private function createCurrentScope(StoreInterface|MockObject $scope): CurrentScopeInterface
    {
        return new CurrentScope(
            scopeObject: $scope,
        );
    }
}
