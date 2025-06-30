<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Optional\Blog\Tests\Controllers;

use HdmBoot\Modules\Core\Storage\Models\Article;
use HdmBoot\Modules\Optional\Blog\Tests\BlogTestCase;

/**
 * Tests for BlogController.
 */
class BlogControllerTest extends BlogTestCase
{
    public function testHomeReturnsHtmlString(): void
    {
        $result = $this->blogController->home();

        $this->assertIsString($result);
        $this->assertStringContainsString('<!DOCTYPE html>', $result);
        $this->assertStringContainsString('MVA Bootstrap Blog', $result);
    }

    public function testHomeWithArticlesShowsArticleList(): void
    {
        // Create test articles
        $this->createTestArticles(2);

        $result = $this->blogController->home();

        $this->assertStringContainsString('Test Article 1', $result);
        $this->assertStringContainsString('Test Article 2', $result);
        $this->assertStringContainsString('Latest Articles', $result);
    }

    public function testHomeWithoutArticlesShowsWelcomeMessage(): void
    {
        $result = $this->blogController->home();

        $this->assertStringContainsString('Welcome to MVA Bootstrap Blog!', $result);
        $this->assertStringContainsString('No articles found', $result);
    }

    public function testArticleWithValidSlugReturnsHtml(): void
    {
        $article = $this->createTestArticle([
            'slug'  => 'test-article-slug',
            'title' => 'Test Article Title',
        ]);

        $result = $this->blogController->article('test-article-slug');

        $this->assertIsString($result);
        $this->assertStringContainsString('Test Article Title', $result);
        $this->assertStringContainsString('This is test content', $result);
    }

    public function testArticleWithInvalidSlugReturnsNotFound(): void
    {
        $result = $this->blogController->article('nonexistent-slug');

        $this->assertStringContainsString('Article Not Found', $result);
        $this->assertStringContainsString('Back to Blog', $result);
    }

    public function testCategoriesReturnsComingSoon(): void
    {
        $result = $this->blogController->categories();

        $this->assertStringContainsString('Categories - Coming Soon', $result);
    }

    public function testTagsReturnsComingSoon(): void
    {
        $result = $this->blogController->tags();

        $this->assertStringContainsString('Tags - Coming Soon', $result);
    }

    public function testAboutReturnsHtmlWithFeatures(): void
    {
        $result = $this->blogController->about();

        $this->assertIsString($result);
        $this->assertStringContainsString('About This Blog', $result);
        $this->assertStringContainsString('File-based storage', $result);
        $this->assertStringContainsString('Multi-database architecture', $result);
        $this->assertStringContainsString('Type safety', $result);
    }

    public function testHomeShowsCorrectStatistics(): void
    {
        // Create test articles with different states
        $this->createTestArticle(['published' => true]);
        $this->createTestArticle(['published' => false]);
        $this->createTestArticle(['published' => true, 'category' => 'tech']);

        $result = $this->blogController->home();

        // Should show total articles count
        $this->assertStringContainsString('Total Articles', $result);
        // Should show published count (2 published out of 3)
        $this->assertStringContainsString('Published', $result);
    }

    public function testArticleDisplaysCorrectMetadata(): void
    {
        $article = $this->createTestArticle([
            'title'        => 'Metadata Test Article',
            'author'       => 'John Doe',
            'reading_time' => 7,
            'tags'         => ['php', 'testing', 'blog'],
        ]);

        $result = $this->blogController->article($article->getAttribute('slug'));

        $this->assertStringContainsString('John Doe', $result);
        $this->assertStringContainsString('7 min read', $result);
        $this->assertStringContainsString('php', $result);
        $this->assertStringContainsString('testing', $result);
        $this->assertStringContainsString('blog', $result);
    }

    public function testHomeHandlesArticlesWithMissingData(): void
    {
        // Create article with minimal data
        $this->createTestArticle([
            'title'   => 'Minimal Article',
            'author'  => null,
            'excerpt' => null,
            'tags'    => null,
        ]);

        $result = $this->blogController->home();

        // Should handle missing data gracefully
        $this->assertStringContainsString('Minimal Article', $result);
        $this->assertStringContainsString('Unknown', $result); // Default author
    }
}
