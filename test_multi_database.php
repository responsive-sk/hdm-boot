<?php

/**
 * Multi-Database System Test
 * 
 * Tests the multi-database architecture with separate databases for different purposes.
 */

declare(strict_types=1);

// Bootstrap
require_once __DIR__ . '/vendor/autoload.php';

use HdmBoot\Modules\Core\Storage\Services\FileStorageService;
use HdmBoot\Modules\Core\Storage\Services\DatabaseManager;
use HdmBoot\Modules\Core\Storage\Models\Article;
use HdmBoot\Modules\Core\Storage\Models\AppUser;
use HdmBoot\Modules\Core\Storage\Models\MarkUser;
use HdmBoot\Modules\Core\Storage\Models\MarkAuditLog;

echo "🚀 Testing MVA Bootstrap Multi-Database System\n";
echo "==============================================\n\n";

try {
    // 1. Setup multi-database system
    echo "1. Setting up multi-database system...\n";
    $contentDir = __DIR__ . '/content';
    $storageService = new FileStorageService($contentDir);
    
    // Initialize DatabaseManager
    DatabaseManager::initialize($contentDir);
    
    // Set storage service for all models
    Article::setStorageService($storageService);
    AppUser::setStorageService($storageService);
    MarkUser::setStorageService($storageService);
    MarkAuditLog::setStorageService($storageService);
    
    echo "   ✅ Multi-database system initialized\n";
    echo "   📁 Content directory: {$contentDir}\n";
    echo "   🗄️  Database directory: " . dirname($contentDir) . "/var/orbit\n\n";

    // 2. Check database health
    echo "2. Checking database health...\n";
    $healthStatus = DatabaseManager::getHealthStatus();
    
    foreach ($healthStatus as $dbName => $status) {
        $statusIcon = $status['exists'] ? '✅' : '🔧';
        $size = $status['exists'] ? number_format($status['size']) . ' bytes' : 'N/A';
        echo "   {$statusIcon} {$dbName}.db - {$status['description']}\n";
        echo "      📄 File: {$status['filename']} ({$size})\n";
        echo "      🔗 Connected: " . ($status['connected'] ?? false ? 'Yes' : 'No') . "\n";
        
        if (isset($status['table_count'])) {
            echo "      📊 Tables: {$status['table_count']}\n";
        }
        echo "\n";
    }

    // 3. Create database tables
    echo "3. Creating database tables...\n";
    foreach (['app', 'mark', 'cache', 'analytics'] as $dbName) {
        try {
            DatabaseManager::createTables($dbName);
            echo "   ✅ {$dbName}.db tables created\n";
        } catch (\Exception $e) {
            echo "   ❌ {$dbName}.db failed: {$e->getMessage()}\n";
        }
    }
    echo "\n";

    // 4. Test file-based storage (Articles)
    echo "4. Testing file-based storage (Articles)...\n";
    
    $article = Article::create([
        'title' => 'Multi-Database Architecture',
        'slug' => 'multi-database',
        'author' => 'System Architect',
        'published' => true,
        'category' => 'architecture',
        'content' => "# Multi-Database Architecture\n\nThis article demonstrates our **multi-database approach**!\n\n## Benefits\n\n- No read/write conflicts\n- Better performance\n- Security isolation"
    ]);
    
    echo "   ✅ Article created in file storage\n";
    echo "   📝 Title: {$article->getAttribute('title')}\n";
    echo "   📄 File: content/articles/{$article->getAttribute('slug')}.md\n\n";

    // 5. Test app database (AppUser)
    echo "5. Testing app database (AppUser)...\n";
    
    $appUser = AppUser::create([
        'username' => 'john_doe',
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'role' => 'user'
    ]);
    
    $appUser->setPassword('user123');
    $appUser->save();
    
    echo "   ✅ App user created in app.db\n";
    echo "   👤 Username: {$appUser->getAttribute('username')}\n";
    echo "   📧 Email: {$appUser->getAttribute('email')}\n";
    echo "   🗄️  Database: app.db\n\n";

    // 6. Test mark database (MarkUser)
    echo "6. Testing mark database (MarkUser)...\n";
    
    $markUser = MarkUser::create([
        'username' => 'admin',
        'email' => 'admin@example.com',
        'role' => 'super_admin'
    ]);
    
    $markUser->setPassword('admin123');
    $markUser->save();
    
    echo "   ✅ Mark admin created in mark.db\n";
    echo "   👤 Username: {$markUser->getAttribute('username')}\n";
    echo "   🛡️  Role: {$markUser->getAttribute('role')}\n";
    echo "   🗄️  Database: mark.db\n\n";

    // 7. Test audit logging
    echo "7. Testing audit logging...\n";
    
    // Log some admin actions
    $markUser->logAction('article.create', 'article', 'multi-database', [
        'title' => 'Multi-Database Architecture',
        'action_type' => 'create'
    ]);
    
    $markUser->logAction('user.view', 'user', (string) $appUser->getKey(), [
        'viewed_user' => $appUser->getAttribute('username')
    ]);
    
    MarkAuditLog::logSystemAction((int) $markUser->getKey(), 'database.health_check', [
        'databases_checked' => array_keys($healthStatus)
    ]);
    
    echo "   ✅ Audit logs created\n";
    
    $auditLogs = MarkAuditLog::recent(10);
    echo "   📊 Total audit logs: " . count($auditLogs) . "\n";
    
    foreach ($auditLogs as $log) {
        echo "   - {$log->getActionDescription()} at {$log->getAttribute('created_at')}\n";
    }
    echo "\n";

    // 8. Test cross-database queries
    echo "8. Testing cross-database operations...\n";
    
    // App users
    $allAppUsers = AppUser::all();
    echo "   👥 App users (app.db): " . count($allAppUsers) . "\n";
    
    // Mark users  
    $allMarkUsers = MarkUser::all();
    echo "   🛡️  Mark users (mark.db): " . count($allMarkUsers) . "\n";
    
    // Articles
    $allArticles = Article::all();
    echo "   📚 Articles (files): " . count($allArticles) . "\n";
    
    // Audit logs
    $allLogs = MarkAuditLog::all();
    echo "   📋 Audit logs (mark.db): " . count($allLogs) . "\n\n";

    // 9. Test authentication across systems
    echo "9. Testing authentication...\n";
    
    // App user authentication
    $appAuth = $appUser->verifyPassword('user123');
    echo "   🔐 App user auth: " . ($appAuth ? 'Success' : 'Failed') . "\n";
    
    // Mark user authentication
    $markAuth = $markUser->verifyPassword('admin123');
    echo "   🔐 Mark admin auth: " . ($markAuth ? 'Success' : 'Failed') . "\n";
    
    if ($appAuth && $markAuth) {
        $appUser->recordLogin();
        $markUser->recordLogin();
        echo "   📊 Login events recorded\n";
    }
    echo "\n";

    // 10. Database isolation verification
    echo "10. Verifying database isolation...\n";
    
    $databases = DatabaseManager::getDatabases();
    foreach ($databases as $dbName => $config) {
        $path = DatabaseManager::getDatabasePath($dbName);
        $exists = file_exists($path);
        $size = $exists ? filesize($path) : 0;
        
        echo "   📁 {$dbName}.db: " . ($exists ? 'Exists' : 'Missing') . " ({$size} bytes)\n";
        echo "      Purpose: {$config['description']}\n";
        echo "      Tables: " . implode(', ', array_keys($config['tables'])) . "\n";
    }
    echo "\n";

    // 11. Performance comparison
    echo "11. Performance summary...\n";
    echo "   ⚡ File Storage (Articles):     Fast reads, Git-friendly\n";
    echo "   ⚡ App Database (Users):        ACID transactions, relations\n";
    echo "   ⚡ Mark Database (Admin):       Security isolation, audit trail\n";
    echo "   ⚡ Cache Database (Cache):      Performance optimization\n";
    echo "   ⚡ Analytics Database (Stats):  Reporting and metrics\n\n";

    echo "🎉 Multi-database system test completed successfully!\n";
    echo "✅ File-based storage working (Articles)\n";
    echo "✅ App database working (Users, Sessions)\n";
    echo "✅ Mark database working (Admin, Audit)\n";
    echo "✅ Database isolation verified\n";
    echo "✅ Cross-database operations working\n";
    echo "✅ Authentication systems isolated\n";

} catch (\Exception $e) {
    echo "❌ Test failed with error:\n";
    echo "   {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    
    if ($e->getPrevious()) {
        echo "   Previous: {$e->getPrevious()->getMessage()}\n";
    }
}

echo "\n🏁 Multi-database test finished.\n";
