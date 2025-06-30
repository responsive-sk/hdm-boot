<!DOCTYPE html>
<html lang="en" <?= $theme::getHtmlAttributes() ?>>
<head>
    <?= $theme::renderHead(ucfirst($category) . ' - HDM Boot Blog') ?>
</head>
<body class="<?= $theme::getBodyClass() ?> blog-page category-page">
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
            <!-- Category Header -->
            <div class="category-header py-12 text-center bg-gradient-to-br from-primary-50 via-white to-secondary-50">
                <div class="category-icon text-6xl mb-4">📂</div>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                    <?= ucfirst(htmlspecialchars($category)) ?>
                </h1>
                <p class="text-xl text-gray-600 mb-8">
                    <?php if ($totalResults > 0): ?>
                        <?= $totalResults ?> article<?= $totalResults !== 1 ? 's' : '' ?> in this category
                    <?php else: ?>
                        No articles found in this category
                    <?php endif; ?>
                </p>
                
                <!-- Breadcrumb -->
                <nav class="flex items-center justify-center space-x-2 text-sm text-gray-500">
                    <a href="/blog" class="hover:text-primary-600">Blog</a>
                    <span>→</span>
                    <a href="/blog/categories" class="hover:text-primary-600">Categories</a>
                    <span>→</span>
                    <span class="text-gray-900 font-medium"><?= ucfirst(htmlspecialchars($category)) ?></span>
                </nav>
            </div>
            
            <div class="blog-layout">
                <div class="blog-main">
                    <?php if ($totalResults > 0): ?>
                        <!-- Category Articles -->
                        <div class="category-articles">
                            <h2 class="text-2xl font-bold text-gray-900 mb-6">Articles in <?= ucfirst(htmlspecialchars($category)) ?></h2>
                            <div class="blog-listing scroll-stagger">
                                <?php foreach ($articles as $article): ?>
                                    <?php
                                    $title = htmlspecialchars($article->getAttribute('title') ?? 'Untitled');
                                    $author = htmlspecialchars($article->getAttribute('author') ?? 'Unknown');
                                    $slug = htmlspecialchars($article->getAttribute('slug') ?? '');
                                    $excerpt = htmlspecialchars($article->getAttribute('excerpt') ?? '');
                                    $publishedAt = htmlspecialchars($article->getAttribute('published_at') ?? '');
                                    $readingTime = is_numeric($article->getAttribute('reading_time')) ? (int) $article->getAttribute('reading_time') : 1;
                                    $articleCategory = htmlspecialchars($article->getAttribute('category') ?? '');
                                    ?>
                                    
                                    <article class="blog-card stagger-item group">
                                        <!-- Article Image Placeholder -->
                                        <div class="blog-card-image">
                                            <div class="absolute inset-0 flex items-center justify-center">
                                                <div class="text-6xl opacity-20">📝</div>
                                            </div>
                                        </div>
                                        
                                        <div class="blog-card-content">
                                            <?php if ($articleCategory): ?>
                                                <span class="blog-post-category"><?= $articleCategory ?></span>
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
                        </div>
                        
                    <?php else: ?>
                        <!-- No Articles -->
                        <div class="category-no-results">
                            <div class="text-center py-16">
                                <div class="text-6xl mb-6">📂</div>
                                <h3 class="text-2xl font-semibold text-gray-900 mb-4">No articles in this category</h3>
                                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                                    We haven't published any articles in the "<?= htmlspecialchars($category) ?>" category yet. 
                                    Check back later or browse other categories.
                                </p>
                                <div class="space-x-4">
                                    <a href="/blog" class="btn btn-primary">
                                        Browse All Articles
                                    </a>
                                    <a href="/blog/search" class="btn btn-outline">
                                        Search Articles
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div class="blog-sidebar">
                    <!-- Category Info -->
                    <div class="sidebar-widget">
                        <h3 class="sidebar-widget-title">📂 Category Info</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Category:</span>
                                <span class="font-semibold text-primary-600"><?= ucfirst(htmlspecialchars($category)) ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Articles:</span>
                                <span class="font-semibold"><?= $totalResults ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- All Categories -->
                    <div class="sidebar-widget">
                        <h3 class="sidebar-widget-title">📂 All Categories</h3>
                        <div class="sidebar-widget-list">
                            <?php foreach ($categories ?? [] as $cat): ?>
                                <?php
                                $catEscaped = htmlspecialchars($cat);
                                $count = count($categoryCounts[$cat] ?? []);
                                $isActive = $cat === $category;
                                ?>
                                <div class="sidebar-widget-item <?= $isActive ? 'bg-primary-50 border border-primary-200 rounded-lg' : '' ?>">
                                    <a href="/blog/categories/<?= urlencode($cat) ?>" 
                                       class="sidebar-widget-link <?= $isActive ? 'text-primary-700 font-semibold' : '' ?>">
                                        <?= $catEscaped ?>
                                    </a>
                                    <span class="sidebar-widget-count"><?= $count ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Tags -->
                    <div class="sidebar-widget">
                        <h3 class="sidebar-widget-title">🏷️ Popular Tags</h3>
                        <div class="tag-list">
                            <?php foreach (array_slice($tags ?? [], 0, 10) as $tag): ?>
                                <?php $tagEscaped = htmlspecialchars($tag); ?>
                                <a href="/blog/tags/<?= urlencode($tag) ?>" class="tag"><?= $tagEscaped ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="sidebar-widget">
                        <h3 class="sidebar-widget-title">🔗 Quick Links</h3>
                        <div class="sidebar-widget-list">
                            <div class="sidebar-widget-item">
                                <a href="/blog" class="sidebar-widget-link">All Articles</a>
                            </div>
                            <div class="sidebar-widget-item">
                                <a href="/blog/search" class="sidebar-widget-link">Search Articles</a>
                            </div>
                            <div class="sidebar-widget-item">
                                <a href="/blog/about" class="sidebar-widget-link">About This Blog</a>
                            </div>
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
