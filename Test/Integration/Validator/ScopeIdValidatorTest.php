<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Validator;

use Klevu\Configuration\Validator\ScopeIdValidator;
use Klevu\Configuration\Validator\ValidatorInterface;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ScopeIdValidatorTest extends TestCase
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

    public function testImplements_ScopeIdValidatorInterface(): void
    {
        $this->assertInstanceOf(
            expected: ValidatorInterface::class,
            actual: $this->instantiateScopeIdValidator(),
        );
    }

    /**
     * @dataProvider testIsValid_ReturnsFalse_InvalidType_DataProvider
     */
    public function testIsValid_ReturnsFalse_InvalidType(mixed $invalidType): void
    {
        $validator = $this->instantiateScopeIdValidator();
        $isValid = $validator->isValid($invalidType);
        $hasMessages = $validator->hasMessages();
        $messages = $validator->getMessages();

        $this->assertFalse($isValid);
        $this->assertTrue($hasMessages);
        $this->assertContains(
            needle: sprintf(
                'Invalid Scope ID provided. Expected string, int or null; received %s.',
                get_debug_type($invalidType),
            ),
            haystack: $messages,
        );
    }

    /**
     * @return mixed[][]
     */
    public function testIsValid_ReturnsFalse_InvalidType_DataProvider(): array
    {
        return [
            [false],
            [true],
            [123.45],
            [['1', '2']],
            [new DataObject(['1', '2'])],
        ];
    }

    /**
     * @dataProvider testIsValid_ReturnsFalse_InvalidValue_DataProvider
     */
    public function testIsValid_ReturnsFalse_InvalidValue(mixed $invalidValue): void
    {
        $validator = $this->instantiateScopeIdValidator();
        $isValid = $validator->isValid($invalidValue);
        $hasMessages = $validator->hasMessages();
        $messages = $validator->getMessages();

        $this->assertFalse($isValid);
        $this->assertTrue($hasMessages);
        $this->assertContains(
            needle: sprintf(
                'Invalid Scope ID provided. Expected numeric value or null; received %s (%s).',
                $invalidValue,
                get_debug_type($invalidValue),
            ),
            haystack: $messages,
        );
    }

    /**
     * @return mixed[][]
     */
    public function testIsValid_ReturnsFalse_InvalidValue_DataProvider(): array
    {
        return [
            ['123.45'],
            ['0.89'],
            ['string'],
            ['true'],
        ];
    }

    /**
     * @dataProvider testIsValid_ReturnsTrue_ValidType_DataProvider
     */
    public function testIsValid_ReturnsTrue_ValidType(mixed $invalidType): void
    {
        $validator = $this->instantiateScopeIdValidator();
        $isValid = $validator->isValid($invalidType);
        $hasMessages = $validator->hasMessages();
        $messages = $validator->getMessages();

        $this->assertTrue($isValid);
        $this->assertFalse($hasMessages);
        $this->assertCount(0, $messages);
    }

    /**
     * @return mixed[][]
     */
    public function testIsValid_ReturnsTrue_ValidType_DataProvider(): array
    {
        return [
            [null],
            [123],
            ['456'],
        ];
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return ScopeIdValidator
     */
    private function instantiateScopeIdValidator(?array $arguments = []): ScopeIdValidator
    {
        return $this->objectManager->create(
            type: ScopeIdValidator::class,
            arguments: $arguments,
        );
    }
}
