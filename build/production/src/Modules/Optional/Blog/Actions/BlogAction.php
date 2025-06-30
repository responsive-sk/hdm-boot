<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Optional\Blog\Actions;

use HdmBoot\Modules\Core\Storage\Models\Article;
use HdmBoot\SharedKernel\Services\ViewRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Blog Action.
 *
 * Handles blog-related HTTP requests using Action pattern.
 * Uses theme-aware view rendering for proper separation of concerns.
 */
class BlogAction
{
    private ViewRenderer $viewRenderer;

    public function __construct()
    {
        $this->viewRenderer = new ViewRenderer();
    }
    /**
     * Blog homepage.
     */
    public function home(): string
    {
        $articles = Article::published();

        // Sort by published date (newest first)
        usort($articles, function (Article $a, Article $b): int {
            $aDate = $a->getAttribute('published_at');
            $bDate = $b->getAttribute('published_at');
            $aDateString = is_string($aDate) ? $aDate : '';
            $bDateString = is_string($bDate) ? $bDate : '';

            return strcmp($bDateString, $aDateString);
        });

        $allArticles = Article::all();
        $categories = Article::getCategories();
        $tags = Article::getTags();

        // Category counts
        $categoryCounts = [];
        foreach ($categories as $category) {
            $categoryCounts[$category] = Article::byCategory($category);
        }

        return $this->viewRenderer->render('blog.home', [
            'articles' => $articles,
            'allArticles' => $allArticles,
            'categories' => $categories,
            'tags' => $tags,
            'categoryCounts' => $categoryCounts,
        ]);
    }

    /**
     * Single article view.
     */
    public function article(string $slug): string
    {
        $article = Article::find($slug);
        $htmlContent = '';

        if ($article) {
            $content = $article->getAttribute('content') ?? '';
            $htmlContent = $this->markdownToHtml(is_string($content) ? $content : '');
        }

        return $this->viewRenderer->render('blog.article', [
            'article' => $article,
            'htmlContent' => $htmlContent,
        ]);
    }

    /**
     * Categories page.
     */
    public function categories(): string
    {
        return '<h1>Categories - Coming Soon</h1>';
    }

    /**
     * Tags page.
     */
    public function tags(): string
    {
        return '<h1>Tags - Coming Soon</h1>';
    }

    /**
     * About page.
     */
    public function about(): string
    {
        return $this->viewRenderer->render('blog.about');
    }

    /**
     * Search articles.
     */
    public function search(?string $query = null): string
    {
        // Normalize query parameter
        $query = $query ?? '';
        $query = trim($query);

        // Get all articles for searching
        $allArticles = Article::all();

        // Filter articles based on search query
        $searchResults = [];
        if (!empty($query)) {
            $searchResults = $this->searchArticles($allArticles, $query);
        }

        // Get categories and tags for sidebar
        $categories = Article::getCategories();
        $tags = Article::getTags();
        $categoryCounts = [];
        foreach ($categories as $category) {
            $categoryCounts[$category] = Article::byCategory($category);
        }

        return $this->viewRenderer->render('blog.search', [
            'query' => $query,
            'articles' => $searchResults,
            'allArticles' => $allArticles,
            'categories' => $categories,
            'tags' => $tags,
            'categoryCounts' => $categoryCounts,
            'totalResults' => count($searchResults),
        ]);
    }

    /**
     * Category view.
     */
    public function category(string $category): string
    {
        // Get articles in this category
        $articles = Article::byCategory($category);

        // Filter only published articles
        $publishedArticles = array_filter($articles, function (Article $article) {
            return $article->getAttribute('published') === true;
        });

        // Sort by published date (newest first)
        usort($publishedArticles, function (Article $a, Article $b): int {
            $aDate = $a->getAttribute('published_at');
            $bDate = $b->getAttribute('published_at');
            $aDateString = is_string($aDate) ? $aDate : '';
            $bDateString = is_string($bDate) ? $bDate : '';

            return strcmp($bDateString, $aDateString);
        });

        // Get all data for sidebar
        $allArticles = Article::all();
        $categories = Article::getCategories();
        $tags = Article::getTags();
        $categoryCounts = [];
        foreach ($categories as $cat) {
            $categoryCounts[$cat] = Article::byCategory($cat);
        }

        return $this->viewRenderer->render('blog.category', [
            'category' => $category,
            'articles' => $publishedArticles,
            'allArticles' => $allArticles,
            'categories' => $categories,
            'tags' => $tags,
            'categoryCounts' => $categoryCounts,
            'totalResults' => count($publishedArticles),
        ]);
    }

