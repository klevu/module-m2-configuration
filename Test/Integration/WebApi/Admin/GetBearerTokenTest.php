<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\WebApi\Admin;

use Klevu\Configuration\Service\GetBearerTokenInterface;
use Klevu\Configuration\Service\GetBearerTokenService;
use Klevu\TestFixtures\Traits\SetAreaTrait;
use Klevu\TestFixtures\User\UserFixturesPool;
use Klevu\TestFixtures\User\UserTrait;
use Magento\Framework\App\Area;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\GetBearerTokenService
 * @magentoAppArea adminhtml
 * @runTestsInSeparateProcesses
 * @magentoAppIsolation enabled
 */
class GetBearerTokenTest extends TestCase
{
    use SetAreaTrait;
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
        $this->setArea(Area::AREA_ADMINHTML);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        $this->userFixturesPool->rollback();
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testIsInstanceOf_GetBearerTokenInterface(): void
    {
        $getBearerToken = $this->instantiateBearerTokenService();

        $this->assertInstanceOf(GetBearerTokenInterface::class, $getBearerToken);
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testPreferenceFor_GetBearerTokenInterface(): void
    {
        $getBearerToken = $this->objectManager->get(GetBearerTokenInterface::class);

        $this->assertInstanceOf(GetBearerTokenService::class, $getBearerToken);
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testExecute_ReturnsToken_ForAdminUser(): void
    {
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

        $getBearerToken = $this->instantiateBearerTokenService();
        $bearerToken = $getBearerToken->execute();

        $this->assertNotSame('', $bearerToken);
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return GetBearerTokenService
     */
    private function instantiateBearerTokenService(?array $arguments = []): GetBearerTokenService
    {
        return $this->objectManager->create(
            type: GetBearerTokenService::class,
            arguments: $arguments,
        );
    }
}
