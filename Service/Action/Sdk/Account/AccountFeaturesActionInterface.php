<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Action\Sdk\Account;

use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Account\AccountFeatures;

interface AccountFeaturesActionInterface
{
    /**
     * @param AccountInterface $account
     *
     * @return AccountFeatures
     * @throws ValidationException
     * @throws BadRequestException
     * @throws BadResponseException
     */
    public function execute(AccountInterface $account): AccountFeatures;
}
