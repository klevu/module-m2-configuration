<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\ViewModel\Config\Integration\Kmc;

use Klevu\Configuration\ViewModel\Config\FieldsetInterface;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Magento\Framework\Phrase;

class Links implements FieldsetInterface
{
    private const HELP_ARTICLE_LINK_INTEGRATION_STEPS = 'https://help.klevu.com/support/solutions/articles/5000871252-integration-steps-for-magento-2'; // phpcs:ignore Generic.Files.LineLength.TooLong

    /**
     * @var BaseUrlsProviderInterface
     */
    private BaseUrlsProviderInterface $baseUrlsProvider;

    /**
     * @param BaseUrlsProviderInterface $baseUrlsProvider
     */
    public function __construct(BaseUrlsProviderInterface $baseUrlsProvider)
    {
        $this->baseUrlsProvider = $baseUrlsProvider;
    }

    /**
     * @return string
     */
    public function getIntegrationStepsArticleLink(): string
    {
        return self::HELP_ARTICLE_LINK_INTEGRATION_STEPS;
    }

    /**
     * @return string
     */
    public function getKlevuMerchantCenterUrl(): string
    {
        return 'https://' . $this->baseUrlsProvider->getMerchantCenterUrl();
    }

    /**
     * @return string[]
     */
    public function getChildBlocks(): array
    {
        return [
            'klevu_integration_kmc_instructions',
        ];
    }

    /**
     * @return Phrase[][]
     */
    public function getMessages(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getStyles(): string
    {
        return '';
    }
}
