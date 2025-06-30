<?php
/**
 * Blog Article Template
 * 
 * Variables available:
 * - $article: Article model instance
 * - $htmlContent: Processed markdown content
 */

if (!$article) {
    $title = 'Article Not Found - HDM Boot Blog';
    $bodyClass = 'article-page';
    
    ob_start();
    ?>
    <div class="page-content">
        <div class="container max-w-4xl mx-auto px-4 py-12">
            <div class="text-center animate-fade-in">
                <h1 class="text-4xl font-bold text-gray-900 mb-6">Article Not Found</h1>
                <p class="text-gray-600 mb-8">The article you're looking for doesn't exist.</p>
                <a href="/blog" class="btn btn-primary">← Back to Blog</a>
            </div>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    $viewRenderer = new \HdmBoot\SharedKernel\Services\ViewRenderer();
    echo $viewRenderer->renderWithLayout('layouts.app', $content, compact('title', 'bodyClass'));
    return;
}

$title = htmlspecialchars($article->getAttribute('title') ?? 'Untitled') . ' - HDM Boot Blog';
$articleTitle = htmlspecialchars($article->getAttribute('title') ?? 'Untitled');
$author = htmlspecialchars($article->getAttribute('author') ?? 'Unknown');
$publishedAt = htmlspecialchars($article->getAttribute('published_at') ?? '');
$readingTime = is_numeric($article->getAttribute('reading_time')) ? (int) $article->getAttribute('reading_time') : 1;
$category = htmlspecialchars($article->getAttribute('category') ?? '');
$tags = $article->getAttribute('tags') ?? [];
$bodyClass = 'article-page';

ob_start();
?>

<div class="page-content">
    <div class="article-header animate-fade-in">
        <div class="article-header-content">
        <?php if ($category): ?>
            <span class="article-category"><?= $category ?></span>
        <?php endif; ?>
        
        <h1 class="article-title"><?= $articleTitle ?></h1>
        
        <div class="article-meta">
            <div class="article-author">
                <div class="article-author-avatar">
                    <?= strtoupper(substr($author, 0, 1)) ?>
                </div>
                <span class="font-semibold"><?= $author ?></span>
            </div>
            <div class="text-gray-600 font-medium">
                <?= $publishedAt ?> • <?= $readingTime ?> min read
            </div>
        </div>
    </div>
</div>

<div class="article-content">
    <div class="mb-8">
        <a href="/blog" class="text-primary-600 hover:text-primary-700 font-medium">← Back to Blog</a>
    </div>
    
    <article class="article-body prose-custom">
        <?= $htmlContent ?>
    </article>
    
    <?php if (!empty($tags)): ?>
        <div class="article-tags">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Tags:</h4>
            <div class="tag-list">
                <?php foreach ($tags as $tag): ?>
                    <?php $tagEscaped = htmlspecialchars($tag); ?>
                    <a href="/blog/tags/<?= urlencode($tag) ?>" class="tag"><?= $tagEscaped ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Article Navigation -->
    <div class="article-navigation">
        <div class="article-nav-item">
            <a href="/blog" class="article-nav-link">
                <div class="article-nav-label">← Back to</div>
                <div class="article-nav-title">All Articles</div>
            </a>
        </div>
        <div class="article-nav-item text-right">
            <a href="/blog/about" class="article-nav-link">
                <div class="article-nav-label">Learn more →</div>
                <div class="article-nav-title">About This Blog</div>
            </a>
        </div>
    </div>
</div>

<!-- Reading progress bar -->
<div class="reading-progress fixed top-0 left-0 w-full h-1 bg-primary-600 transform scale-x-0 origin-left z-50"></div>

<?php
$content = ob_get_clean();
$viewRenderer = new \HdmBoot\SharedKernel\Services\ViewRenderer();
echo $viewRenderer->renderWithLayout('layouts.app', $content, compact('title', 'bodyClass'));
?>
