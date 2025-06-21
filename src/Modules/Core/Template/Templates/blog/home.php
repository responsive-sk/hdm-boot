<?php
// Stats with safe counting
$allArticlesArray = isset($allArticles) && is_countable($allArticles) ? $allArticles : [];
$articlesArray = isset($articles) && is_countable($articles) ? $articles : [];
$categoriesArray = isset($categoriesList) && is_countable($categoriesList) ? $categoriesList : [];
$tagsArray = isset($tagsList) && is_countable($tagsList) ? $tagsList : [];

$totalArticles = count($allArticlesArray);
$publishedCount = count($articlesArray);
$categories = $categoriesArray;
$tags = $tagsArray;
?>

<div class="stats">
    <div class="stat">
        <div class="stat-number"><?= $totalArticles ?></div>
        <div class="stat-label">Total Articles</div>
    </div>
    <div class="stat">
        <div class="stat-number"><?= $publishedCount ?></div>
        <div class="stat-label">Published</div>
    </div>
    <div class="stat">
        <?php // @phpstan-ignore-next-line function.alreadyNarrowedType ?>
        <div class="stat-number"><?= is_countable($categories) ? count($categories) : 0 ?></div>
        <div class="stat-label">Categories</div>
    </div>
    <div class="stat">
        <?php // @phpstan-ignore-next-line function.alreadyNarrowedType ?>
        <div class="stat-number"><?= is_countable($tags) ? count($tags) : 0 ?></div>
        <div class="stat-label">Tags</div>
    </div>
</div>

<?php if (empty($articles)) : ?>
    <div class="article">
        <h2>Welcome to MVA Bootstrap Blog!</h2>
        <p>No articles found. Create your first article by adding a Markdown file to the <code>content/articles/</code> directory.</p>
        <h3>Example Article:</h3>
        <pre>---
title: "My First Post"
slug: "my-first-post"
author: "Your Name"
published: true
category: "general"
tags: ["hello", "world"]
---

# My First Post

Hello world! This is my first article.</pre>
    </div>
<?php else : ?>
    <h2>Latest Articles</h2>
    
    <?php
    // Helper function for safe attribute access
    function safeGetAttribute(mixed $obj, string $key, string $default = ''): string
    {
        if (!is_object($obj) || !method_exists($obj, 'getAttribute')) {
            return $default;
        }
        $value = $obj->getAttribute($key);
        return is_string($value) ? $value : $default;
    }

    // Helper function for safe numeric attribute access
    function safeGetNumericAttribute(mixed $obj, string $key, int $default = 0): int
    {
        if (!is_object($obj) || !method_exists($obj, 'getAttribute')) {
            return $default;
        }
        $value = $obj->getAttribute($key);
        return is_numeric($value) ? (int) $value : $default;
    }
    ?>

    <?php
    $articlesIterable = is_iterable($articles) ? $articles : [];
    foreach ($articlesIterable as $article) : ?>
        <article class="article">
            <h2>
                <a href="/blog/article/<?= urlencode(safeGetAttribute($article, 'slug')) ?>">
                    <?= htmlspecialchars(safeGetAttribute($article, 'title', 'Untitled')) ?>
                </a>
            </h2>

            <div class="meta">
                By <?= htmlspecialchars(safeGetAttribute($article, 'author', 'Unknown')) ?>
                • <?php
                    $publishedAt = safeGetAttribute($article, 'published_at', 'now');
                    $timestamp = strtotime($publishedAt);
                    echo date('F j, Y', $timestamp !== false ? $timestamp : time());
                ?>
                • <?= safeGetNumericAttribute($article, 'reading_time', 1) ?> min read
                <?php $category = safeGetAttribute($article, 'category'); ?>
                <?php if ($category) : ?>
                    • <?= ucfirst($category) ?>
                <?php endif; ?>
            </div>

            <?php $excerpt = safeGetAttribute($article, 'excerpt'); ?>
            <?php if ($excerpt) : ?>
                <div class="excerpt"><?= htmlspecialchars($excerpt) ?></div>
            <?php endif; ?>

            <a href="/blog/article/<?= urlencode(safeGetAttribute($article, 'slug')) ?>">Read more →</a>

            <?php
            $articleTags = is_object($article) && method_exists($article, 'getAttribute')
                ? $article->getAttribute('tags')
                : null;
            if (is_array($articleTags)) : ?>
                <div class="tags">
                    <?php foreach ($articleTags as $tag) : ?>
                        <?php if (is_string($tag)) : ?>
                            <span class="tag"><?= htmlspecialchars($tag) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    
    <?php if (!empty($categories) || !empty($tags)) : ?>
        <div class="sidebar">
            <?php if (!empty($categories)) : ?>
                <h3>Categories</h3>
                <ul>
                    <?php
                    $categoriesIterable = is_iterable($categories) ? $categories : [];
                    foreach ($categoriesIterable as $category) : ?>
                        <?php if (is_string($category)) : ?>
                            <?php
                            $categoryCounts = $categoryCounts ?? [];
                            $categoryData = is_array($categoryCounts) && isset($categoryCounts[$category])
                                ? $categoryCounts[$category]
                                : [];
                            $count = is_array($categoryData) ? count($categoryData) : 0;
                            ?>
                            <li>
                                <a href="/blog/category/<?= urlencode($category) ?>">
                                    <?= ucfirst($category) ?> (<?= $count ?>)
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <?php if (!empty($tags)) : ?>
                <h3>Popular Tags</h3>
                <div class="tags">
                    <?php
                    $tagsArray = is_array($tags) ? array_slice($tags, 0, 10) : [];
                    foreach ($tagsArray as $tag) : ?>
                        <?php if (is_string($tag)) : ?>
                            <span class="tag"><?= htmlspecialchars($tag) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
