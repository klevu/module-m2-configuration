<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service\Provider;

use Klevu\Configuration\Exception\ApiKeyNotFoundException;
use Klevu\Configuration\Model\CurrentScopeInterface;
use Magento\Framework\Exception\NoSuchEntityException;

interface AuthKeyProviderInterface
{
    /**
     * @param CurrentScopeInterface $scope
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function get(CurrentScopeInterface $scope): ?string;

    /**
     * @param string $apiKey
     *
     * @return string|null
     * @throws ApiKeyNotFoundException
     */
    public function getForApiKey(string $apiKey): ?string;
}
