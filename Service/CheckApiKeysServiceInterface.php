<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service;

use Klevu\Configuration\Exception\Integration\InactiveAccountException;
use Klevu\Configuration\Exception\Integration\InvalidPlatformException;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;

interface CheckApiKeysServiceInterface
{
    /**
     * @param string $apiKey
     * @param string $authKey
     *
     * @return AccountInterface
     * @throws ValidationException
     * @throws BadRequestException
     * @throws BadResponseException
     * @throws AccountNotFoundException
     * @throws InvalidPlatformException
     * @throws InactiveAccountException
     */
    public function execute(string $apiKey, string $authKey): AccountInterface;
}
