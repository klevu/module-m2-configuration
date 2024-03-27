<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Ui\Component\Listing\Column;

use Magento\Framework\Phrase;
use Magento\Store\Model\ScopeInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class IntegrateKlevuAccount extends Column
{
    /**
     * @param mixed[] $dataSource
     *
     * @return mixed[]
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        // @TODO add when channels are available
        // $storeLevel = null !== $this->context->getRequestParam(key: 'store');
        // phpcs:disable SlevomatCodingStandard.PHP.DisallowReference.DisallowedAssigningByReference
        foreach ($dataSource['data']['items'] as &$item) {
            $name = $this->getName();
            $item[$name]['integrate_store'] = $this->getIntegrateStoreLink(item: $item);
            if ($item['store_integrated'] ?? null) {
                $item[$name]['remove_store'] = $this->getRemoveStoreLink(item: $item);
            }
            // @TODO add when channels are available
//            if (!$storeLevel) {
//                $item[$name]['integrate_website'] = $this->getIntegrateWebsiteLink(item: $item);
//                if ($item['website_integrated'] ?? null) {
//                    $item[$name]['remove_website'] = $this->getRemoveWebsiteLink(item: $item);
//                }
//            }
        }
        // phpcs:enable SlevomatCodingStandard.PHP.DisallowReference.DisallowedAssigningByReference

        return $dataSource;
    }

    /**
     * @param mixed[] $item
     *
     * @return mixed[]
     */
    private function getIntegrateWebsiteLink(array $item): array //@phpstan-ignore-line
    {
        return [
            'callback' => [
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_wizard_modal' .
                        '.klevu_integration_wizard_container.klevu_integration_wizard',
                    'target' => 'destroyInserted',
                ],
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_wizard_modal',
                    'target' => 'openModal',
                ],
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_wizard_modal',
                    'target' => 'setTitle',
                    'params' => $this->getTitle(scope: 'Website', scopeId: (string)$item['website']),
                ],
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_wizard_modal' .
                        '.klevu_integration_wizard_container.klevu_integration_wizard',
                    'target' => 'updateData',
                    'params' => [
                        'scope_id' => $item['website_id'] ?? null,
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                    ],
                ],
            ],
            'href' => '#',
            'label' => $this->getWebsiteLabel($item),
            'hidden' => false,
        ];
    }

    /**
     * @param mixed[] $item
     *
     * @return Phrase
     */
    private function getWebsiteLabel(array $item): Phrase
    {
        return ($item['website_integrated'] ?? null)
            ? __('Edit Website Keys')
            : __('Integrate Website');
    }

    /**
     * @param mixed[] $item
     *
     * @return mixed[]
     */
    private function getIntegrateStoreLink(array $item): array
    {
        return [
            'callback' => [
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_wizard_modal' .
                        '.klevu_integration_wizard_container.klevu_integration_wizard',
                    'target' => 'destroyInserted',
                ],
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_wizard_modal',
                    'target' => 'openModal',
                ],
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_wizard_modal',
                    'target' => 'setTitle',
                    'params' => $this->getTitle(scope: 'Store', scopeId: (string)$item['store']),
                ],
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_wizard_modal' .
                        '.klevu_integration_wizard_container.klevu_integration_wizard',
                    'target' => 'updateData',
                    'params' => [
                        'scope_id' => $item['store_id'] ?? null,
                        'scope' => ScopeInterface::SCOPE_STORES,
                    ],
                ],
            ],
            'href' => '#',
            'label' => $this->getStoreLabel($item),
            'hidden' => false,
        ];
    }

    /**
     * @param mixed[] $item
     *
     * @return Phrase
     */
    private function getStoreLabel(array $item): Phrase
    {
        return ($item['store_integrated'] ?? null)
            ? __('Edit Store Keys')
            : __('Integrate Store');
    }

    /**
     * @param string $scope
     * @param string $scopeId
     * @param bool $remove
     *
     * @return Phrase
     */
    private function getTitle(string $scope, string $scopeId, bool $remove = false): Phrase
    {
        $title = $remove
            ? 'Remove Integration with Klevu. %1: %2'
            : 'Integrate with Klevu. %1: %2';

        return __(
            $title,
            $scope,
            $scopeId,
        );
    }

    /**
     * @param mixed[] $item
     *
     * @return mixed[]
     */
    private function getRemoveStoreLink(array $item): array
    {
        return [
            'callback' => [
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_removal_modal' .
                        '.klevu_integration_removal_container.klevu_integration_removal',
                    'target' => 'destroyInserted',
                ],
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_removal_modal',
                    'target' => 'openModal',
                ],
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_removal_modal',
                    'target' => 'setTitle',
                    'params' => $this->getTitle(scope:'Store', scopeId: (string)$item['store'], remove: true),
                ],
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_removal_modal' .
                        '.klevu_integration_removal_container.klevu_integration_removal',
                    'target' => 'updateData',
                    'params' => [
                        'scope_id' => $item['store_id'] ?? null,
                        'scope' => ScopeInterface::SCOPE_STORES,
                    ],
                ],
            ],
            'href' => '#',
            'label' => __('Remove Store Keys'),
            'hidden' => false,
        ];
    }

    /**
     * @param mixed[] $item
     *
     * @return mixed[]
     */
    private function getRemoveWebsiteLink(array $item): array //@phpstan-ignore-line
    {
        return [
            'callback' => [
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_removal_modal' .
                        '.klevu_integration_removal_container.klevu_integration_removal',
                    'target' => 'destroyInserted',
                ],
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_removal_modal',
                    'target' => 'openModal',
                ],
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_removal_modal',
                    'target' => 'setTitle',
                    'params' => $this->getTitle(scope: 'Website', scopeId: (string)$item['website'], remove: true),
                ],
                [
                    'provider' => 'klevu_integration_store_listing.klevu_integration_store_listing' .
                        '.klevu_integration_removal_modal' .
                        '.klevu_integration_removal_container.klevu_integration_removal',
                    'target' => 'updateData',
                    'params' => [
                        'scope_id' => $item['website_id'] ?? null,
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                    ],
                ],
            ],
            'href' => '#',
            'label' => __('Remove Website Keys'),
            'hidden' => false,
        ];
    }
}
