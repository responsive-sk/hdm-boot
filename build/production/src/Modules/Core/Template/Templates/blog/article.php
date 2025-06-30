<?php
// Type guard for template variables
$article ??= null;

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
<?php if (!$article) : ?>
    <div class="article">
        <h2>Article Not Found</h2>
        <p>The requested article could not be found.</p>
        <a href="/blog" class="back-link">← Back to Blog</a>
    </div>
<?php else : ?>
    <a href="/blog" class="back-link">← Back to Blog</a>

    <article class="article">
        <h1><?= htmlspecialchars(safeGetAttribute($article, 'title', 'Untitled')); ?></h1>

        <div class="meta">
            By <?= htmlspecialchars(safeGetAttribute($article, 'author', 'Unknown')); ?>
            • <?php
                $publishedAt = safeGetAttribute($article, 'published_at', 'now');
    $timestamp = strtotime($publishedAt);
    echo date('F j, Y', $timestamp !== false ? $timestamp : time());
    ?>
            • <?= safeGetNumericAttribute($article, 'reading_time', 1); ?> min read
        </div>
        
        <div class="content">
            <?php
    // Simple markdown to HTML conversion with safe content handling
    $content = safeGetAttribute($article, 'content', '');

    $content = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $content) ?? $content;
    $content = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $content) ?? $content;
    $content = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $content) ?? $content;
    $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content) ?? $content;
    $content = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $content) ?? $content;
    $content = preg_replace('/`(.+?)`/', '<code>$1</code>', $content) ?? $content;
    $content = nl2br($content);
    echo $content;
    ?>
        </div>
        
        <?php
        // @phpstan-ignore-next-line booleanAnd.leftAlwaysTrue
        $tags = $article && is_object($article) && method_exists($article, 'getAttribute')
        ? $article->getAttribute('tags')
        : null;
    if (is_array($tags)) : ?>
            <div class="tags">
                    <?php foreach ($tags as $tag) : ?>
                        <?php if (is_string($tag)) : ?>
                        <span class="tag"><?= htmlspecialchars($tag); ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
<?php endif; ?>
