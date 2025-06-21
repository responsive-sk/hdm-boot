<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Optional\Blog\Controllers;

use MvaBootstrap\Modules\Core\Storage\Services\FileStorageService;
use MvaBootstrap\Modules\Core\Storage\Models\Article;
use MvaBootstrap\SharedKernel\Services\PathsFactory;
use ResponsiveSk\Slim4Paths\Paths;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Blog Controller.
 *
 * Handles blog-related routes and renders templates.
 * Part of the Optional Blog module - returns HTML strings for routing.
 */
class BlogController
{
    private Paths $paths;

    public function __construct(?Paths $paths = null)
    {
        // Initialize Paths service using factory (secure, no hardcoded paths)
        $this->paths = $paths ?? PathsFactory::create();

        // Setup storage using Core Storage module with secure paths
        $storageService = new FileStorageService($this->paths->content(), $this->paths);
        Article::setStorageService($storageService);
    }

    /**
     * Blog homepage.
     */
    public function home(): string
    {
        $articles = Article::published();

        // Sort by published date (newest first)
        usort($articles, function ($a, $b) {
            $aDate = $a->getAttribute('published_at') ?? '';
            $bDate = $b->getAttribute('published_at') ?? '';
            return strcmp($bDate, $aDate);
        });

        $allArticles = Article::all();
        $categories = Article::getCategories();
        $tags = Article::getTags();

        // Category counts
        $categoryCounts = [];
        foreach ($categories as $category) {
            $categoryCounts[$category] = Article::byCategory($category);
        }

        return $this->renderBlogHome($articles, $allArticles, $categories, $tags, $categoryCounts);
    }

    /**
     * Single article view.
     */
    public function article(string $slug): string
    {
        $article = Article::find($slug);
        return $this->renderArticle($article);
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
        return $this->renderAbout();
    }

