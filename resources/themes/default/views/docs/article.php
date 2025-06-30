<!DOCTYPE html>
<html lang="en" <?= $theme::getHtmlAttributes() ?>>
<head>
    <?= $theme::renderHead($title ?? 'Documentation') ?>
</head>
<body class="<?= $theme::getBodyClass() ?> docs-page docs-article-page">
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
            <!-- Breadcrumbs -->
            <?php if (!empty($breadcrumbs)): ?>
                <nav class="docs-breadcrumbs">
                    <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                        <?php if ($index > 0): ?>
                            <span class="docs-breadcrumb-separator">→</span>
                        <?php endif; ?>
                        
                        <?php if ($index === count($breadcrumbs) - 1): ?>
                            <span class="text-gray-900 font-medium"><?= htmlspecialchars($breadcrumb['name']) ?></span>
                        <?php else: ?>
                            <a href="/docs/<?= htmlspecialchars($breadcrumb['path']) ?>" class="hover:text-primary-600">
                                <?= htmlspecialchars($breadcrumb['name']) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
            
            <div class="docs-layout">
                <div class="docs-main">
                    <!-- Article -->
                    <article class="docs-article">
                        <header class="docs-article-header">
                            <h1 class="docs-article-title"><?= htmlspecialchars($title ?? 'Documentation') ?></h1>
                        </header>
                        
                        <div class="docs-article-content">
                            <?= $content ?? '<p>No content available.</p>' ?>
                        </div>
                    </article>
                    
                    <!-- Navigation -->
                    <div class="docs-navigation mt-12 pt-8 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <a href="/docs" class="btn btn-outline">
                                ← Back to Documentation
                            </a>
                            
                            <div class="flex items-center space-x-4">
                                <button onclick="window.print()" class="btn btn-outline">
                                    🖨️ Print
                                </button>
                                <button onclick="copyToClipboard(window.location.href)" class="btn btn-outline">
                                    🔗 Share
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="docs-sidebar">
                    <!-- Table of Contents -->
                    <div class="docs-widget">
                        <h3 class="docs-widget-title">📋 Table of Contents</h3>
                        <div class="docs-toc">
                            <div class="docs-toc-list" id="toc-list">
                                <!-- TOC will be generated by JavaScript -->
                                <p class="text-gray-500 text-sm">Loading table of contents...</p>
                            </div>
                        </div>
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
                                <a href="/docs/README.md" class="docs-widget-link">Getting Started</a>
                            </div>
                            <div class="docs-widget-item">
                                <a href="/docs/TROUBLESHOOTING.md" class="docs-widget-link">Troubleshooting</a>
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

    <script>
        // Generate Table of Contents
        document.addEventListener('DOMContentLoaded', function() {
            generateTableOfContents();
        });

        function generateTableOfContents() {
            const content = document.querySelector('.docs-article-content');
            const tocList = document.getElementById('toc-list');
            
            if (!content || !tocList) return;
            
            const headings = content.querySelectorAll('h1, h2, h3, h4, h5, h6');
            
            if (headings.length === 0) {
                tocList.innerHTML = '<p class="text-gray-500 text-sm">No headings found.</p>';
                return;
            }
            
            let tocHTML = '';
            headings.forEach((heading, index) => {
                const id = `heading-${index}`;
                heading.id = id;
                
                const level = parseInt(heading.tagName.charAt(1));
                const indent = (level - 1) * 16;
                
                tocHTML += `<div style="margin-left: ${indent}px;" class="py-1">
                    <a href="#${id}" class="docs-toc-link text-sm">${heading.textContent}</a>
                </div>`;
            });
            
            tocList.innerHTML = tocHTML;
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Link copied to clipboard!');
            });
        }
    </script>
</body>
</html>

<?php
// Helper function to render docs tree (same as in index.php)
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
