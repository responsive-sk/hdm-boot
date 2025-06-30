<!DOCTYPE html>
<html lang="en" <?= $theme::getHtmlAttributes() ?>>
<head>
    <?= $theme::renderHead($title ?? 'HDM Boot') ?>
</head>
<body class="<?= $theme::getBodyClass() ?> <?= $bodyClass ?? '' ?>">
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

                    <!-- Mobile Menu Toggle -->
                    <button class="navbar-toggler md:hidden">
                    <span class="sr-only">Open menu</span>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>

            <!-- Mobile menu -->
            <div class="mobile-menu hidden">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="/" class="nav-link block">Home</a>
                    <a href="/blog" class="nav-link block">Blog</a>
                    <a href="/blog/about" class="nav-link block">About</a>
                    <a href="/api/blog/articles" class="nav-link block">API</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main content -->
    <main>
        <?= $content ?? '' ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-16 mt-24">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <div class="mb-8">
                    <h3 class="text-2xl font-bold mb-4">🚀 HDM Boot</h3>
                    <p class="text-gray-300 max-w-2xl mx-auto leading-relaxed">
                        Modern PHP framework with Orbit-style content management,
                        theme system, and cutting-edge web technologies.
                    </p>
                </div>

                <div class="border-t border-gray-700 pt-8">
                    <p class="text-gray-300 mb-2">
                        &copy; 2024 HDM Boot. Built with ❤️ and modern web technologies.
                    </p>
                    <div class="flex items-center justify-center space-x-4 text-sm text-gray-400">
                        <span class="bg-gray-800 px-3 py-1 rounded-full">
                            Theme: <?= $theme::getActiveTheme() ?>
                        </span>
                        <span class="bg-gray-800 px-3 py-1 rounded-full">
                            Stack: <?= implode(', ', array_slice($theme::getThemeStack(), 0, 2)) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Theme JavaScript is automatically included -->
</body>
</html>