    /**
     * Tag view.
     */
    public function tag(string $tag): string
    {
        // Get all articles and filter by tag
        $allArticles = Article::all();
        $taggedArticles = [];

        foreach ($allArticles as $article) {
            $articleTags = $article->getAttribute('tags') ?? [];
            if (is_array($articleTags) && in_array($tag, $articleTags, true) && $article->getAttribute('published')) {
                $taggedArticles[] = $article;
            }
        }

        // Sort by published date (newest first)
        usort($taggedArticles, function (Article $a, Article $b): int {
            $aDate = $a->getAttribute('published_at');
            $bDate = $b->getAttribute('published_at');
            $aDateString = is_string($aDate) ? $aDate : '';
            $bDateString = is_string($bDate) ? $bDate : '';

            return strcmp($bDateString, $aDateString);
        });

        // Get data for sidebar
        $categories = Article::getCategories();
        $tags = Article::getTags();
        $categoryCounts = [];
        foreach ($categories as $category) {
            $categoryCounts[$category] = Article::byCategory($category);
        }

        return $this->viewRenderer->render('blog.tag', [
            'tag' => $tag,
            'articles' => $taggedArticles,
            'allArticles' => $allArticles,
            'categories' => $categories,
            'tags' => $tags,
            'categoryCounts' => $categoryCounts,
            'totalResults' => count($taggedArticles),
        ]);
    }

