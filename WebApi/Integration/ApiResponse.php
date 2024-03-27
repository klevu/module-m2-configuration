<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\WebApi\Integration;

use Klevu\Configuration\Api\Data\ApiResponseInterface;
use Magento\Framework\Phrase;

class ApiResponse implements ApiResponseInterface
{
    /**
     * @var int
     */
    private int $code = 0;
    /**
     * @var mixed[]
     */
    private array $data = [];
    /**
     * @var Phrase[]
     */
    private array $messages = [];
    /**
     * @var string
     */
    private string $status = '';

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param int $code
     *
     * @return void
     */
    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * @return mixed[][]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param mixed[][] $data
     *
     * @return void
     */
    public function setData(array $data = []): void
    {
        $this->data = $data;
    }

    /**
     * @return string[]
     */
    public function getMessages(): array
    {
        return array_map(static function (Phrase $message): string {
            return $message->render();
        }, $this->messages);
    }

    /**
     * @param Phrase[] $messages
     *
     * @return void
     */
    public function setMessages(array $messages): void
    {
        $this->messages = $messages;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return void
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
