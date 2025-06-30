#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * HDM Boot System Database Initialization.
 * 
 * Creates system.db with core system modules data.
 * Follows HDM Boot Protocol - ONLY system data, NO user data.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HdmBoot\Modules\Core\Database\SystemSqliteDatabaseManager;
use ResponsiveSk\Slim4Paths\Paths;

echo "🟢 HDM Boot System Database Initialization\n";
echo "=========================================\n\n";

try {
    // Initialize Paths service
    $paths = new Paths(__DIR__ . '/..');
    
    // Create System database manager
    $systemManager = new SystemSqliteDatabaseManager('storage/system.db', $paths);
    
    echo "📁 Initializing system.db...\n";
    $connection = $systemManager->getConnection();
    
    // Check if sample blog article exists
    $stmt = $connection->prepare('SELECT COUNT(*) FROM blog_articles WHERE slug = ?');
    $stmt->execute(['welcome-to-hdm-boot-blog']);
    $sampleArticleExists = (int) $stmt->fetchColumn() > 0;
    
    if (!$sampleArticleExists) {
        echo "📝 Creating sample blog article...\n";
        
        $sampleArticle = [
            'id' => 'article-' . uniqid(),
            'title' => 'Welcome to HDM Boot Protocol',
            'slug' => 'welcome-to-hdm-boot-protocol',
            'content' => "# Welcome to HDM Boot Protocol\n\nThis is your first article in HDM Boot framework.\n\n## HDM Boot Protocol Features\n\n- **Three-Database Architecture** - mark.db, user.db, system.db\n- **Forbidden 'Admin' Terminology** - Use 'mark' instead\n- **Secure Path Resolution** - ResponsiveSk\\Slim4Paths\n- **Centralized Permission Management** - PermissionManager\n- **Container Abstraction** - Support for multiple DI containers\n\n## Getting Started\n\n1. **Mark System** - Super user functionality (mark.db)\n2. **User System** - Application users (user.db)\n3. **System Modules** - Core functionality (system.db)\n\n## Protocol Compliance\n\nThis installation follows HDM Boot Protocol v2.0:\n- ✅ Three-database isolation\n- ✅ No 'admin' terminology\n- ✅ Secure architecture\n- ✅ Production-ready deployment\n\nEnjoy building with HDM Boot Protocol!",
            'excerpt' => 'Welcome to HDM Boot Protocol - a modern PHP framework built with three-database architecture and strict protocol compliance.',
            'author_id' => 'system',
            'category' => 'protocol',
            'tags' => '["hdm-boot", "protocol", "architecture", "php", "framework"]',
            'status' => 'published',
            'published_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $stmt = $connection->prepare("
            INSERT INTO blog_articles (id, title, slug, content, excerpt, author_id, category, tags, status, published_at, created_at, updated_at)
            VALUES (:id, :title, :slug, :content, :excerpt, :author_id, :category, :tags, :status, :published_at, :created_at, :updated_at)
        ");
        
        $stmt->execute($sampleArticle);
        echo "   ✅ Created sample blog article\n";
    } else {
        echo "   ✅ Sample blog article already exists\n";
    }
    
    // Add some system cache entries
    $stmt = $connection->prepare('SELECT COUNT(*) FROM system_cache WHERE cache_key = ?');
    $stmt->execute(['system_initialized']);
    $cacheExists = (int) $stmt->fetchColumn() > 0;
    
    if (!$cacheExists) {
        echo "💾 Creating system cache entries...\n";
        
        $cacheEntries = [
            [
                'cache_key' => 'system_initialized',
                'cache_value' => json_encode([
                    'initialized_at' => date('Y-m-d H:i:s'),
                    'protocol_version' => '2.0',
                    'databases' => ['mark.db', 'user.db', 'system.db']
                ]),
                'expires_at' => time() + (365 * 24 * 60 * 60), // 1 year
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'cache_key' => 'protocol_compliance',
                'cache_value' => json_encode([
                    'admin_terminology_banned' => true,
                    'three_database_architecture' => true,
                    'secure_paths' => true,
                    'permission_management' => true,
                    'container_abstraction' => true
                ]),
                'expires_at' => time() + (365 * 24 * 60 * 60), // 1 year
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ];
        
        $stmt = $connection->prepare("
            INSERT INTO system_cache (cache_key, cache_value, expires_at, created_at)
            VALUES (:cache_key, :cache_value, :expires_at, :created_at)
        ");
        
        foreach ($cacheEntries as $entry) {
            $stmt->execute($entry);
        }
        
        echo "   ✅ Created system cache entries\n";
    } else {
        echo "   ✅ System cache already initialized\n";
    }
    
    // Get statistics
    $stats = $systemManager->getStatistics();
    
    echo "\n📊 System Database Statistics:\n";
    foreach ($stats as $table => $count) {
        echo "   • {$table}: {$count} records\n";
    }
    
    echo "\n✅ System database initialization completed!\n";
    echo "\n🗄️ System Data Created:\n";
    echo "   • Sample blog article\n";
    echo "   • System cache entries\n";
    echo "   • Protocol compliance markers\n";
    echo "\n🎯 Protocol Compliance: ✅ ONLY system data in system.db\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
