<!DOCTYPE html>
<html lang="en" <?= $theme::getHtmlAttributes() ?>>
<head>
    <?= $theme::renderHead('Search Results - HDM Boot Blog') ?>
</head>
<body class="<?= $theme::getBodyClass() ?> blog-page search-page">
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
                    <a href="/blog" class="nav-link">Blog</a>
                    <a href="/blog/about" class="nav-link">About</a>
                    <a href="/api/blog/articles" class="nav-link">API</a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Dark Mode Toggle -->
                    <button 
                        class="dark-mode-toggle p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-all duration-200"
                        x-data="{ dark: false }"
                        @click="toggleDarkMode()"
                        :class="{ 'text-yellow-500': !dark, 'text-blue-400': dark }"
                        title="Toggle dark mode"
                    >
                        <svg x-show="!dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                        <svg x-show="dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main content -->
    <div class="page-content">
        <div class="container blog-container">
            <!-- Search Header -->
            <div class="search-header py-12 text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                    🔍 Search Results
                </h1>
                <?php if (!empty($query ?? '')): ?>
                    <p class="text-xl text-gray-600 mb-8">
                        <?php if (($totalResults ?? 0) > 0): ?>
                            Found <strong><?= $totalResults ?? 0 ?></strong> result<?= ($totalResults ?? 0) !== 1 ? 's' : '' ?> for
                            "<strong><?= htmlspecialchars($query ?? '') ?></strong>"
                        <?php else: ?>
                            No results found for "<strong><?= htmlspecialchars($query ?? '') ?></strong>"
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <p class="text-xl text-gray-600 mb-8">
                        Enter a search term to find articles
                    </p>
                <?php endif; ?>
                
                <!-- Search Form -->
                <div class="max-w-2xl mx-auto">
                    <form method="GET" action="/blog/search" class="search-form" x-data="{ query: '<?= htmlspecialchars($query ?? '') ?>' }">
                        <div class="relative">
                            <input
                                type="text"
                                name="q"
                                x-model="query"
                                value="<?= htmlspecialchars($query ?? '') ?>"
                                placeholder="Search articles..."
                                class="form-input w-full text-lg py-4 px-6 pr-16 rounded-xl shadow-lg border-2 border-gray-200 focus:border-primary-500"
                                autofocus
                            >
                            <button 
                                type="submit" 
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 p-2 text-gray-400 hover:text-primary-600 transition-colors"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="blog-layout">
                <div class="blog-main">
                    <?php if (!empty($query ?? '') && ($totalResults ?? 0) > 0): ?>
                        <!-- Search Results -->
                        <div class="search-results">
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
                                
                                <article class="search-result-item group">
                                    <div class="flex items-start space-x-4">
                                        <!-- Article Icon -->
                                        <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-primary-100 to-secondary-100 rounded-xl flex items-center justify-center">
                                            <span class="text-2xl">📝</span>
                                        </div>
                                        
                                        <!-- Article Content -->
                                        <div class="flex-1 min-w-0">
                                            <?php if ($category): ?>
                                                <span class="inline-block bg-primary-100 text-primary-700 px-3 py-1 rounded-full text-sm font-medium mb-2">
                                                    <?= $category ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <h2 class="search-result-title">
                                                <a href="/blog/article/<?= $slug ?>" class="text-gray-900 hover:text-primary-600 transition-colors duration-200">
                                                    <?= $title ?>
                                                </a>
                                            </h2>
                                            
                                            <div class="search-result-excerpt mb-3">
                                                <?= $excerpt ?>
                                            </div>
                                            
                                            <div class="flex items-center text-sm text-gray-500 space-x-4">
                                                <span>By <?= $author ?></span>
                                                <span>•</span>
                                                <span><?= $publishedAt ?></span>
                                                <span>•</span>
                                                <span><?= $readingTime ?> min read</span>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Search Tips -->
                        <div class="mt-12 p-6 bg-gray-50 rounded-xl">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">💡 Search Tips</h3>
                            <ul class="text-gray-600 space-y-1">
                                <li>• Use multiple words to narrow your search</li>
                                <li>• Search matches titles, content, categories, tags, and authors</li>
                                <li>• Results are ranked by relevance</li>
                            </ul>
                        </div>
                        
                    <?php elseif (!empty($query ?? '')): ?>
                        <!-- No Results -->
                        <div class="search-no-results">
                            <div class="text-center py-16">
                                <div class="text-6xl mb-6">🔍</div>
                                <h3 class="text-2xl font-semibold text-gray-900 mb-4">No articles found</h3>
                                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                                    We couldn't find any articles matching "<strong><?= htmlspecialchars($query ?? '') ?></strong>".
                                    Try different keywords or browse our categories below.
                                </p>
                                <a href="/blog" class="btn btn-primary">
                                    Browse All Articles
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Empty Search -->
                        <div class="search-no-results">
                            <div class="text-center py-16">
                                <div class="text-6xl mb-6">📚</div>
                                <h3 class="text-2xl font-semibold text-gray-900 mb-4">Start Your Search</h3>
                                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                                    Enter keywords above to search through our articles, or browse by category.
                                </p>
                                <a href="/blog" class="btn btn-primary">
                                    Browse All Articles
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div class="blog-sidebar">
                    <!-- Quick Search -->
                    <div class="sidebar-widget">
                        <h3 class="sidebar-widget-title">🚀 Quick Search</h3>
                        <div class="space-y-2">
                            <a href="/blog/search?q=hdm-boot" class="block text-primary-600 hover:text-primary-700">HDM Boot</a>
                            <a href="/blog/search?q=php" class="block text-primary-600 hover:text-primary-700">PHP</a>
                            <a href="/blog/search?q=theme" class="block text-primary-600 hover:text-primary-700">Themes</a>
                            <a href="/blog/search?q=api" class="block text-primary-600 hover:text-primary-700">API</a>
                        </div>
                    </div>
                    
                    <!-- Statistics Widget -->
                    <div class="sidebar-widget">
                        <h3 class="sidebar-widget-title">📊 Blog Statistics</h3>
                        <div class="space-y-2">
                            <p><strong><?= count($allArticles ?? []) ?></strong> total articles</p>
                            <p><strong><?= count($categories ?? []) ?></strong> categories</p>
                            <p><strong><?= count($tags ?? []) ?></strong> tags</p>
                        </div>
                    </div>
                    
                    <!-- Categories Widget -->
                    <div class="sidebar-widget">
                        <h3 class="sidebar-widget-title">📂 Categories</h3>
                        <div class="sidebar-widget-list">
                            <?php foreach ($categories ?? [] as $category): ?>
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
