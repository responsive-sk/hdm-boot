<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Optional\Blog\Tests\Models;

use HdmBoot\Modules\Core\Storage\Models\Article;
use HdmBoot\Modules\Optional\Blog\Tests\BlogTestCase;

/**
 * Tests for Article model.
 */
class ArticleTest extends BlogTestCase
{
    public function testCreateArticleWithBasicData(): void
    {
        $article = Article::create([
            'title'   => 'Test Article',
            'content' => 'This is test content.',
            'author'  => 'Test Author',
        ]);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals('Test Article', $article->getAttribute('title'));
        $this->assertEquals('This is test content.', $article->getAttribute('content'));
        $this->assertEquals('Test Author', $article->getAttribute('author'));
    }

    public function testArticleSlugIsGeneratedFromTitle(): void
    {
        $article = Article::create([
            'title'   => 'This is a Test Article!',
            'content' => 'Content',
            'author'  => 'Author',
        ]);

        $slug = $article->getAttribute('slug');
        $this->assertEquals('this-is-a-test-article', $slug);
    }

    public function testArticleCalculatesReadingTime(): void
    {
        $longContent = str_repeat('word ', 400); // ~400 words

        $article = Article::create([
            'title'   => 'Long Article',
            'content' => $longContent,
            'author'  => 'Author',
        ]);

        $readingTime = $article->getAttribute('reading_time');
        $this->assertGreaterThan(1, $readingTime); // Should be more than 1 minute
        $this->assertLessThan(5, $readingTime); // Should be less than 5 minutes
    }

    public function testPublishedArticlesFilter(): void
    {
        // Create published article
        $this->createTestArticle([
            'title'     => 'Published Article',
            'published' => true,
        ]);

        // Create unpublished article
        $this->createTestArticle([
            'title'     => 'Draft Article',
            'published' => false,
        ]);

        $publishedArticles = Article::published();

        $this->assertCount(1, $publishedArticles);
        $this->assertEquals('Published Article', $publishedArticles[0]->getAttribute('title'));
    }

    public function testFeaturedArticlesFilter(): void
    {
        // Create featured article
        $this->createTestArticle([
            'title'     => 'Featured Article',
            'published' => true,
            'featured'  => true,
        ]);

        // Create regular article
        $this->createTestArticle([
            'title'     => 'Regular Article',
            'published' => true,
            'featured'  => false,
        ]);

        $featuredArticles = Article::featured();

        $this->assertCount(1, $featuredArticles);
        $this->assertEquals('Featured Article', $featuredArticles[0]->getAttribute('title'));
    }

    public function testArticlesByCategory(): void
    {
        $this->createTestArticle([
            'title'     => 'Tech Article',
            'category'  => 'technology',
            'published' => true,
        ]);

        $this->createTestArticle([
            'title'     => 'News Article',
            'category'  => 'news',
            'published' => true,
        ]);

        $techArticles = Article::byCategory('technology');
        $newsArticles = Article::byCategory('news');

        $this->assertCount(1, $techArticles);
        $this->assertCount(1, $newsArticles);
        $this->assertEquals('Tech Article', $techArticles[0]->getAttribute('title'));
        $this->assertEquals('News Article', $newsArticles[0]->getAttribute('title'));
    }

    public function testArticlesByTag(): void
    {
        $this->createTestArticle([
            'title'     => 'PHP Article',
            'tags'      => ['php', 'programming'],
            'published' => true,
        ]);

        $this->createTestArticle([
            'title'     => 'JavaScript Article',
            'tags'      => ['javascript', 'programming'],
            'published' => true,
        ]);

        $phpArticles = Article::byTag('php');
        $programmingArticles = Article::byTag('programming');

        $this->assertCount(1, $phpArticles);
        $this->assertCount(2, $programmingArticles);
        $this->assertEquals('PHP Article', $phpArticles[0]->getAttribute('title'));
    }

