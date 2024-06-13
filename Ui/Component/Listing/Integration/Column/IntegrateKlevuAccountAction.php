<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Ui\Component\Listing\Integration\Column;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class IntegrateKlevuAccountAction extends Column
{
    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param mixed[] $components
     * @param mixed[] $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = [],
    ) {
        $this->storeManager = $storeManager;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

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
//            if (!$storeLevel && !$this->storeManager->isSingleStoreMode()) {
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
                        'scope_id' => $this->storeManager->isSingleStoreMode()
                            ? Store::DEFAULT_STORE_ID
                            : $item['store_id'] ?? null,
                        'scope' => $this->storeManager->isSingleStoreMode()
                            ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT
                            : ScopeInterface::SCOPE_STORES,
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
        if ((bool)$this->storeManager->isSingleStoreMode()) {
            return ($item['store_integrated'] ?? null)
                ? __('Edit Keys')
                : __('Integrate');
        }
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
        if ((bool)$this->storeManager->isSingleStoreMode()) {
            return $remove
                ? __('Remove Integration with Klevu.')
                : __('Integrate with Klevu.');
        }
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
        $label = $this->storeManager->isSingleStoreMode()
            ? __('Remove Keys')
            : __('Remove Store Keys');

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
                        'scope_id' => $this->storeManager->isSingleStoreMode()
                            ? Store::DEFAULT_STORE_ID
                            : $item['store_id'] ?? null,
                        'scope' => $this->storeManager->isSingleStoreMode()
                            ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT
                            : ScopeInterface::SCOPE_STORES,

                    ],
                ],
            ],
            'href' => '#',
            'label' => $label,
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
