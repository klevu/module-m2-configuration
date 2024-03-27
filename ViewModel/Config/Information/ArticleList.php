<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\ViewModel\Config\Information;

use Klevu\Configuration\ViewModel\Config\ArticleListInterface;

class ArticleList implements ArticleListInterface
{
    /**
     * @var string[][][]|null
     */
    private readonly ?array $unProcessedArticles;
    /**
     * @var string[][][]|null
     */
    private ?array $articles = null;

    /**
     * @param string[][][]|null $articles
     */
    public function __construct(?array $articles = [])
    {
        $this->unProcessedArticles = $articles;
    }

    /**
     * @return bool
     */
    public function hasLinks(): bool
    {
        $links = $this->getLinks() ?? [];

        return (bool)count($links);
    }

    /**
     * @return string[][][]|null
     */
    public function getLinks(): ?array
    {
        if (null !== $this->articles) {
            return $this->articles;
        }
        if (!$this->unProcessedArticles) {
            return [];
        }
        foreach ($this->unProcessedArticles as $key => $unProcessedArticles) {
            $unSortedArticles = $this->removeArticlesWithoutValidLink(articles: $unProcessedArticles);
            $articles = $this->sortArticles(articles: $unSortedArticles);
            if ($articles) {
                $this->articles[$key] = $this->setTitleIfMissing(articles: $articles);
            }
        }

        return $this->articles;
    }

    /**
     * @return string
     */
    public function getStyles(): string
    {
        return '.klevu-ul-list-title {font-size: 1.2em;}'
            . '.klevu-ul-list{list-style-position: inside; list-style-type: none;}'
            . ' .klevu-ul-list li{padding: 0.2rem 0}';
    }

    /**
     * @param string[][] $articles
     *
     * @return string[][]
     */
    private function removeArticlesWithoutValidLink(?array $articles): array
    {
        return array_filter(
            array: $articles,
            callback: static fn (array $article): bool => (
                ($article[static::ARTICLE_LIST_LINK] ?? null)
                && filter_var(value: $article[static::ARTICLE_LIST_LINK], filter: FILTER_VALIDATE_URL)
            ),
        );
    }

    /**
     * @param string[][]|null $articles
     *
     * @return string[][]
     */
    private function sortArticles(?array $articles): array
    {
        $orderColumn = array_column(
            array: $articles,
            column_key: static::ARTICLE_LIST_ORDER,
        );
        array_multisort($orderColumn, SORT_ASC, $articles);

        return $articles;
    }

    /**
     * @param string[][]|null $articles
     *
     * @return string[][]
     */
    private function setTitleIfMissing(?array $articles): array
    {
        return array_map(
            callback: static function (array $article): array {
                if (!trim((string)($article[static::ARTICLE_LIST_TITLE] ?? ''))) {
                    $article[static::ARTICLE_LIST_TITLE] = $article[static::ARTICLE_LIST_LINK];
                }

                return $article;
            },
            array: $articles,
        );
    }
}
