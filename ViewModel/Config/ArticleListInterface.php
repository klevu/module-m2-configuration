<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\ViewModel\Config;

interface ArticleListInterface
{
    public const ARTICLE_LIST_LINK = 'link';
    public const ARTICLE_LIST_ORDER = 'order';
    public const ARTICLE_LIST_TITLE = 'title';

    /**
     * @return string[][][]|null
     */
    public function getLinks(): ?array;

    /**
     * @return bool
     */
    public function hasLinks(): bool;

    /**
     * @return string
     */
    public function getStyles(): string;
}
