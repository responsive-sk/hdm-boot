<?php
/**
 * Blog About Template
 */

$title = 'About - HDM Boot Blog';
$bodyClass = 'article-page';

ob_start();
?>

<div class="page-content">
    <div class="article-header animate-fade-in">
        <div class="article-header-content">
        <h1 class="article-title">About This Blog</h1>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Learn about HDM Boot's Orbit-style content management system
        </p>
    </div>
</div>

<div class="article-content">
    <div class="mb-8">
        <a href="/blog" class="text-primary-600 hover:text-primary-700 font-medium">← Back to Blog</a>
    </div>
    
    <article class="prose-custom">
        <h2>HDM Boot Blog System</h2>
        <p>This blog demonstrates HDM Boot's powerful <strong>Orbit-style content management system</strong>.</p>
        
        <h3>Features</h3>
        <ul>
            <li><strong>Markdown Support</strong> - Write articles in Markdown with YAML front-matter</li>
            <li><strong>File-based Storage</strong> - Articles stored as files in <code>content/articles/</code></li>
            <li><strong>Theme System</strong> - Modern themes with Tailwind CSS, GSAP, and Alpine.js</li>
            <li><strong>API Integration</strong> - RESTful API for content management</li>
            <li><strong>Responsive Design</strong> - Mobile-first, accessible interface</li>
        </ul>
        
        <h3>Technology Stack</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="font-semibold">Frontend</h4>
                </div>
                <div class="card-body">
                    <ul class="space-y-2">
                        <li>• Tailwind CSS</li>
                        <li>• Alpine.js</li>
                        <li>• GSAP Animations</li>
                        <li>• Vite Build System</li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h4 class="font-semibold">Backend</h4>
                </div>
                <div class="card-body">
                    <ul class="space-y-2">
                        <li>• PHP 8.4</li>
                        <li>• Slim Framework</li>
                        <li>• File-based Storage</li>
                        <li>• RESTful API</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <h3>Theme System</h3>
        <p>HDM Boot features a powerful theme system with:</p>
        <ul>
            <li><strong>Laravel-style Resources</strong> - Organized theme structure</li>
            <li><strong>Per-theme Dependencies</strong> - Each theme has its own node_modules</li>
            <li><strong>Vite Build System</strong> - Fast, modern asset compilation</li>
            <li><strong>Theme Switching</strong> - Runtime theme changes</li>
        </ul>
        
        <div class="bg-gray-50 p-6 rounded-lg my-6">
            <h4 class="font-semibold mb-3">Current Theme: <?= $theme::getActiveTheme() ?></h4>
            <?php $config = $theme::getThemeConfig(); ?>
            <p class="text-gray-600 mb-3"><?= htmlspecialchars($config['description']) ?></p>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($config['stack'] as $tech): ?>
                    <span class="bg-primary-100 text-primary-700 px-3 py-1 rounded-full text-sm">
                        <?= htmlspecialchars($tech) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        
        <h3>Getting Started</h3>
        <p>To add new articles, simply create Markdown files in the <code>content/articles/</code> directory with YAML front-matter:</p>
        
        <pre><code>---
title: "My Article Title"
author: "Author Name"
published: true
category: "Technology"
tags: ["php", "web-development"]
published_at: "2024-01-01"
---

# Article Content

Your article content goes here...</code></pre>
        
        <p>The blog system will automatically detect and display your articles!</p>
        
        <h3>API Access</h3>
        <p>The blog also provides a RESTful API for programmatic access:</p>
        <ul>
            <li><code>GET /api/blog/articles</code> - List all articles</li>
            <li><code>GET /api/blog/articles/{slug}</code> - Get specific article</li>
            <li><code>GET /api/blog/categories</code> - List categories</li>
            <li><code>GET /api/blog/tags</code> - List tags</li>
        </ul>
    </article>
</div>
</div>

<?php
$content = ob_get_clean();
$viewRenderer = new \HdmBoot\SharedKernel\Services\ViewRenderer();
echo $viewRenderer->renderWithLayout('layouts.app', $content, compact('title', 'bodyClass'));
?>
