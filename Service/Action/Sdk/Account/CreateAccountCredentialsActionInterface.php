<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action\Sdk\Account;

use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;

interface CreateAccountCredentialsActionInterface
{
    /**
     * @param string $apiKey
     * @param string $authKey
     *
     * @return AccountCredentials
     * @throws InvalidTypeValidationException
     */
    public function execute(string $apiKey, string $authKey): AccountCredentials;
}