    public function testRecentArticlesOrdering(): void
    {
        // Create articles with different dates
        $this->createTestArticle([
            'title'        => 'Old Article',
            'published_at' => '2023-01-01 10:00:00',
            'published'    => true,
        ]);

        $this->createTestArticle([
            'title'        => 'New Article',
            'published_at' => '2023-12-01 10:00:00',
            'published'    => true,
        ]);

        $recentArticles = Article::recent(2);

        $this->assertCount(2, $recentArticles);
        // Should be ordered by date descending (newest first)
        $this->assertEquals('New Article', $recentArticles[0]->getAttribute('title'));
        $this->assertEquals('Old Article', $recentArticles[1]->getAttribute('title'));
    }

    public function testArticleSearch(): void
    {
        $this->createTestArticle([
            'title'     => 'PHP Programming Guide',
            'content'   => 'Learn PHP programming basics',
            'published' => true,
        ]);

        $this->createTestArticle([
            'title'     => 'JavaScript Tutorial',
            'content'   => 'JavaScript fundamentals',
            'published' => true,
        ]);

        $phpResults = Article::search('php');
        $programmingResults = Article::search('programming');

        $this->assertCount(1, $phpResults);
        $this->assertCount(1, $programmingResults);
        $this->assertEquals('PHP Programming Guide', $phpResults[0]->getAttribute('title'));
    }

    public function testGetCategoriesReturnsUniqueList(): void
    {
        $this->createTestArticle(['category' => 'tech', 'published' => true]);
        $this->createTestArticle(['category' => 'tech', 'published' => true]);
        $this->createTestArticle(['category' => 'news', 'published' => true]);

        $categories = Article::getCategories();

        $this->assertCount(2, $categories);
        $this->assertContains('tech', $categories);
        $this->assertContains('news', $categories);
    }

    public function testGetTagsReturnsUniqueList(): void
    {
        $this->createTestArticle(['tags' => ['php', 'web'], 'published' => true]);
        $this->createTestArticle(['tags' => ['php', 'backend'], 'published' => true]);
        $this->createTestArticle(['tags' => ['javascript'], 'published' => true]);

        $tags = Article::getTags();

        $this->assertContains('php', $tags);
        $this->assertContains('web', $tags);
        $this->assertContains('backend', $tags);
        $this->assertContains('javascript', $tags);
        // PHP should appear only once despite being in multiple articles
        $this->assertEquals(1, array_count_values($tags)['php']);
    }

    public function testWhereQueryBuilder(): void
    {
        $article = $this->createTestArticle(['slug' => 'test-slug']);

        $found = Article::where('slug', 'test-slug')->first();
        $notFound = Article::where('slug', 'nonexistent')->first();

        $this->assertInstanceOf(Article::class, $found);
        $this->assertEquals('test-slug', $found->getAttribute('slug'));
        $this->assertNull($notFound);
    }

    public function testIsPublishedMethod(): void
    {
        $publishedArticle = $this->createTestArticle([
            'published'    => true,
            'published_at' => date('Y-m-d H:i:s', time() - 3600), // 1 hour ago
        ]);

        $draftArticle = $this->createTestArticle([
            'published' => false,
        ]);

        $futureArticle = $this->createTestArticle([
            'published'    => true,
            'published_at' => date('Y-m-d H:i:s', time() + 3600), // 1 hour in future
        ]);

        $this->assertTrue($publishedArticle->isPublished());
        $this->assertFalse($draftArticle->isPublished());
        $this->assertFalse($futureArticle->isPublished());
    }

    public function testGenerateExcerpt(): void
    {
        $article = $this->createTestArticle([
            'excerpt' => null, // Force generation from content
            'content' => 'This is a very long content that should be truncated when generating an excerpt. It contains multiple sentences and should be cut off at the specified length.',
        ]);

        $excerpt = $article->generateExcerpt(50);

        $this->assertLessThanOrEqual(53, strlen($excerpt)); // 50 + "..."
        $this->assertStringEndsWith('...', $excerpt);
    }
}
