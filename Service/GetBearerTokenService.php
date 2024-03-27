<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Service;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Integration\Api\UserTokenIssuerInterface;
use Magento\Integration\Model\UserToken\UserTokenParametersFactory;

/**
 * Is required due to an issue in the core of Magento.
 * Admin Session does not auth admin ajax api calls as it should.
 * https://github.com/magento/magento2/issues/14297
 */
class GetBearerTokenService implements GetBearerTokenInterface
{
    /**
     * @var UserTokenIssuerInterface
     */
    private readonly UserTokenIssuerInterface $userTokenIssuer;
    /**
     * @var UserTokenParametersFactory
     */
    private readonly UserTokenParametersFactory $tokenParamsFactory;
    /**
     * @var UserContextInterface
     */
    private readonly UserContextInterface $userContext;

    /**
     * @param UserTokenIssuerInterface $userTokenIssuer
     * @param UserTokenParametersFactory $tokenParamsFactory
     * @param UserContextInterface $userContext
     */
    public function __construct(
        UserTokenIssuerInterface $userTokenIssuer,
        UserTokenParametersFactory $tokenParamsFactory,
        UserContextInterface $userContext,
    ) {
        $this->userTokenIssuer = $userTokenIssuer;
        $this->tokenParamsFactory = $tokenParamsFactory;
        $this->userContext = $userContext;
    }

    /**
     * @return string
     */
    public function execute(): string
    {
        return $this->userTokenIssuer->create(
            $this->userContext,
            $this->tokenParamsFactory->create(),
        );
    }
}
