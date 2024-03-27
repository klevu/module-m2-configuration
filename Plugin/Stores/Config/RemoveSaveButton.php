<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Plugin\Stores\Config;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\AbstractBlock;

class RemoveSaveButton
{
    private const BLOCK_ALIAS = 'save_button';
    private const CONFIG_SECTION_INFORMATION = 'klevu_information';
    private const CONFIG_SECTION_INTEGRATION = 'klevu_integration';

    /**
     * @var RequestInterface
     */
    private readonly RequestInterface $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(
        RequestInterface $request,
    ) {
        $this->request = $request;
    }

    // phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    /**
     * @param AbstractBlock $subject
     * @param AbstractBlock $result
     * @param string $alias
     * @param string $block
     * @param mixed[]|null $data
     *
     * @return AbstractBlock
     */
    public function afterAddChild(
        AbstractBlock $subject,
        AbstractBlock $result,
        string $alias,
        string $block,
        ?array $data = [],
    ): AbstractBlock {
        if (
            $alias === self::BLOCK_ALIAS
            && $this->isKlevuIntegrationStoresConfig()
        ) {
            $result->setData([]);
        }

        return $result;
    }
    // phpcs:enable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter

    /**
     * @return bool
     */
    private function isKlevuIntegrationStoresConfig(): bool
    {
        $section = $this->request->getParam(key: 'section');

        return in_array(
            needle: $section,
            haystack: [
                self::CONFIG_SECTION_INFORMATION,
                self::CONFIG_SECTION_INTEGRATION,
            ],
            strict: true,
        );
    }
}
