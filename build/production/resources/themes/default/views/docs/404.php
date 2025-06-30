<!DOCTYPE html>
<html lang="en" <?= $theme::getHtmlAttributes() ?>>
<head>
    <?= $theme::renderHead($title ?? 'Documentation Not Found') ?>
</head>
<body class="<?= $theme::getBodyClass() ?> docs-page docs-404-page">
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
            <div class="docs-layout">
                <div class="docs-main">
                    <!-- 404 Content -->
                    <div class="docs-404">
                        <div class="text-center py-16">
                            <div class="text-6xl mb-6">📚</div>
                            <h1 class="text-4xl font-bold text-gray-900 mb-4">Documentation Not Found</h1>
                            <p class="text-xl text-gray-600 mb-8 max-w-md mx-auto">
                                The documentation page you're looking for doesn't exist or has been moved.
                            </p>
                            <div class="space-x-4">
                                <a href="/docs" class="btn btn-primary">
                                    Browse Documentation
                                </a>
                                <a href="/docs/README.md" class="btn btn-outline">
                                    Getting Started
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Helpful Links -->
                    <div class="docs-section">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">📖 Popular Documentation</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="docs-card group">
                                <div class="docs-card-icon">🏗️</div>
                                <h3 class="docs-card-title">Architecture</h3>
                                <p class="docs-card-description">Learn about HDM Boot's hexagonal architecture.</p>
                                <a href="/docs/architecture/README.md" class="docs-card-link">Read More →</a>
                            </div>
                            
                            <div class="docs-card group">
                                <div class="docs-card-icon">📖</div>
                                <h3 class="docs-card-title">Guides</h3>
                                <p class="docs-card-description">Step-by-step development guides.</p>
                                <a href="/docs/guides/README.md" class="docs-card-link">Read More →</a>
                            </div>
                            
                            <div class="docs-card group">
                                <div class="docs-card-icon">🔌</div>
                                <h3 class="docs-card-title">API Reference</h3>
                                <p class="docs-card-description">Complete API documentation.</p>
                                <a href="/docs/api/README.md" class="docs-card-link">Read More →</a>
                            </div>
                            
                            <div class="docs-card group">
                                <div class="docs-card-icon">🚀</div>
                                <h3 class="docs-card-title">Quick Start</h3>
                                <p class="docs-card-description">Get started with HDM Boot quickly.</p>
                                <a href="/docs/ORBIT_QUICK_START.md" class="docs-card-link">Read More →</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="docs-sidebar">
                    <!-- Search -->
                    <div class="docs-widget">
                        <h3 class="docs-widget-title">🔍 Search Docs</h3>
                        <form class="docs-search-form" action="/docs" method="get">
                            <div class="relative">
                                <input 
                                    type="text" 
                                    name="search"
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
                                <a href="/docs" class="docs-widget-link">Documentation Home</a>
                            </div>
                            <div class="docs-widget-item">
                                <a href="/docs/TROUBLESHOOTING.md" class="docs-widget-link">Troubleshooting</a>
                            </div>
                            <div class="docs-widget-item">
                                <a href="/blog" class="docs-widget-link">Blog</a>
                            </div>
                            <div class="docs-widget-item">
                                <a href="/" class="docs-widget-link">Home</a>
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
