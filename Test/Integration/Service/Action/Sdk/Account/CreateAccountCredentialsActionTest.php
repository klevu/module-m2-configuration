<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Service\Action\Sdk\Account;

use Klevu\Configuration\Service\Action\Sdk\Account\CreateAccountCredentialsAction;
use Klevu\Configuration\Service\Action\Sdk\Account\CreateAccountCredentialsActionInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\Action\CreateAccountCredentialsAction
 */
class CreateAccountCredentialsActionTest extends TestCase
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

    public function testImplements_CreateAccountCredentialsActionInterface(): void
    {
        $this->assertInstanceOf(
            expected: CreateAccountCredentialsActionInterface::class,
            actual: $this->instantiateCreateAccountCredentialsAction(),
        );
    }

    public function testPreference_ForCreateAccountCredentialsActionInterface(): void
    {
        $this->assertInstanceOf(
            expected: CreateAccountCredentialsAction::class,
            actual: $this->objectManager->get(type: CreateAccountCredentialsActionInterface::class),
        );
    }

    public function testExecute_ReturnsAccountCredentials(): void
    {
        $jsApiKey = 'klevu-1234567890';
        $restAuthKey = $this->generateAuthKey(length: 10);

        $createAccountCredentials = $this->instantiateCreateAccountCredentialsAction();
        $accountCredentials = $createAccountCredentials->execute(apiKey: $jsApiKey, authKey: $restAuthKey);

        $this->assertSame(expected: $jsApiKey, actual: $accountCredentials->jsApiKey);
        $this->assertSame(expected: $restAuthKey, actual: $accountCredentials->restAuthKey);
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
     * @return CreateAccountCredentialsAction
     */
    private function instantiateCreateAccountCredentialsAction(?array $arguments = []): CreateAccountCredentialsAction
    {
        return $this->objectManager->create(
            type: CreateAccountCredentialsAction::class,
            arguments: $arguments,
        );
    }
}