    /**
     * Render blog homepage HTML.
     *
     * @param array<int, Article> $articles
     * @param array<int, Article> $allArticles
     * @param array<int, string> $categories
     * @param array<int, string> $tags
     * @param array<string, array<int, Article>> $categoryCounts
     */
    private function renderBlogHome(array $articles, array $allArticles, array $categories, array $tags, array $categoryCounts): string
    {
        $totalArticles = count($allArticles);
        $publishedCount = count($articles);

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MVA Bootstrap Blog</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 20px; background: #f8fafc; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { background: #2563eb; color: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; }
        .nav { margin: 1rem 0; }
        .nav a { color: white; text-decoration: none; margin: 0 1rem; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 4px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
        .stat { background: white; padding: 1rem; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2rem; font-weight: bold; color: #2563eb; }
        .article { background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .article h2 { margin: 0 0 0.5rem 0; color: #1f2937; }
        .article h2 a { color: inherit; text-decoration: none; }
        .meta { color: #6b7280; font-size: 0.875rem; margin-bottom: 1rem; }
        .tag { background: #e5e7eb; color: #374151; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-right: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 MVA Bootstrap Blog</h1>
            <div class="nav">
                <a href="/blog">Home</a>
                <a href="/blog/categories">Categories</a>
                <a href="/blog/tags">Tags</a>
                <a href="/blog/about">About</a>
                <a href="/">← Back to Main</a>
            </div>
        </div>

        <div class="stats">
            <div class="stat"><div class="stat-number">' . $totalArticles . '</div><div>Total Articles</div></div>
            <div class="stat"><div class="stat-number">' . $publishedCount . '</div><div>Published</div></div>
            <div class="stat"><div class="stat-number">' . count($categories) . '</div><div>Categories</div></div>
            <div class="stat"><div class="stat-number">' . count($tags) . '</div><div>Tags</div></div>
        </div>';

        if (empty($articles)) {
            $html .= '<div class="article">
                <h2>Welcome to MVA Bootstrap Blog!</h2>
                <p>No articles found. This demonstrates our <strong>Orbit-style content management system</strong>.</p>
                <p>Articles are stored as Markdown files with YAML front-matter in <code>content/articles/</code>.</p>
                <p>Try creating an article file to see it appear here automatically!</p>
            </div>';
        } else {
            $html .= '<h2>Latest Articles</h2>';
            foreach ($articles as $article) {
                $title = htmlspecialchars($article->getAttribute('title') ?? 'Untitled');
                $author = htmlspecialchars($article->getAttribute('author') ?? 'Unknown');
                $slug = $article->getAttribute('slug');
                $excerpt = htmlspecialchars($article->getAttribute('excerpt') ?? '');
                $publishedAt = date('F j, Y', strtotime($article->getAttribute('published_at') ?? 'now'));
                $readingTime = $article->getAttribute('reading_time') ?? 1;
                $category = $article->getAttribute('category');
                $tags = $article->getAttribute('tags') ?? [];

                $html .= '<article class="article">
                    <h2><a href="/blog/article/' . urlencode($slug) . '">' . $title . '</a></h2>
                    <div class="meta">By ' . $author . ' • ' . $publishedAt . ' • ' . $readingTime . ' min read';

                if ($category) {
                    $html .= ' • ' . ucfirst($category);
                }

                $html .= '</div>';

                if ($excerpt) {
                    $html .= '<p>' . $excerpt . '</p>';
                }

                $html .= '<a href="/blog/article/' . urlencode($slug) . '">Read more →</a>';

                if (!empty($tags)) {
                    $html .= '<div style="margin-top: 1rem;">';
                    foreach ($tags as $tag) {
                        $html .= '<span class="tag">' . htmlspecialchars($tag) . '</span>';
                    }
                    $html .= '</div>';
                }

                $html .= '</article>';
            }
        }

        $html .= '</div></body></html>';

        return $html;
    }

    /**
     * Render single article HTML.
     */
    private function renderArticle(?Article $article): string
    {
        if (!$article) {
            return '<h1>Article Not Found</h1><p><a href="/blog">← Back to Blog</a></p>';
        }

        $title = htmlspecialchars($article->getAttribute('title') ?? 'Untitled');
        $author = htmlspecialchars($article->getAttribute('author') ?? 'Unknown');
        $publishedAt = date('F j, Y', strtotime($article->getAttribute('published_at') ?? 'now'));
        $readingTime = $article->getAttribute('reading_time') ?? 1;
        $content = $article->getAttribute('content') ?? '';
        $tags = $article->getAttribute('tags') ?? [];

        // Simple markdown to HTML conversion
        $content = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $content);
        $content = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $content);
        $content = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $content);
        $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content);
        $content = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $content);
        $content = preg_replace('/`(.+?)`/', '<code>$1</code>', $content);
        $content = nl2br($content);

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $title . ' - MVA Bootstrap Blog</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 20px; background: #f8fafc; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { background: #2563eb; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; }
        .nav a { color: white; text-decoration: none; margin: 0 1rem; }
        .article { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .meta { color: #6b7280; font-size: 0.875rem; margin-bottom: 2rem; }
        .content { line-height: 1.8; }
        .content h1, .content h2, .content h3 { color: #1f2937; margin: 1.5rem 0 1rem 0; }
        .content p { margin-bottom: 1rem; }
        .content code { background: #f3f4f6; padding: 0.125rem 0.25rem; border-radius: 3px; }
        .tag { background: #e5e7eb; color: #374151; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-right: 0.5rem; }
        .back-link { color: #2563eb; text-decoration: none; margin-bottom: 1rem; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 MVA Bootstrap Blog</h1>
            <div class="nav">
                <a href="/blog">← Back to Blog</a>
            </div>
        </div>

        <article class="article">
            <h1>' . $title . '</h1>
            <div class="meta">
                By ' . $author . ' • ' . $publishedAt . ' • ' . $readingTime . ' min read
            </div>
            <div class="content">' . $content . '</div>';

        if (!empty($tags)) {
            $html .= '<div style="margin-top: 2rem;">';
            foreach ($tags as $tag) {
                $html .= '<span class="tag">' . htmlspecialchars($tag) . '</span>';
            }
            $html .= '</div>';
        }

        $html .= '</article></div></body></html>';

        return $html;
    }

    /**
     * Render about page HTML.
     */
    private function renderAbout(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - MVA Bootstrap Blog</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 20px; background: #f8fafc; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { background: #2563eb; color: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; }
        .nav a { color: white; text-decoration: none; margin: 0 1rem; }
        .article { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 MVA Bootstrap Blog</h1>
            <div class="nav">
                <a href="/blog">← Back to Blog</a>
                <a href="/">← Back to Main</a>
            </div>
        </div>

        <div class="article">
            <h2>About This Blog</h2>
            <p>This is a demonstration of <strong>MVA Bootstrap\'s Orbit-style content management system</strong>.</p>

            <h3>Features Demonstrated:</h3>
            <ul>
                <li>📝 <strong>File-based storage</strong> - Articles stored as Markdown files with YAML front-matter</li>
                <li>🗄️ <strong>Multi-database architecture</strong> - Separate SQLite databases in var/orbit/</li>
                <li>⚡ <strong>Performance</strong> - Fast file-based queries with caching</li>
                <li>🔍 <strong>Rich querying</strong> - Categories, tags, search, and more</li>
                <li>🎯 <strong>Type safety</strong> - PHPStan Level MAX compliance</li>
                <li>🏗️ <strong>Modular architecture</strong> - Core and Optional modules</li>
            </ul>

            <h3>Architecture:</h3>
            <pre>content/           # Git-friendly content
├── articles/      # Markdown files
└── docs/         # Documentation

var/orbit/        # Runtime databases
├── app.db        # Application data
├── mark.db       # Admin system
├── cache.db      # Performance cache
└── analytics.db  # Metrics & reporting</pre>

            <h3>Modules Used:</h3>
            <ul>
                <li><strong>Core/Storage</strong> - Hybrid file + database storage</li>
                <li><strong>Core/Template</strong> - Template rendering system</li>
                <li><strong>Optional/Blog</strong> - This blog interface</li>
            </ul>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * API: Create new article (Orbit CMS style).
     */
    public function apiCreate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (!$data || !isset($data['title']) || !isset($data['content'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Title and content are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            // Create article using enhanced Orbit-style API
            $article = Article::create([
                'title' => $data['title'],
                'content' => $data['content'],
                'excerpt' => $data['excerpt'] ?? '',
                'published' => $data['published'] ?? false,
                'tags' => $data['tags'] ?? [],
                'meta' => $data['meta'] ?? []
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'article' => [
                    'id' => $article->getKey(),
                    'slug' => $article->getAttribute('slug'),
                    'title' => $article->getAttribute('title'),
                    'published' => $article->getAttribute('published'),
                    'created_at' => $article->getAttribute('created_at')
                ]
            ]));

            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to create article: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
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
                    'id' => $article->getKey(),
                    'slug' => $article->getAttribute('slug'),
                    'title' => $article->getAttribute('title'),
                    'excerpt' => $article->getAttribute('excerpt'),
                    'published' => $article->getAttribute('published'),
                    'created_at' => $article->getAttribute('created_at'),
                    'updated_at' => $article->getAttribute('updated_at'),
                    'tags' => $article->getAttribute('tags') ?? []
                ];
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'articles' => $articlesData,
                'count' => count($articlesData)
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to fetch articles: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * API: Get single article.
     */
    public function apiShow(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $slug = $args['slug'] ?? '';

        if (empty($slug)) {
            $response->getBody()->write(json_encode(['error' => 'Slug is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $article = Article::where('slug', $slug)->first();

            if (!$article) {
                $response->getBody()->write(json_encode(['error' => 'Article not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'article' => [
                    'id' => $article->getKey(),
                    'slug' => $article->getAttribute('slug'),
                    'title' => $article->getAttribute('title'),
                    'content' => $article->getAttribute('content'),
                    'excerpt' => $article->getAttribute('excerpt'),
                    'published' => $article->getAttribute('published'),
                    'author' => $article->getAttribute('author'),
                    'category' => $article->getAttribute('category'),
                    'tags' => $article->getAttribute('tags') ?? [],
                    'meta' => $article->getAttribute('meta') ?? [],
                    'reading_time' => $article->getAttribute('reading_time'),
                    'created_at' => $article->getAttribute('created_at'),
                    'updated_at' => $article->getAttribute('updated_at'),
                    'published_at' => $article->getAttribute('published_at')
                ]
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to fetch article: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
