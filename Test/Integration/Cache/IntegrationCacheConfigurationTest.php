<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Cache;

use Klevu\Configuration\Cache\Type\Integration as IntegrationCache;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Cache\Type\Integration
 */
class IntegrationCacheConfigurationTest extends TestCase
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

    public function testCacheType_IsRegistered(): void
    {
        $typeList = $this->objectManager->get(TypeListInterface::class);

        $labels = $typeList->getTypeLabels();
        $this->assertArrayHasKey(key: IntegrationCache::TYPE_IDENTIFIER, array: $labels);
        $this->assertSame(expected: 'Klevu Integration', actual: $labels[IntegrationCache::TYPE_IDENTIFIER]);

        $types = $typeList->getTypes();
        $this->assertArrayHasKey(key: IntegrationCache::TYPE_IDENTIFIER, array: $types);
        /** @var DataObject $cacheConfig */
        $cacheConfig = $types[IntegrationCache::TYPE_IDENTIFIER];
        $this->assertSame(expected: 'Klevu Integration', actual: $cacheConfig->getData('cache_type'));
        $this->assertSame(expected: IntegrationCache::TYPE_IDENTIFIER, actual: $cacheConfig->getData('id'));
        $this->assertSame(expected: IntegrationCache::CACHE_TAG, actual: $cacheConfig->getData('tags'));
        $this->assertSame(
            expected: 'Features Available in KMC. Refresh if you have changed setting in KMC.',
            actual: $cacheConfig->getData('description'),
        );
    }
}
