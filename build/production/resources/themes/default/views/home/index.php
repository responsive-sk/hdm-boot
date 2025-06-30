<!DOCTYPE html>
<html lang="en" <?= $theme::getHtmlAttributes() ?>>
<head>
    <?= $theme::renderHead('HDM Boot - Modern PHP Framework') ?>
</head>
<body class="<?= $theme::getBodyClass() ?> home-page">
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="navbar-brand">
                    <a href="/" class="text-2xl font-bold text-gray-900 hover:text-primary-600">
                        🚀 HDM Boot
                    </a>
                </div>
                
                <div class="navbar-nav hidden md:flex">
                    <a href="/" class="nav-link active">Home</a>
                    <a href="/blog" class="nav-link">Blog</a>
                    <a href="/docs" class="nav-link">Docs</a>
                    <a href="/api" class="nav-link">API</a>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Search Toggle -->
                    <button
                        class="search-toggle p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-all duration-200 hidden md:block"
                        @click="toggleSearch()"
                        title="Search"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>

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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-background parallax-bg"></div>
        <div class="hero-content">
            <h1 class="hero-title animate-fade-in">
                HDM Boot
            </h1>
            <p class="hero-subtitle animate-fade-in">
                Modern PHP framework with <strong>Orbit-style content management</strong>, 
                powerful theme system, and cutting-edge web technologies.
            </p>
            <div class="hero-cta animate-fade-in">
                <a href="/blog" class="btn btn-primary btn-lg">
                    Explore Blog
                </a>
                <a href="/api" class="btn btn-outline btn-lg">
                    View API
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container mx-auto px-4">
            <div class="stats-grid">
                <?php foreach ($stats as $stat): ?>
                    <div class="stat-item scroll-fade-in">
                        <div class="stat-number counter" data-target="<?= htmlspecialchars($stat['number']) ?>">
                            <?= htmlspecialchars($stat['number']) ?>
                        </div>
                        <div class="stat-label"><?= htmlspecialchars($stat['label']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16 animate-fade-in">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                    Why Choose HDM Boot?
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Built for modern web development with developer experience and performance in mind.
                </p>
            </div>
            
            <div class="features-grid scroll-stagger">
                <?php foreach ($features as $feature): ?>
                    <div class="feature-card stagger-item group">
                        <div class="feature-icon">
                            <?= $feature['icon'] ?>
                        </div>
                        <h3 class="feature-title"><?= htmlspecialchars($feature['title']) ?></h3>
                        <p class="feature-description"><?= htmlspecialchars($feature['description']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Theme Showcase -->
    <section class="py-20 bg-gradient-to-br from-primary-50 to-secondary-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16 animate-fade-in">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                    Powerful Theme System
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Laravel-style theme structure with modern build tools and hot reload support.
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="scroll-slide-left">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-xl font-semibold">Current Theme: <?= htmlspecialchars($themeConfig['name']) ?></h3>
                        </div>
                        <div class="card-body">
                            <p class="text-gray-600 mb-4"><?= htmlspecialchars($themeConfig['description']) ?></p>
                            
                            <h4 class="font-semibold mb-3">Technology Stack:</h4>
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php foreach ($themeConfig['stack'] as $tech): ?>
                                    <span class="bg-primary-100 text-primary-700 px-3 py-1 rounded-full text-sm font-medium">
                                        <?= htmlspecialchars($tech) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            
                            <h4 class="font-semibold mb-3">Features:</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <?php foreach ($themeConfig['features'] as $feature => $enabled): ?>
                                    <?php if ($enabled): ?>
                                        <div class="flex items-center text-sm text-green-600">
                                            <span class="mr-2">✅</span>
                                            <?= ucwords(str_replace('_', ' ', $feature)) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="scroll-slide-right">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-xl font-semibold">Available Themes</h3>
                        </div>
                        <div class="card-body">
                            <div class="space-y-3">
                                <?php foreach ($availableThemes as $themeName): ?>
                                    <?php $isActive = $themeName === $theme::getActiveTheme(); ?>
                                    <div class="flex items-center justify-between p-3 rounded-lg <?= $isActive ? 'bg-primary-50 border border-primary-200' : 'bg-gray-50' ?>">
                                        <div class="flex items-center">
                                            <span class="mr-3"><?= $isActive ? '✅' : '📦' ?></span>
                                            <span class="font-medium"><?= ucfirst($themeName) ?></span>
                                            <?php if ($isActive): ?>
                                                <span class="ml-2 text-xs bg-primary-600 text-white px-2 py-1 rounded-full">Active</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!$isActive): ?>
                                            <button class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                                                Switch
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container mx-auto px-4">
            <div class="cta-content">
                <h2 class="cta-title">Ready to Build Something Amazing?</h2>
                <p class="cta-description">
                    Start exploring HDM Boot's features and see how it can accelerate your development workflow.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-6">
                    <a href="/blog" class="btn btn-primary btn-lg">
                        Read Documentation
                    </a>
                    <a href="/api" class="btn btn-outline btn-lg text-white border-white hover:bg-white hover:text-gray-900">
                        Explore API
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div class="md:col-span-2">
                    <h3 class="text-2xl font-bold mb-4">🚀 HDM Boot</h3>
                    <p class="text-gray-300 leading-relaxed max-w-md">
                        Modern PHP framework with Orbit-style content management, 
                        theme system, and cutting-edge web technologies.
                    </p>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="/" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="/blog" class="hover:text-white transition-colors">Blog</a></li>
                        <li><a href="/blog/about" class="hover:text-white transition-colors">About</a></li>
                        <li><a href="/api" class="hover:text-white transition-colors">API</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Technology</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li>PHP <?= PHP_VERSION ?></li>
                        <li>Slim Framework</li>
                        <li>Tailwind CSS</li>
                        <li>Alpine.js & GSAP</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 pt-8 text-center">
                <p class="text-gray-300">
                    &copy; 2024 HDM Boot. Built with ❤️ and modern web technologies.
                </p>
                <div class="flex items-center justify-center space-x-4 mt-4 text-sm text-gray-400">
                    <span class="bg-gray-800 px-3 py-1 rounded-full">
                        Theme: <?= $theme::getActiveTheme() ?>
                    </span>
                    <span class="bg-gray-800 px-3 py-1 rounded-full">
                        Version: 1.0.0
                    </span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
