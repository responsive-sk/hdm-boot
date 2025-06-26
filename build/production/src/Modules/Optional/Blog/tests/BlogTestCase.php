<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Optional\Blog\Tests;

use HdmBoot\Modules\Core\Storage\Models\Article;
use HdmBoot\Modules\Optional\Blog\Controllers\BlogController;
use PHPUnit\Framework\TestCase;

/**
 * Base test case for Blog module tests.
 */
abstract class BlogTestCase extends TestCase
{
    protected BlogController $blogController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->blogController = new BlogController();
    }

    protected function tearDown(): void
    {
        // Clean up test articles
        $this->cleanupTestArticles();
        parent::tearDown();
    }

    /**
     * Clean up test articles created during tests.
     */
    protected function cleanupTestArticles(): void
    {
        // This is a simplified cleanup - in real implementation
        // you would clean up the actual storage files
        // For now, we'll just reset any static caches

        // If Article model has static cache, clear it here
        // Article::clearCache();
    }

    /**
     * Create a test article for testing.
     *
     * @param array<string, mixed> $attributes
     */
    protected function createTestArticle(array $attributes = []): Article
    {
        $defaultAttributes = [
            'title'        => 'Test Article',
            'slug'         => 'test-article-' . uniqid(), // Make unique
            'author'       => 'Test Author',
            'content'      => 'This is test content for the article.',
            'excerpt'      => 'Test excerpt',
            'published'    => true,
            'published_at' => date('Y-m-d H:i:s'),
            'category'     => 'test',
            'tags'         => ['test', 'phpunit'],
            'reading_time' => 5,
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        // Create article but don't save to disk for tests
        $article = new Article();
        foreach ($attributes as $key => $value) {
            $article->setAttribute($key, $value);
        }

        return $article;
    }

    /**
     * Create multiple test articles.
     *
     * @param int $count
     *
     * @return array<int, Article>
     */
    protected function createTestArticles(int $count = 3): array
    {
        $articles = [];
        for ($i = 1; $i <= $count; ++$i) {
            $articles[] = $this->createTestArticle([
                'title'   => "Test Article {$i}",
                'slug'    => "test-article-{$i}",
                'content' => "Content for test article {$i}",
                'excerpt' => "Excerpt for article {$i}",
            ]);
        }

        return $articles;
    }

    /**
     * Assert that response is valid JSON.
     */
    protected function assertValidJson(string $json): void
    {
        $decoded = json_decode($json, true);
        $this->assertNotNull($decoded, 'Response should be valid JSON');
        $this->assertIsArray($decoded, 'Decoded JSON should be an array');
    }

    /**
     * Assert that API response has success structure.
     *
     * @param array<string, mixed> $response
     */
    protected function assertSuccessResponse(array $response): void
    {
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
    }

    /**
     * Assert that API response has error structure.
     *
     * @param array<string, mixed> $response
     */
    protected function assertErrorResponse(array $response): void
    {
        $this->assertArrayHasKey('error', $response);
        $this->assertIsString($response['error']);
    }
}
