<?php
/**
 * Blog Home Template
 *
 * Variables available:
 * - $articles: array of published articles
 * - $allArticles: array of all articles
 * - $categories: array of categories
 * - $tags: array of tags
 * - $categoryCounts: array of category counts
 */
?>
<!DOCTYPE html>
<html lang="en" <?= $theme::getHtmlAttributes() ?>>
<head>
    <?= $theme::renderHead('HDM Boot Blog') ?>
</head>
<body class="<?= $theme::getBodyClass() ?> blog-page">
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="navbar-brand">
                    <a href="/" class="text-xl font-bold text-gray-900 hover:text-primary-600">
                        🚀 HDM Boot
                    </a>
                </div>

                <div class="navbar-nav hidden md:flex">
                    <a href="/" class="nav-link">Home</a>
                    <a href="/blog" class="nav-link active">Blog</a>
                    <a href="/blog/about" class="nav-link">About</a>
                    <a href="/api/blog/articles" class="nav-link">API</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main content -->

    <div class="page-content">
        <div class="container blog-container">
            <div class="blog-header animate-fade-in">
                <h1>🚀 HDM Boot Blog</h1>
                <p>Orbit-style Content Management System</p>
            </div>

            <div class="blog-layout">
                <div class="blog-main">
            <?php if (empty($articles)): ?>
                <div class="card blog-card animate-scale-in">
                    <div class="card-body">
                        <h2 class="blog-card-title">Welcome to HDM Boot Blog!</h2>
                        <div class="blog-card-excerpt">
                            <p>No articles found. This demonstrates our <strong>Orbit-style content management system</strong>.</p>
                            <p>Articles are stored as Markdown files with YAML front-matter in <code>content/articles/</code>.</p>
                            <p>Try creating an article file to see it appear here automatically!</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <h2 class="text-2xl font-bold text-gray-900 mb-6 animate-fade-in">Latest Articles</h2>
                <div class="blog-listing scroll-stagger">
                    <?php foreach ($articles as $article): ?>
                        <?php
                        $title = htmlspecialchars($article->getAttribute('title') ?? 'Untitled');
                        $author = htmlspecialchars($article->getAttribute('author') ?? 'Unknown');
                        $slug = htmlspecialchars($article->getAttribute('slug') ?? '');
                        $excerpt = htmlspecialchars($article->getAttribute('excerpt') ?? '');
                        $publishedAt = htmlspecialchars($article->getAttribute('published_at') ?? '');
                        $readingTime = is_numeric($article->getAttribute('reading_time')) ? (int) $article->getAttribute('reading_time') : 1;
                        $category = htmlspecialchars($article->getAttribute('category') ?? '');
                        ?>
                        
                        <article class="blog-card stagger-item group">
                            <!-- Article Image Placeholder -->
                            <div class="blog-card-image">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="text-6xl opacity-20">📝</div>
                                </div>
                            </div>

                            <div class="blog-card-content">
                                <?php if ($category): ?>
                                    <span class="blog-post-category"><?= $category ?></span>
                                <?php endif; ?>

                                <h2 class="blog-card-title">
                                    <a href="/blog/article/<?= $slug ?>"><?= $title ?></a>
                                </h2>

                                <div class="blog-card-excerpt"><?= $excerpt ?></div>

                                <div class="blog-card-meta">
                                    <div class="blog-card-author">
                                        <div class="blog-card-avatar">
                                            <?= strtoupper(substr($author, 0, 1)) ?>
                                        </div>
                                        <span class="font-semibold"><?= $author ?></span>
                                    </div>
                                    <div class="blog-card-date">
                                        <?= $publishedAt ?> • <?= $readingTime ?> min read
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="blog-sidebar">
            <!-- Search Widget -->
            <div class="sidebar-widget scroll-scale-in">
                <h3 class="sidebar-widget-title">🔍 Search Articles</h3>
                <form class="search-form" x-data="{ query: '' }" @submit.prevent="searchArticles()">
                    <div class="relative">
                        <input
                            type="text"
                            x-model="query"
                            placeholder="Search articles..."
                            class="form-input w-full pr-10 blog-search"
                            @input="liveSearch()"
                        >
                        <button
                            type="submit"
                            class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-primary-600 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-3 text-sm text-gray-500">
                        <span x-show="query.length > 0" x-text="`Searching for: ${query}`"></span>
                        <span x-show="query.length === 0">Type to search articles...</span>
                    </div>
                </form>
            </div>

            <!-- Statistics Widget -->
            <div class="sidebar-widget scroll-scale-in">
                <h3 class="sidebar-widget-title">📊 Blog Statistics</h3>
                <div class="space-y-2">
                    <p><strong><?= count($allArticles ?? []) ?></strong> total articles</p>
                    <p><strong><?= count($articles ?? []) ?></strong> published</p>
                    <p><strong><?= count($categories ?? []) ?></strong> categories</p>
                    <p><strong><?= count($tags ?? []) ?></strong> tags</p>
                </div>
            </div>
            
            <!-- Categories Widget -->
            <div class="sidebar-widget scroll-scale-in">
                <h3 class="sidebar-widget-title">📂 Categories</h3>
                <div class="sidebar-widget-list">
                    <?php foreach ($categories as $category): ?>
                        <?php
                        $categoryEscaped = htmlspecialchars($category);
                        $count = count($categoryCounts[$category] ?? []);
                        ?>
                        <div class="sidebar-widget-item">
                            <a href="/blog/categories/<?= urlencode($category) ?>" class="sidebar-widget-link">
                                <?= $categoryEscaped ?>
                            </a>
                            <span class="sidebar-widget-count"><?= $count ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Tags Widget -->
            <div class="sidebar-widget scroll-scale-in">
                <h3 class="sidebar-widget-title">🏷️ Tags</h3>
                <div class="tag-list">
                    <?php foreach ($tags as $tag): ?>
                        <?php $tagEscaped = htmlspecialchars($tag); ?>
                        <a href="/blog/tags/<?= urlencode($tag) ?>" class="tag"><?= $tagEscaped ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Quick Links Widget -->
            <div class="sidebar-widget scroll-scale-in">
                <h3 class="sidebar-widget-title">🔗 Quick Links</h3>
                <div class="sidebar-widget-list">
                    <div class="sidebar-widget-item">
                        <a href="/blog/about" class="sidebar-widget-link">About This Blog</a>
                    </div>
                    <div class="sidebar-widget-item">
                        <a href="/api/blog/articles" class="sidebar-widget-link">Articles API</a>
                    </div>
                    <div class="sidebar-widget-item">
                        <a href="/" class="sidebar-widget-link">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-16 mt-24">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2024 HDM Boot. Built with ❤️ and modern web technologies.</p>
        </div>
    </footer>
</body>
</html>
