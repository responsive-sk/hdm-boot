<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Storage\Models;

/**
 * Article Model.
 *
 * Represents articles stored as Markdown files with YAML front-matter.
 * Articles are the main content type for the application.
 */
class Article extends FileModel
{
    /**
     * Storage driver name.
     */
    protected static string $driver = 'markdown';

    /**
     * Primary key field name.
     */
    protected string $primaryKey = 'slug';

    /**
     * Define the schema for articles.
     *
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        return [
            'title' => 'string|required',
            'slug' => 'string|required|unique',
            'author' => 'string|required',
            'published_at' => 'datetime|nullable',
            'published' => 'boolean|default:false',
            'featured' => 'boolean|default:false',
            'excerpt' => 'string|nullable',
            'tags' => 'array|nullable',
            'category' => 'string|nullable',
            'reading_time' => 'integer|nullable',
            'seo_title' => 'string|nullable',
            'seo_description' => 'string|nullable',
            'content' => 'text', // Markdown content
        ];
    }

    /**
     * Get published articles.
     *
     * @return array<int, static>
     */
    public static function published(): array
    {
        return array_filter(static::all(), function (Article $article) {
            $publishedAt = $article->getAttribute('published_at');
            return $article->getAttribute('published') === true &&
                   ($publishedAt === null || (is_string($publishedAt) && strtotime($publishedAt) <= time()));
        });
    }

    /**
     * Get featured articles.
     *
     * @return array<int, static>
     */
    public static function featured(): array
    {
        return array_filter(static::published(), function (Article $article) {
            return $article->getAttribute('featured') === true;
        });
    }

    /**
     * Get articles by category.
     *
     * @return array<int, static>
     */
    public static function byCategory(string $category): array
    {
        return array_filter(static::published(), function (Article $article) use ($category) {
            return $article->getAttribute('category') === $category;
        });
    }

    /**
     * Get articles by tag.
     *
     * @return array<int, static>
     */
    public static function byTag(string $tag): array
    {
        return array_filter(static::published(), function (Article $article) use ($tag) {
            $tags = $article->getAttribute('tags') ?? [];
            return is_array($tags) && in_array($tag, $tags);
        });
    }

    /**
     * Get recent articles.
     *
     * @return array<int, static>
     */
    public static function recent(int $limit = 10): array
    {
        $published = static::published();

        // Sort by published_at descending
        usort($published, function (Article $a, Article $b) {
            $aDateRaw = $a->getAttribute('published_at');
            $bDateRaw = $b->getAttribute('published_at');
            $aDate = is_string($aDateRaw) ? $aDateRaw : '';
            $bDate = is_string($bDateRaw) ? $bDateRaw : '';
            return strcmp($bDate, $aDate); // Descending order
        });

        return array_slice($published, 0, $limit);
    }

    /**
     * Search articles by title or content.
     *
     * @return array<int, static>
     */
    public static function search(string $query): array
    {
        $query = strtolower($query);

        return array_filter(static::published(), function (Article $article) use ($query) {
            $titleRaw = $article->getAttribute('title');
            $contentRaw = $article->getAttribute('content');
            $excerptRaw = $article->getAttribute('excerpt');

            $title = strtolower(is_string($titleRaw) ? $titleRaw : '');
            $content = strtolower(is_string($contentRaw) ? $contentRaw : '');
            $excerpt = strtolower(is_string($excerptRaw) ? $excerptRaw : '');

            return str_contains($title, $query) ||
                   str_contains($content, $query) ||
                   str_contains($excerpt, $query);
        });
    }

    /**
     * Get all categories.
     *
     * @return array<int, string>
     */
    public static function getCategories(): array
    {
        $categories = [];
        foreach (static::published() as $article) {
            $category = $article->getAttribute('category');
            if (is_string($category) && !empty($category)) {
                $categories[] = $category;
            }
        }

        $categories = array_unique($categories);
        sort($categories);
        // @phpstan-ignore-next-line arrayValues.list
        return array_values($categories);
    }

    /**
     * Get all tags.
     *
     * @return array<int, string>
     */
    public static function getTags(): array
    {
        $allTags = [];
        foreach (static::published() as $article) {
            $tags = $article->getAttribute('tags');
            if (is_array($tags)) {
                foreach ($tags as $tag) {
                    if (is_string($tag) && !empty($tag)) {
                        $allTags[] = $tag;
                    }
                }
            }
        }

        $allTags = array_unique($allTags);
        sort($allTags);
        // @phpstan-ignore-next-line arrayValues.list
        return array_values($allTags);
    }

    /**
     * Calculate reading time based on content.
     */
    public function calculateReadingTime(): int
    {
        $contentRaw = $this->getAttribute('content');
        $content = is_string($contentRaw) ? $contentRaw : '';
        $wordCount = str_word_count(strip_tags($content));

        // Average reading speed: 200 words per minute
        return max(1, (int) ceil($wordCount / 200));
    }

