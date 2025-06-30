<!DOCTYPE html>
<html lang="en" <?= $theme::getHtmlAttributes() ?>>
<head>
    <?= $theme::renderHead($title ?? 'HDM Boot Documentation') ?>
</head>
<body class="<?= $theme::getBodyClass() ?> docs-page">
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
                    <a href="/docs" class="nav-link active">Docs</a>
                    <a href="/api" class="nav-link">API</a>
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
        <div class="container docs-container">
            <!-- Docs Header -->
            <div class="docs-header py-12 text-center bg-gradient-to-br from-blue-50 via-white to-indigo-50">
                <div class="docs-icon text-6xl mb-4">📚</div>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                    HDM Boot Documentation
                </h1>
                <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                    Complete documentation for HDM Boot framework - architecture, guides, API references, and more.
                </p>
            </div>
            
            <div class="docs-layout">
                <div class="docs-main">
                    <?php if (!empty($readmeContent)): ?>
                        <!-- Main README Content -->
                        <div class="docs-content">
                            <?= $readmeContent ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Quick Start Section -->
                    <div class="docs-section">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">🚀 Quick Start</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="docs-card group">
                                <div class="docs-card-icon">🏗️</div>
                                <h3 class="docs-card-title">Architecture</h3>
                                <p class="docs-card-description">Learn about HDM Boot's hexagonal architecture and design principles.</p>
                                <a href="/docs/architecture/README.md" class="docs-card-link">Read More →</a>
                            </div>

                            <div class="docs-card group">
                                <div class="docs-card-icon">📖</div>
                                <h3 class="docs-card-title">Guides</h3>
                                <p class="docs-card-description">Step-by-step guides for development, deployment, and best practices.</p>
                                <a href="/docs/guides/README.md" class="docs-card-link">Read More →</a>
                            </div>

                            <div class="docs-card group">
                                <div class="docs-card-icon">🔌</div>
                                <h3 class="docs-card-title">API Reference</h3>
                                <p class="docs-card-description">Complete API documentation with examples and endpoints.</p>
                                <a href="/docs/api/README.md" class="docs-card-link">Read More →</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Featured Documentation -->
                    <div class="docs-section">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">📋 Featured Documentation</h2>
                        <div class="docs-featured">
                            <div class="docs-featured-item">
                                <h3><a href="/docs/ORBIT_QUICK_START.md">Orbit CMS Quick Start</a></h3>
                                <p>Get started with the Orbit-style content management system.</p>
                            </div>
                            
                            <div class="docs-featured-item">
                                <h3><a href="/docs/DEPLOYMENT_GUIDE.md">Deployment Guide</a></h3>
                                <p>Complete guide for deploying HDM Boot to production.</p>
                            </div>
                            
                            <div class="docs-featured-item">
                                <h3><a href="/docs/SECURITY.md">Security Guide</a></h3>
                                <p>Security best practices and implementation details.</p>
                            </div>
                            
                            <div class="docs-featured-item">
                                <h3><a href="/docs/MODULES.md">Module Development</a></h3>
                                <p>Learn how to create and manage modules in HDM Boot.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="docs-sidebar">
                    <!-- Search -->
                    <div class="docs-widget">
                        <h3 class="docs-widget-title">🔍 Search Docs</h3>
                        <form class="docs-search-form" x-data="{ query: '' }">
                            <div class="relative">
                                <input 
                                    type="text" 
                                    x-model="query"
                                    placeholder="Search documentation..." 
                                    class="form-input w-full pr-10"
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
                        </form>
                    </div>
                    
                    <!-- Documentation Structure -->
                    <div class="docs-widget">
                        <h3 class="docs-widget-title">📂 Documentation</h3>
                        <div class="docs-tree">
                            <?php if (!empty($docsStructure)): ?>
                                <?= renderDocsTree($docsStructure) ?>
                            <?php else: ?>
                                <p class="text-gray-500 text-sm">No documentation structure available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="docs-widget">
                        <h3 class="docs-widget-title">🔗 Quick Links</h3>
                        <div class="docs-widget-list">
                            <div class="docs-widget-item">
                                <a href="/docs/README.md" class="docs-widget-link">Getting Started</a>
                            </div>
                            <div class="docs-widget-item">
                                <a href="/docs/TROUBLESHOOTING.md" class="docs-widget-link">Troubleshooting</a>
                            </div>
                            <div class="docs-widget-item">
                                <a href="/docs/API.md" class="docs-widget-link">API Overview</a>
                            </div>
                            <div class="docs-widget-item">
                                <a href="/blog" class="docs-widget-link">Blog</a>
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

<?php
// Helper function to render docs tree
function renderDocsTree($structure, $level = 0) {
    $html = '';
    
    // Render directories
    if (!empty($structure['dirs'])) {
        foreach ($structure['dirs'] as $dirName => $dirStructure) {
            $html .= '<div class="docs-tree-dir" style="margin-left: ' . ($level * 16) . 'px;">';
            $html .= '<span class="docs-tree-folder">📁 ' . htmlspecialchars($dirName) . '</span>';
            $html .= renderDocsTree($dirStructure, $level + 1);
            $html .= '</div>';
        }
    }
    
    // Render files
    if (!empty($structure['files'])) {
        foreach ($structure['files'] as $file) {
            $html .= '<div class="docs-tree-file" style="margin-left: ' . ($level * 16) . 'px;">';
            $html .= '<a href="/docs/' . htmlspecialchars($file['path']) . '" class="docs-tree-link">';
            $html .= '📄 ' . htmlspecialchars($file['title']);
            $html .= '</a>';
            $html .= '</div>';
        }
    }
    
    return $html;
}
?>
