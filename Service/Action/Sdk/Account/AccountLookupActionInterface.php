<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action\Sdk\Account;

use Klevu\Configuration\Exception\Integration\InactiveAccountException;
use Klevu\Configuration\Exception\Integration\InvalidPlatformException;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;

interface AccountLookupActionInterface
{
    /**
     * @param AccountCredentials $accountCredentials
     *
     * @return AccountInterface
     * @throws AccountNotFoundException
     * @throws BadRequestException
     * @throws BadResponseException
     * @throws InactiveAccountException
     * @throws InvalidPlatformException
     * @throws ValidationException
     */
    public function execute(AccountCredentials $accountCredentials): AccountInterface;
}