    /**
     * Generate excerpt from content if not provided.
     */
    public function generateExcerpt(int $length = 160): string
    {
        $excerptRaw = $this->getAttribute('excerpt');
        if (!empty($excerptRaw) && is_string($excerptRaw)) {
            return $excerptRaw;
        }

        $contentRaw = $this->getAttribute('content');
        $content = strip_tags(is_string($contentRaw) ? $contentRaw : '');

        if (strlen($content) <= $length) {
            return $content;
        }

        return substr($content, 0, $length) . '...';
    }

    /**
     * Check if article is published.
     */
    public function isPublished(): bool
    {
        $publishedAt = $this->getAttribute('published_at');
        return $this->getAttribute('published') === true &&
               ($publishedAt === null || (is_string($publishedAt) && strtotime($publishedAt) <= time()));
    }

    /**
     * Get reading time in minutes.
     */
    public function getReadingTime(): int
    {
        $readingTime = $this->getAttribute('reading_time');
        if (is_int($readingTime)) {
            return $readingTime;
        }

        return $this->calculateReadingTime();
    }

    /**
     * Get URL-friendly slug.
     */
    public function getUrl(): string
    {
        $slug = $this->getAttribute('slug');
        $slugString = is_string($slug) ? $slug : '';
        return '/articles/' . $slugString;
    }

    /**
     * Save with automatic fields.
     */
    public function save(): bool
    {
        // Auto-calculate reading time
        if (empty($this->getAttribute('reading_time'))) {
            $this->setAttribute('reading_time', $this->calculateReadingTime());
        }

        // Set published_at if publishing for first time
        if ($this->getAttribute('published') && empty($this->getAttribute('published_at'))) {
            $this->setAttribute('published_at', date('Y-m-d H:i:s'));
        }

        return parent::save();
    }

    /**
     * Create new article with Orbit-style API.
     *
     * @param array<string, mixed> $attributes
     */
    public static function create(array $attributes): static
    {
        // @phpstan-ignore-next-line new.static
        $article = new static();

        // Set all attributes
        foreach ($attributes as $key => $value) {
            $article->setAttribute($key, $value);
        }

        // Auto-generate slug if not provided
        if (empty($attributes['slug']) && !empty($attributes['title'])) {
            $title = $attributes['title'];
            $titleString = is_string($title) ? $title : '';
            // @phpstan-ignore-next-line staticClassAccess.privateMethod
            $slug = static::generateSlug($titleString);
            $article->setAttribute('slug', $slug);
        }

        $article->save();
        return $article;
    }

    /**
     * Generate URL-friendly slug from title.
     */
    private static function generateSlug(string $title): string
    {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug) ?? $slug;
        $slug = preg_replace('/[\s-]+/', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }

    /**
     * Simple where clause for compatibility with Eloquent-style queries.
     *
     * @return ArticleQueryBuilder
     */
    public static function where(string $field, string $value): ArticleQueryBuilder
    {
        return new ArticleQueryBuilder($field, $value);
    }

    /**
     * Get articles directory path using Paths service.
     */
    public static function getArticlesPath(): string
    {
        $storageService = static::getStorageService();
        // @phpstan-ignore-next-line booleanAnd.leftAlwaysTrue
        if ($storageService && method_exists($storageService, 'getPaths')) {
            $paths = $storageService->getPaths();
            if (is_object($paths) && method_exists($paths, 'articles')) {
                $articlesPath = $paths->articles();
                return is_string($articlesPath) ? $articlesPath : '';
            }
        }

        // Fallback to storage directory
        // @phpstan-ignore-next-line ternary.alwaysTrue
        return $storageService ? $storageService->getStorageDirectory('articles') : '';
    }
}

/**
 * Simple query builder for Article model.
 */
class ArticleQueryBuilder
{
    public function __construct(
        private readonly string $field,
        private readonly string $value
    ) {
    }

    /**
     * Get first matching article.
     */
    public function first(): ?Article
    {
        $articles = Article::all();

        foreach ($articles as $article) {
            $fieldValue = $article->getAttribute($this->field);
            if (is_string($fieldValue) && $fieldValue === $this->value) {
                return $article;
            }
        }

        return null;
    }

    /**
     * Get all matching articles.
     *
     * @return array<int, Article>
     */
    public function get(): array
    {
        $articles = Article::all();
        $results = [];

        foreach ($articles as $article) {
            $fieldValue = $article->getAttribute($this->field);
            if (is_string($fieldValue) && $fieldValue === $this->value) {
                $results[] = $article;
            }
        }

        return $results;
    }
}
