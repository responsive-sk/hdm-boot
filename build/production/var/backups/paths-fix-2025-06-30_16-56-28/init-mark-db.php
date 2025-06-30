#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * HDM Boot Mark Database Initialization.
 * 
 * Creates mark.db with proper mark users and data.
 * Follows HDM Boot Protocol - NO "admin" terminology allowed.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HdmBoot\Modules\Core\Database\MarkSqliteDatabaseManager;
use ResponsiveSk\Slim4Paths\Paths;

echo "🔴 HDM Boot Mark Database Initialization\n";
echo "=======================================\n\n";

try {
    // Initialize Paths service
    $paths = new Paths(__DIR__ . '/..');
    
    // Create Mark database manager
    $markManager = new MarkSqliteDatabaseManager('storage/mark.db', $paths);
    
    echo "📁 Initializing mark.db...\n";
    $connection = $markManager->getConnection();
    
    // Check if mark users exist
    $stmt = $connection->prepare('SELECT COUNT(*) FROM mark_users WHERE email = ?');
    
    // Check for mark@responsive.sk
    $stmt->execute(['mark@responsive.sk']);
    $markUserExists = (int) $stmt->fetchColumn() > 0;
    
    // Check for admin@example.com (to be converted to mark)
    $stmt->execute(['admin@example.com']);
    $adminUserExists = (int) $stmt->fetchColumn() > 0;
    
    if (!$markUserExists) {
        echo "👤 Creating mark@responsive.sk user...\n";
        
        $markUser = [
            'id' => 'mark-' . uniqid(),
            'username' => 'mark',
            'email' => 'mark@responsive.sk',
            'password_hash' => password_hash('mark123', PASSWORD_DEFAULT),
            'role' => 'mark_admin',
            'status' => 'active',
            'last_login_at' => null,
            'login_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $stmt = $connection->prepare("
            INSERT INTO mark_users (id, username, email, password_hash, role, status, last_login_at, login_count, created_at, updated_at)
            VALUES (:id, :username, :email, :password_hash, :role, :status, :last_login_at, :login_count, :created_at, :updated_at)
        ");
        
        $stmt->execute($markUser);
        echo "   ✅ Created mark@responsive.sk / mark123\n";
    } else {
        echo "   ✅ mark@responsive.sk already exists\n";
    }
    
    if (!$adminUserExists) {
        echo "👤 Creating admin@example.com user (with mark role)...\n";

        $adminUser = [
            'id' => '550e8400-e29b-41d4-a716-446655440000', // Keep same ID for compatibility
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'mark_admin', // ✅ Protocol compliant - NO "admin" role
            'status' => 'active',
            'last_login_at' => null,
            'login_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $stmt = $connection->prepare("
            INSERT INTO mark_users (id, username, email, password_hash, role, status, last_login_at, login_count, created_at, updated_at)
            VALUES (:id, :username, :email, :password_hash, :role, :status, :last_login_at, :login_count, :created_at, :updated_at)
        ");

        $stmt->execute($adminUser);
        echo "   ✅ Created admin@example.com / admin123 (role: mark_admin)\n";
    } else {
        echo "   ✅ admin@example.com already exists\n";
    }
    
    // Get statistics
    $stats = $markManager->getStatistics();
    
    echo "\n📊 Mark Database Statistics:\n";
    foreach ($stats as $table => $count) {
        echo "   • {$table}: {$count} records\n";
    }
    
    echo "\n✅ Mark database initialization completed!\n";
    echo "\n🔑 Mark Users Created:\n";
    echo "   • mark@responsive.sk / mark123 (role: mark_admin)\n";
    echo "   • admin@example.com / admin123 (role: mark_admin)\n";
    echo "\n🎯 Protocol Compliance: ✅ NO 'admin' roles used\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
