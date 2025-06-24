<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Storage\Models;

/**
 * Documentation Model.
 *
 * Represents documentation pages stored as Markdown files.
 * Used for API docs, guides, and technical documentation.
 */
class Documentation extends FileModel
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
     * Define the schema for documentation.
     *
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        return [
            'title'          => 'string|required',
            'slug'           => 'string|required|unique',
            'category'       => 'string|required',
            'order'          => 'integer|default:0',
            'description'    => 'string|nullable',
            'tags'           => 'array|nullable',
            'version'        => 'string|nullable',
            'updated_at'     => 'datetime|nullable',
            'author'         => 'string|nullable',
            'difficulty'     => 'string|nullable', // beginner, intermediate, advanced
            'estimated_time' => 'string|nullable', // e.g., "5 minutes"
            'prerequisites'  => 'array|nullable',
            'related_docs'   => 'array|nullable',
            'content'        => 'text', // Markdown content
        ];
    }

    /**
     * Get documentation by category.
     *
     * @return array<int, static>
     */
    public static function byCategory(string $category): array
    {
        $docs = array_filter(static::all(), function (Documentation $doc) use ($category) {
            return $doc->getAttribute('category') === $category;
        });

        // Sort by order
        usort($docs, function (Documentation $a, Documentation $b) {
            $aOrderRaw = $a->getAttribute('order');
            $bOrderRaw = $b->getAttribute('order');
            $aOrder = is_numeric($aOrderRaw) ? (int) $aOrderRaw : 0;
            $bOrder = is_numeric($bOrderRaw) ? (int) $bOrderRaw : 0;

            return $aOrder <=> $bOrder;
        });

        return $docs;
    }

    /**
     * Get all categories with their documentation.
     *
     * @return array<string, array<int, static>>
     */
    public static function groupedByCategory(): array
    {
        $grouped = [];

        foreach (static::all() as $doc) {
            $category = $doc->getAttribute('category') ?? 'uncategorized';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $doc;
        }

        // Sort each category by order
        foreach ($grouped as $category => $docs) {
            usort($docs, function (Documentation $a, Documentation $b) {
                $aOrderRaw = $a->getAttribute('order');
                $bOrderRaw = $b->getAttribute('order');
                $aOrder = is_numeric($aOrderRaw) ? (int) $aOrderRaw : 0;
                $bOrder = is_numeric($bOrderRaw) ? (int) $bOrderRaw : 0;

                return $aOrder <=> $bOrder;
            });
            $grouped[$category] = $docs;
        }

        // Sort categories by key
        ksort($grouped);

        // @phpstan-ignore-next-line return.type
        return $grouped;
    }

    /**
     * Get documentation by difficulty level.
     *
     * @return array<int, static>
     */
    public static function byDifficulty(string $difficulty): array
    {
        return array_filter(static::all(), function (Documentation $doc) use ($difficulty) {
            return $doc->getAttribute('difficulty') === $difficulty;
        });
    }

    /**
     * Search documentation.
     *
     * @return array<int, static>
     */
    public static function search(string $query): array
    {
        $query = strtolower($query);

        return array_filter(static::all(), function (Documentation $doc) use ($query) {
            $titleRaw = $doc->getAttribute('title');
            $contentRaw = $doc->getAttribute('content');
            $descriptionRaw = $doc->getAttribute('description');
            $categoryRaw = $doc->getAttribute('category');

            $title = strtolower(is_string($titleRaw) ? $titleRaw : '');
            $content = strtolower(is_string($contentRaw) ? $contentRaw : '');
            $description = strtolower(is_string($descriptionRaw) ? $descriptionRaw : '');
            $category = strtolower(is_string($categoryRaw) ? $categoryRaw : '');

            return str_contains($title, $query) ||
                   str_contains($content, $query) ||
                   str_contains($description, $query) ||
                   str_contains($category, $query);
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
        foreach (static::all() as $doc) {
            $category = $doc->getAttribute('category');
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
        foreach (static::all() as $doc) {
            $tags = $doc->getAttribute('tags');
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
     * Get navigation structure for documentation.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function getNavigation(): array
    {
        $navigation = [];

        $grouped = static::groupedByCategory();

        foreach ($grouped as $category => $docs) {
            $categoryDocs = [];
            foreach ($docs as $doc) {
                $categoryDocs[] = [
                    'title'       => $doc->getAttribute('title'),
                    'slug'        => $doc->getAttribute('slug'),
                    'url'         => $doc->getUrl(),
                    'description' => $doc->getAttribute('description'),
                    'difficulty'  => $doc->getAttribute('difficulty'),
                ];
            }
            $navigation[$category] = $categoryDocs;
        }

        return $navigation;
    }

    /**
     * Get related documentation.
     *
     * @return array<int, static>
     */
    public function getRelated(): array
    {
        $relatedSlugs = $this->getAttribute('related_docs') ?? [];

        if (empty($relatedSlugs)) {
            // Auto-suggest based on category and tags
            $category = $this->getAttribute('category');
            $categoryStr = is_string($category) ? $category : '';
            $categoryDocs = static::byCategory($categoryStr);
            $filtered = array_filter($categoryDocs, function (Documentation $doc) {
                return $doc->getAttribute('slug') !== $this->getAttribute('slug');
            });

            return array_slice($filtered, 0, 3);
        }

        return array_filter(static::all(), function (Documentation $doc) use ($relatedSlugs) {
            return is_array($relatedSlugs) && in_array($doc->getAttribute('slug'), $relatedSlugs);
        });
    }

    /**
     * Get previous documentation in same category.
     */
    public function getPrevious(): ?static
    {
        $category = $this->getAttribute('category');
        $categoryStr = is_string($category) ? $category : '';
        $docs = static::byCategory($categoryStr);
        $currentIndex = null;

        foreach ($docs as $index => $doc) {
            if ($doc->getAttribute('slug') === $this->getAttribute('slug')) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === null || $currentIndex === 0) {
            return null;
        }

        return $docs[$currentIndex - 1] ?? null;
    }

    /**
     * Get next documentation in same category.
     */
    public function getNext(): ?static
    {
        $category = $this->getAttribute('category');
        $categoryStr = is_string($category) ? $category : '';
        $docs = static::byCategory($categoryStr);
        $currentIndex = null;

        foreach ($docs as $index => $doc) {
            if ($doc->getAttribute('slug') === $this->getAttribute('slug')) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === null) {
            return null;
        }

        return $docs[$currentIndex + 1] ?? null;
    }

    /**
     * Get URL for this documentation.
     */
    public function getUrl(): string
    {
        $slug = $this->getAttribute('slug');
        $slugString = is_string($slug) ? $slug : '';

        return '/docs/' . $slugString;
    }

    /**
     * Get breadcrumb navigation.
     *
     * @return array<int, array<string, string>>
     */
    public function getBreadcrumbs(): array
    {
        $categoryRaw = $this->getAttribute('category');
        $titleRaw = $this->getAttribute('title');

        $category = is_string($categoryRaw) ? $categoryRaw : '';
        $title = is_string($titleRaw) ? $titleRaw : '';

        return [
            ['title' => 'Documentation', 'url' => '/docs'],
            ['title' => ucfirst($category), 'url' => '/docs/category/' . $category],
            ['title' => $title, 'url' => $this->getUrl()],
        ];
    }

    /**
     * Save with automatic fields.
     */
    public function save(): bool
    {
        // Set updated_at
        $this->setAttribute('updated_at', date('Y-m-d H:i:s'));

        return parent::save();
    }
}