    /**
     * API: Get articles list.
     */
    public function apiList(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $articles = Article::all();
            $articlesData = [];

            foreach ($articles as $article) {
                $articlesData[] = [
                    'id'         => $article->getKey(),
                    'slug'       => $article->getAttribute('slug'),
                    'title'      => $article->getAttribute('title'),
                    'excerpt'    => $article->getAttribute('excerpt'),
                    'published'  => $article->getAttribute('published'),
                    'created_at' => $article->getAttribute('created_at'),
                    'updated_at' => $article->getAttribute('updated_at'),
                    'tags'       => $article->getAttribute('tags') ?? [],
                ];
            }

            $response->getBody()->write(json_encode([
                'success'  => true,
                'articles' => $articlesData,
                'count'    => count($articlesData),
            ]) ?: '{"error": "JSON encoding failed"}');

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to fetch articles: ' . $e->getMessage(),
            ]) ?: '{"error": "JSON encoding failed"}');

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * API: Get single article.
     *
     * @param array<string, string> $args
     */
    public function apiShow(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $slug = $args['slug'] ?? '';
            $article = Article::find($slug);

            if (!$article) {
                $response->getBody()->write(json_encode([
                    'error' => 'Article not found',
                ]) ?: '{"error": "JSON encoding failed"}');

                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $articleData = [
                'id'          => $article->getKey(),
                'slug'        => $article->getAttribute('slug'),
                'title'       => $article->getAttribute('title'),
                'content'     => $article->getAttribute('content'),
                'excerpt'     => $article->getAttribute('excerpt'),
                'author'      => $article->getAttribute('author'),
                'published'   => $article->getAttribute('published'),
                'featured'    => $article->getAttribute('featured'),
                'category'    => $article->getAttribute('category'),
                'tags'        => $article->getAttribute('tags') ?? [],
                'created_at'  => $article->getAttribute('created_at'),
                'updated_at'  => $article->getAttribute('updated_at'),
                'published_at' => $article->getAttribute('published_at'),
                'reading_time' => $article->getAttribute('reading_time'),
            ];

            $response->getBody()->write(json_encode([
                'success' => true,
                'article' => $articleData,
            ]) ?: '{"error": "JSON encoding failed"}');

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to fetch article: ' . $e->getMessage(),
            ]) ?: '{"error": "JSON encoding failed"}');

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Simple markdown to HTML conversion.
     */
    private function markdownToHtml(string $markdown): string
    {
        // Basic markdown conversion
        $html = htmlspecialchars($markdown);

        // Headers
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html) ?? $html;
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html) ?? $html;
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html) ?? $html;

        // Bold and italic
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html) ?? $html;
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html) ?? $html;

        // Code blocks
        $html = preg_replace('/```(.+?)```/s', '<pre><code>$1</code></pre>', $html) ?? $html;
        $html = preg_replace('/`(.+?)`/', '<code>$1</code>', $html) ?? $html;

        // Line breaks
        $html = nl2br($html);

        return $html;
    }

    /**
     * Search articles by query.
     *
     * @param array<Article> $articles
     * @return array<Article>
     */
    private function searchArticles(array $articles, string $query): array
    {
        $query = strtolower(trim($query));
        if (empty($query)) {
            return [];
        }

        $results = [];
        $queryWords = explode(' ', $query);

        foreach ($articles as $article) {
            $score = 0;

            // Get article content for searching
            $title = strtolower(is_string($article->getAttribute('title')) ? $article->getAttribute('title') : '');
            $content = strtolower(is_string($article->getAttribute('content')) ? $article->getAttribute('content') : '');
            $excerpt = strtolower(is_string($article->getAttribute('excerpt')) ? $article->getAttribute('excerpt') : '');
            $category = strtolower(is_string($article->getAttribute('category')) ? $article->getAttribute('category') : '');
            $tagsRaw = $article->getAttribute('tags');
            $tags = [];
            if (is_array($tagsRaw)) {
                foreach ($tagsRaw as $tag) {
                    if (is_string($tag)) {
                        $tags[] = strtolower($tag);
                    }
                }
            }
            $author = strtolower(is_string($article->getAttribute('author')) ? $article->getAttribute('author') : '');

            // Only search published articles
            if ($article->getAttribute('published') !== true) {
                continue;
            }

            // Score based on matches
            foreach ($queryWords as $word) {
                // Title matches (highest score)
                if (strpos($title, $word) !== false) {
                    $score += 10;
                }

                // Category matches
                if (strpos($category, $word) !== false) {
                    $score += 8;
                }

                // Tag matches
                foreach ($tags as $tag) {
                    if (strpos($tag, $word) !== false) {
                        $score += 6;
                    }
                }

                // Excerpt matches
                if (strpos($excerpt, $word) !== false) {
                    $score += 4;
                }

                // Author matches
                if (strpos($author, $word) !== false) {
                    $score += 3;
                }

                // Content matches (lowest score but still relevant)
                if (strpos($content, $word) !== false) {
                    $score += 1;
                }
            }

            // Add to results if any matches found
            if ($score > 0) {
                $results[] = [
                    'article' => $article,
                    'score' => $score
                ];
            }
        }

        // Sort by score (highest first)
        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Return only articles (without scores)
        return array_map(function ($result) {
            return $result['article'];
        }, $results);
    }

    /**
     * API: Create new article.
     */
    public function apiCreate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (!is_array($data) || !isset($data['title']) || !isset($data['content'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Title and content are required',
            ]) ?: '{"error": "JSON encoding failed"}');

            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            // Create article using enhanced Orbit-style API
            $article = Article::create([
                'title'     => $data['title'],
                'content'   => $data['content'],
                'excerpt'   => $data['excerpt'] ?? '',
                'published' => $data['published'] ?? false,
                'tags'      => $data['tags'] ?? [],
                'meta'      => $data['meta'] ?? [],
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'article' => [
                    'id'         => $article->getKey(),
                    'slug'       => $article->getAttribute('slug'),
                    'title'      => $article->getAttribute('title'),
                    'published'  => $article->getAttribute('published'),
                    'created_at' => $article->getAttribute('created_at'),
                ],
            ]) ?: '{"error": "JSON encoding failed"}');

            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to create article: ' . $e->getMessage(),
            ]) ?: '{"error": "JSON encoding failed"}');

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * API: Update article.
     *
     * @param array<string, string> $args
     */
    public function apiUpdate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $response->getBody()->write(json_encode(['error' => 'Update not implemented yet']) ?: '{"error": "JSON encoding failed"}');

        return $response->withStatus(501)->withHeader('Content-Type', 'application/json');
    }

    /**
     * API: Delete article.
     *
     * @param array<string, string> $args
     */
    public function apiDelete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $response->getBody()->write(json_encode(['error' => 'Delete not implemented yet']) ?: '{"error": "JSON encoding failed"}');

        return $response->withStatus(501)->withHeader('Content-Type', 'application/json');
    }

    /**
     * API: Get statistics.
     */
    public function apiStats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response->getBody()->write(json_encode(['error' => 'Stats not implemented yet']) ?: '{"error": "JSON encoding failed"}');

        return $response->withStatus(501)->withHeader('Content-Type', 'application/json');
    }

    /**
     * API: Search articles.
     */
    public function apiSearch(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response->getBody()->write(json_encode(['error' => 'Search not implemented yet']) ?: '{"error": "JSON encoding failed"}');

        return $response->withStatus(501)->withHeader('Content-Type', 'application/json');
    }

    /**
     * API: Get categories.
     */
    public function apiCategories(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response->getBody()->write(json_encode(['error' => 'Categories not implemented yet']) ?: '{"error": "JSON encoding failed"}');

        return $response->withStatus(501)->withHeader('Content-Type', 'application/json');
    }

    /**
     * API: Get tags.
     */
    public function apiTags(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response->getBody()->write(json_encode(['error' => 'Tags not implemented yet']) ?: '{"error": "JSON encoding failed"}');

        return $response->withStatus(501)->withHeader('Content-Type', 'application/json');
    }
}
