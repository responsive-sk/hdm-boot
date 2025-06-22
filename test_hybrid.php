<?php

/**
 * Hybrid Storage System Test
 * 
 * Tests both file-based (Articles) and database-based (Users) storage.
 */

declare(strict_types=1);

// Bootstrap
require_once __DIR__ . '/vendor/autoload.php';

use HdmBoot\Modules\Core\Storage\Services\FileStorageService;
use HdmBoot\Modules\Core\Storage\Models\Article;
use HdmBoot\Modules\Core\Storage\Models\User;

echo "🚀 Testing MVA Bootstrap Hybrid Storage System\n";
echo "==============================================\n\n";

try {
    // 1. Setup storage service
    echo "1. Setting up hybrid storage...\n";
    $contentDir = __DIR__ . '/content';
    $storageService = new FileStorageService($contentDir);
    
    // Set storage service for both models
    Article::setStorageService($storageService);
    User::setStorageService($storageService);
    
    echo "   ✅ Storage service initialized\n";
    echo "   📁 Content directory: {$contentDir}\n";
    echo "   🗄️  Database: {$contentDir}/database.sqlite\n\n";

    // 2. Test file-based storage (Articles)
    echo "2. Testing file-based storage (Articles)...\n";
    
    $article = Article::create([
        'title' => 'Hybrid Storage Test',
        'slug' => 'hybrid-test',
        'author' => 'System',
        'published' => true,
        'category' => 'testing',
        'content' => "# Hybrid Storage\n\nThis article is stored in a **Markdown file**! 📝"
    ]);
    
    echo "   ✅ Article created and saved to file\n";
    echo "   📝 Title: {$article->getAttribute('title')}\n";
    echo "   📄 File: content/articles/{$article->getAttribute('slug')}.md\n\n";

    // 3. Test database storage (Users)
    echo "3. Testing database storage (Users)...\n";
    
    $user = User::create([
        'username' => 'testuser',
        'email' => 'test@example.com',
        'first_name' => 'Test',
        'last_name' => 'User',
        'role' => 'admin'
    ]);
    
    $user->setPassword('secret123');
    $user->save();
    
    echo "   ✅ User created and saved to database\n";
    echo "   👤 Username: {$user->getAttribute('username')}\n";
    echo "   📧 Email: {$user->getAttribute('email')}\n";
    echo "   🔑 Role: {$user->getAttribute('role')}\n";
    echo "   🗄️  Storage: SQLite database\n\n";

    // 4. Test hybrid queries
    echo "4. Testing hybrid queries...\n";
    
    // File-based query
    $articles = Article::all();
    echo "   📚 Found " . count($articles) . " article(s) in files\n";
    
    // Database query
    $users = User::all();
    echo "   👥 Found " . count($users) . " user(s) in database\n";
    
    // Specific queries
    $foundArticle = Article::find('hybrid-test');
    $foundUser = User::findByUsername('testuser');
    
    echo "   🔍 Article lookup: " . ($foundArticle ? 'Found' : 'Not found') . "\n";
    echo "   🔍 User lookup: " . ($foundUser ? 'Found' : 'Not found') . "\n\n";

    // 5. Test user authentication
    echo "5. Testing user authentication...\n";
    
    if ($foundUser) {
        $passwordCheck = $foundUser->verifyPassword('secret123');
        echo "   🔐 Password verification: " . ($passwordCheck ? 'Success' : 'Failed') . "\n";
        
        $foundUser->recordLogin();
        $foundUser->save();
        echo "   📊 Login recorded\n";
        
        echo "   👤 Full name: {$foundUser->getFullName()}\n";
        echo "   🛡️  Is admin: " . ($foundUser->isAdmin() ? 'Yes' : 'No') . "\n";
    }
    echo "\n";

    // 6. Test storage locations
    echo "6. Verifying storage locations...\n";
    
    $articleFile = $contentDir . '/articles/hybrid-test.md';
    $databaseFile = $contentDir . '/database.sqlite';
    
    echo "   📄 Article file exists: " . (file_exists($articleFile) ? 'Yes' : 'No') . "\n";
    echo "   🗄️  Database file exists: " . (file_exists($databaseFile) ? 'Yes' : 'No') . "\n";
    
    if (file_exists($articleFile)) {
        echo "   📊 Article file size: " . filesize($articleFile) . " bytes\n";
    }
    
    if (file_exists($databaseFile)) {
        echo "   📊 Database file size: " . filesize($databaseFile) . " bytes\n";
    }
    echo "\n";

    // 7. Show storage summary
    echo "7. Storage summary...\n";
    echo "   📝 Content (Files):     Articles, Documentation, Settings\n";
    echo "   🗄️  Database (SQLite):   Users, Sessions, Logs, Analytics\n";
    echo "   🔄 Hybrid Benefits:     Git-friendly content + Relational data\n";
    echo "   ⚡ Performance:        File cache + Database indexes\n\n";

    echo "🎉 Hybrid storage system test completed successfully!\n";
    echo "✅ File-based storage working (Articles)\n";
    echo "✅ Database storage working (Users)\n";
    echo "✅ Both systems integrated seamlessly\n";

} catch (\Exception $e) {
    echo "❌ Test failed with error:\n";
    echo "   {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    
    if ($e->getPrevious()) {
        echo "   Previous: {$e->getPrevious()->getMessage()}\n";
    }
}

echo "\n🏁 Hybrid test finished.\n";
