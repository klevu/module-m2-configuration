<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\Validator;

use Klevu\Configuration\Validator\ScopeTypeValidator;
use Klevu\Configuration\Validator\ValidatorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

class ScopeTypeValidatorTest extends TestCase
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
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testImplements_ScopeTypeValidatorInterface(): void
    {
        $this->assertInstanceOf(
            expected: ValidatorInterface::class,
            actual: $this->instantiateScopeTypeValidator(),
        );
    }

    /**
     * @dataProvider testIsValid_ReturnsFalse_InvalidType_DataProvider
     */
    public function testIsValid_ReturnsFalse_InvalidType(mixed $invalidType): void
    {
        $validator = $this->instantiateScopeTypeValidator();
        $isValid = $validator->isValid($invalidType);
        $hasMessages = $validator->hasMessages();
        $messages = $validator->getMessages();

        $this->assertFalse($isValid);
        $this->assertTrue($hasMessages);
        $this->assertContains(
            needle: sprintf(
                'Invalid Scope provided. Expected string; received %s.',
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
            [0],
            [123],
            [123.45],
            [['1', '2']],
            [new DataObject(['1', '2'])],
        ];
    }

    /**
     * @dataProvider testIsValid_ReturnsFalse_InvalidValue_DataProvider
     */
    public function testIsValid_ReturnsFalse_InvalidValue(mixed $invalidType): void
    {
        $validator = $this->instantiateScopeTypeValidator();
        $isValid = $validator->isValid($invalidType);
        $hasMessages = $validator->hasMessages();
        $messages = $validator->getMessages();

        $this->assertFalse($isValid);
        $this->assertTrue($hasMessages);
        $this->assertContains(
            needle: sprintf(
                'Invalid Scope provided. Expected one of %s; received %s.',
                implode(
                    separator: ', ',
                    array: [
                        ScopeInterface::SCOPE_STORE,
                        ScopeInterface::SCOPE_STORES,
//                        ScopeInterface::SCOPE_WEBSITE, // @TODO add when channels are available
//                        ScopeInterface::SCOPE_WEBSITES, // @TODO add when channels are available
                    ],
                ),
                $invalidType,
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
            ['string'],
            ['global'],
            [ScopeConfigInterface::SCOPE_TYPE_DEFAULT],
            [ScopeInterface::SCOPE_GROUP],
            [ScopeInterface::SCOPE_GROUPS],
            [ScopeInterface::SCOPE_WEBSITE], // @TODO remove when channels are available
            [ScopeInterface::SCOPE_WEBSITES], // @TODO remove when channels are available
        ];
    }

    /**
     * @dataProvider testIsValid_ReturnsTrue_ValidValue_DataProvider
     */
    public function testIsValid_ReturnsTrue_ValidValue(string $validValue): void
    {
        $validator = $this->instantiateScopeTypeValidator();
        $isValid = $validator->isValid($validValue);
        $hasMessages = $validator->hasMessages();

        $this->assertTrue($isValid);
        $this->assertFalse($hasMessages);
    }

    /**
     * @dataProvider testIsValid_ReturnsFalse_InvalidValue_InSingleStoreMode_DataProvider
     */
    public function testIsValid_ReturnsFalse_InvalidValue_InSingleStoreMode(string $invalidType): void
    {
        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 1,
        );
        $validator = $this->instantiateScopeTypeValidator();
        $isValid = $validator->isValid($invalidType);
        $hasMessages = $validator->hasMessages();
        $messages = $validator->getMessages();

        $this->assertFalse($isValid);
        $this->assertTrue($hasMessages);
        $this->assertContains(
            needle: sprintf(
                'Invalid Scope provided. Expected one of %s; received %s.',
                implode(
                    separator: ', ',
                    array: [
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    ],
                ),
                $invalidType,
            ),
            haystack: $messages,
        );
    }

    /**
     * @return mixed[][]
     */
    public function testIsValid_ReturnsFalse_InvalidValue_InSingleStoreMode_DataProvider(): array
    {
        return [
            ['string'],
            ['global'],
            [ScopeInterface::SCOPE_STORE],
            [ScopeInterface::SCOPE_STORES],
            [ScopeInterface::SCOPE_GROUP],
            [ScopeInterface::SCOPE_GROUPS],
            [ScopeInterface::SCOPE_WEBSITE], // @TODO remove when channels are available
            [ScopeInterface::SCOPE_WEBSITES], // @TODO remove when channels are available
        ];
    }

    public function testIsValid_ReturnsTrue_ValidValue_InSingleStoreMode(): void
    {
        ConfigFixture::setGlobal(
            path: 'general/single_store_mode/enabled',
            value: 1,
        );
        $validator = $this->instantiateScopeTypeValidator();
        $isValid = $validator->isValid(ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        $hasMessages = $validator->hasMessages();

        $this->assertTrue($isValid);
        $this->assertFalse($hasMessages);
    }

    /**
     * @return mixed[][]
     */
    public function testIsValid_ReturnsTrue_ValidValue_DataProvider(): array
    {
        return [
            [ScopeInterface::SCOPE_STORE],
            [ScopeInterface::SCOPE_STORES],
        ];
    }

    /**
     * @param mixed[]|null $arguments
     *
     * @return ScopeTypeValidator
     */
    private function instantiateScopeTypeValidator(?array $arguments = []): ScopeTypeValidator
    {
        return $this->objectManager->create(
            type: ScopeTypeValidator::class,
            arguments: $arguments,
        );
    }
}
