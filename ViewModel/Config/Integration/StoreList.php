<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\ViewModel\Config\Integration;

use Klevu\Configuration\ViewModel\Config\FieldsetInterface;
use Magento\Framework\Phrase;

class StoreList implements FieldsetInterface
{
    /**
     * {@inherit-doc}
     *
     * @return Phrase[][]
     */
    public function getMessages(): array
    {
        return [
            'info' => [
                __(
                    'Note: An integration at Store Scope will override an integration at Website Scope.',
                ),
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function getChildBlocks(): array
    {
        return [
            'klevu_integration_store_listing_container',
        ];
    }

    /**
     * @return string
     */
    public function getStyles(): string
    {
        return '.accordion .form-inline .klevu-integration .config th,' .
            ' .accordion .form-inline .klevu-integration .config td {padding: 1.5rem;}';
    }
}
