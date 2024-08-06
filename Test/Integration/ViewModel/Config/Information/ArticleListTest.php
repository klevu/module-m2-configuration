<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Configuration\Test\Integration\ViewModel\Config\Information;

use Klevu\Configuration\ViewModel\Config\ArticleListInterface;
use Klevu\Configuration\ViewModel\Config\Information\ArticleList;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\ViewModel\Config\Information\ArticleList
 */
class ArticleListTest extends TestCase
{
    use ObjectInstantiationTrait;
    use TestImplementsInterfaceTrait;

    /**
     * @var ObjectManagerInterface
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = ArticleList::class;
        $this->interfaceFqcn = ArticleListInterface::class;
        $this->constructorArgumentDefaults = [
            'articles' => [],
        ];
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testGetLinks_FiltersOutMissingLinks(): void
    {
        $articles = [
            'section' => [
                'article-1' => [
                    ArticleListInterface::ARTICLE_LIST_ORDER => 10,
                    ArticleListInterface::ARTICLE_LIST_TITLE => 'Test Article 1',
                ],
                'article-2' => [
                    ArticleListInterface::ARTICLE_LIST_LINK => 'https://klevu.com',
                    ArticleListInterface::ARTICLE_LIST_ORDER => 10,
                    ArticleListInterface::ARTICLE_LIST_TITLE => 'Test Article 1',
                ],
            ],
        ];

        $viewModel = $this->instantiateTestObject([
            'articles' => $articles,
        ]);
        $links = $viewModel->getLinks();

        $this->assertCount(expectedCount: 1, haystack: $links);
    }

    /**
     * @dataProvider dataProvider_InvalidUrls
     */
    public function testGetLinks_FiltersOutInvalidLinks(mixed $invalidLink): void
    {
        $articles = [
            'section' => [
                'article-1' => [
                    ArticleListInterface::ARTICLE_LIST_LINK => $invalidLink,
                    ArticleListInterface::ARTICLE_LIST_ORDER => 10,
                    ArticleListInterface::ARTICLE_LIST_TITLE => 'Test Article 1',
                ],
                'article-2' => [
                    ArticleListInterface::ARTICLE_LIST_LINK => 'https://klevu.com',
                    ArticleListInterface::ARTICLE_LIST_ORDER => 20,
                    ArticleListInterface::ARTICLE_LIST_TITLE => 'Test Article 2',
                ],
            ],
        ];

        $viewModel = $this->instantiateTestObject([
            'articles' => $articles,
        ]);
        $links = $viewModel->getLinks();

        $this->assertCount(expectedCount: 1, haystack: $links);
        $keys = array_keys($links);
        $sectionLinks = $links[$keys[0]];
        $this->assertCount(expectedCount: 1, haystack: $sectionLinks);
        $keys = array_keys($sectionLinks);
        $this->assertSame(
            expected: 'https://klevu.com',
            actual: $sectionLinks[$keys[0]][ArticleListInterface::ARTICLE_LIST_LINK],
        );
        $this->assertSame(
            expected: 20,
            actual: $sectionLinks[$keys[0]][ArticleListInterface::ARTICLE_LIST_ORDER],
        );
        $this->assertSame(
            expected: 'Test Article 2',
            actual: $sectionLinks[$keys[0]][ArticleListInterface::ARTICLE_LIST_TITLE],
        );
    }

    /**
     * @dataProvider dataProvider_InvalidUrls
     */
    public function testHasLinks_ReturnsFalse_ForInvalidLinks(mixed $invalidLink): void
    {
        $articles = [
            'section' => [

                'article-1' => [
                    ArticleListInterface::ARTICLE_LIST_LINK => $invalidLink,
                    ArticleListInterface::ARTICLE_LIST_ORDER => 10,
                    ArticleListInterface::ARTICLE_LIST_TITLE => 'Test Article 1',
                ],
            ],
        ];

        $viewModel = $this->instantiateTestObject([
            'articles' => $articles,
        ]);
        $this->assertFalse(
            $viewModel->hasLinks(),
        );
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_InvalidUrls(): array
    {
        return [
            ['string'],
            ['string.url'],
            [false],
            [true],
            [null],
            [0],
            [1],
            [1.2],
            [['https://klevu.com']],
            [json_decode(json_encode(['1' => '2', '3' => '4']))],
        ];
    }

    public function testHasLinks_ReturnsTrue_ForValidLinks(): void
    {
        $articles = [
            'section' => [
                'article-1' => [
                    ArticleListInterface::ARTICLE_LIST_LINK => 'https://klevu.com',
                    ArticleListInterface::ARTICLE_LIST_ORDER => 10,
                    ArticleListInterface::ARTICLE_LIST_TITLE => 'Test Article 1',
                ],
            ],
        ];

        $viewModel = $this->instantiateTestObject([
            'articles' => $articles,
        ]);
        $this->assertTrue(
            $viewModel->hasLinks(),
        );
    }

    public function testGetLinks_ReturnsLinkAsTitle_TitleMissing(): void
    {
        $articles = [
            'section' => [
                'article-1' => [
                    ArticleListInterface::ARTICLE_LIST_LINK => 'https://help.klevu.com/support/solutions/articles',
                    ArticleListInterface::ARTICLE_LIST_ORDER => 20,
                ],
            ],
        ];

        $viewModel = $this->instantiateTestObject([
            'articles' => $articles,
        ]);
        $links = $viewModel->getLinks();

        $this->assertCount(expectedCount: 1, haystack: $links);
        $keys = array_keys($links);
        $sectionLinks = $links[$keys[0]];
        $this->assertCount(expectedCount: 1, haystack: $sectionLinks);
        $keys = array_keys($sectionLinks);
        $this->assertSame(
            expected: 'https://help.klevu.com/support/solutions/articles',
            actual: $sectionLinks[$keys[0]][ArticleListInterface::ARTICLE_LIST_LINK],
        );
        $this->assertSame(
            expected: 20,
            actual: $sectionLinks[$keys[0]][ArticleListInterface::ARTICLE_LIST_ORDER],
        );
        $this->assertSame(
            expected: 'https://help.klevu.com/support/solutions/articles',
            actual: $sectionLinks[$keys[0]][ArticleListInterface::ARTICLE_LIST_TITLE],
        );
    }
}
