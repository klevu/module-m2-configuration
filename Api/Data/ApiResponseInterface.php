<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Api\Data;

interface ApiResponseInterface
{
    /**
     * @return int
     */
    public function getCode(): int;

    /**
     * @param int $code
     *
     * @return void
     */
    public function setCode(int $code): void;

    /**
     * @return mixed[][]
     */
    public function getData(): array;

    /**
     * @param mixed[][] $data
     *
     * @return void
     */
    public function setData(array $data = []): void;

    /**
     * @return string[]
     */
    public function getMessages(): array;

    /**
     * @param \Magento\Framework\Phrase[] $messages
     *
     * @return void
     */
    public function setMessages(array $messages): void;

    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @param string $status
     *
     * @return void
     */
    public function setStatus(string $status): void;
}
