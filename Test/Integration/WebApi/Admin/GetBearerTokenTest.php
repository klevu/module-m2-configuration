<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\WebApi\Admin;

use Klevu\Configuration\Service\GetBearerTokenInterface;
use Klevu\Configuration\Service\GetBearerTokenService;
use Klevu\TestFixtures\User\UserFixturesPool;
use Klevu\TestFixtures\User\UserTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\GetBearerTokenService
 * @magentoAppArea adminhtml
 */
class GetBearerTokenTest extends TestCase
{
    use UserTrait;

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
        $this->userFixturesPool = $this->objectManager->create(UserFixturesPool::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        $this->userFixturesPool->rollback();
    }

    public function testIsInstanceOf_GetBearerTokenInterface(): void
    {
        $getBearerToken = $this->InstantiateBearerTokenService();

        $this->assertInstanceOf(GetBearerTokenInterface::class, $getBearerToken);
    }

    public function testPreferenceFor_GetBearerTokenInterface(): void
    {
        $getBearerToken = $this->objectManager->get(GetBearerTokenInterface::class);

        $this->assertInstanceOf(GetBearerTokenService::class, $getBearerToken);
    }

    public function testExecute_ReturnsToken_ForAdminUser(): void
    {
        $this->createUser();
        $userFixture = $this->userFixturesPool->get('test_user');
        $this->loginUser(user: $userFixture->get());

        $getBearerToken = $this->InstantiateBearerTokenService();
        $bearerToken = $getBearerToken->execute();

        $this->assertNotSame('', $bearerToken);
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return GetBearerTokenService
     */
    private function InstantiateBearerTokenService(?array $arguments = []): GetBearerTokenService
    {
        return $this->objectManager->create(
            type: GetBearerTokenService::class,
            arguments: $arguments,
        );
    }
}
