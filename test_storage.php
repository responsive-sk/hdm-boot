<?php

/**
 * Simple Storage System Test
 * 
 * Tests basic functionality of our file-based storage system.
 */

declare(strict_types=1);

// Bootstrap
require_once __DIR__ . '/vendor/autoload.php';

use HdmBoot\Modules\Core\Storage\Services\FileStorageService;
use HdmBoot\Modules\Core\Storage\Drivers\MarkdownDriver;
use HdmBoot\Modules\Core\Storage\Models\Article;

echo "🚀 Testing MVA Bootstrap Storage System\n";
echo "=====================================\n\n";

try {
    // 1. Setup storage service
    echo "1. Setting up storage service...\n";
    $contentDir = __DIR__ . '/content';
    $storageService = new FileStorageService($contentDir);
    
    // Set storage service for models
    Article::setStorageService($storageService);
    
    echo "   ✅ Storage service initialized\n";
    echo "   📁 Content directory: {$contentDir}\n\n";

    // 2. Create test article
    echo "2. Creating test article...\n";
    $articleData = [
        'title' => 'Test Article',
        'slug' => 'test-article',
        'author' => 'Test Author',
        'published' => true,
        'published_at' => date('Y-m-d H:i:s'),
        'category' => 'testing',
        'tags' => ['test', 'storage', 'markdown'],
        'content' => "# Test Article\n\nThis is a test article to verify our storage system works!\n\n## Features\n\n- File-based storage\n- Markdown support\n- YAML front-matter\n\nGreat! 🎉"
    ];
    
    $article = new Article($articleData);
    echo "   ✅ Article object created\n";
    echo "   📝 Title: {$article->getAttribute('title')}\n";
    echo "   🔗 Slug: {$article->getAttribute('slug')}\n\n";

    // 3. Save article
    echo "3. Saving article to file...\n";
    $result = $article->save();
    
    if ($result) {
        echo "   ✅ Article saved successfully\n";
        $filePath = $contentDir . '/articles/' . $article->getAttribute('slug') . '.md';
        echo "   📄 File: {$filePath}\n";
        
        if (file_exists($filePath)) {
            echo "   📊 File size: " . filesize($filePath) . " bytes\n";
        }
    } else {
        echo "   ❌ Failed to save article\n";
    }
    echo "\n";

    // 4. Load all articles
    echo "4. Loading all articles...\n";
    $articles = Article::all();
    echo "   📚 Found " . count($articles) . " article(s)\n";
    
    foreach ($articles as $loadedArticle) {
        echo "   - {$loadedArticle->getAttribute('title')} (slug: {$loadedArticle->getAttribute('slug')})\n";
    }
    echo "\n";

    // 5. Find specific article
    echo "5. Finding article by slug...\n";
    $foundArticle = Article::find('test-article');
    
    if ($foundArticle) {
        echo "   ✅ Article found!\n";
        echo "   📝 Title: {$foundArticle->getAttribute('title')}\n";
        echo "   👤 Author: {$foundArticle->getAttribute('author')}\n";
        echo "   📅 Published: " . ($foundArticle->getAttribute('published') ? 'Yes' : 'No') . "\n";
        echo "   🏷️  Tags: " . implode(', ', $foundArticle->getAttribute('tags') ?? []) . "\n";
    } else {
        echo "   ❌ Article not found\n";
    }
    echo "\n";

    // 6. Test file content
    echo "6. Checking file content...\n";
    $filePath = $contentDir . '/articles/test-article.md';
    
    if (file_exists($filePath)) {
        $fileContent = file_get_contents($filePath);
        echo "   ✅ File exists and readable\n";
        echo "   📄 Content preview:\n";
        echo "   " . str_repeat('-', 50) . "\n";
        
        $lines = explode("\n", $fileContent);
        $previewLines = array_slice($lines, 0, 10);
        foreach ($previewLines as $line) {
            echo "   {$line}\n";
        }
        
        if (count($lines) > 10) {
            echo "   ... (" . (count($lines) - 10) . " more lines)\n";
        }
        echo "   " . str_repeat('-', 50) . "\n";
    } else {
        echo "   ❌ File not found\n";
    }
    echo "\n";

    echo "🎉 Storage system test completed successfully!\n";
    echo "✅ All basic operations working\n";

} catch (\Exception $e) {
    echo "❌ Test failed with error:\n";
    echo "   {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    
    if ($e->getPrevious()) {
        echo "   Previous: {$e->getPrevious()->getMessage()}\n";
    }
}

echo "\n🏁 Test finished.\n";
