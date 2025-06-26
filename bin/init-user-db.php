#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * HDM Boot User Database Initialization.
 * 
 * Creates user.db with proper application users.
 * Follows HDM Boot Protocol - ONLY regular users, NO mark users.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HdmBoot\Modules\Core\Database\UserSqliteDatabaseManager;
use ResponsiveSk\Slim4Paths\Paths;

echo "🔵 HDM Boot User Database Initialization\n";
echo "=======================================\n\n";

try {
    // Initialize Paths service
    $paths = new Paths(__DIR__ . '/..');
    
    // Create User database manager
    $userManager = new UserSqliteDatabaseManager('storage/user.db', $paths);
    
    echo "📁 Initializing user.db...\n";
    $connection = $userManager->getConnection();
    
    // Check if users exist
    $stmt = $connection->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    
    // Check for test@example.com
    $stmt->execute(['test@example.com']);
    $testUserExists = (int) $stmt->fetchColumn() > 0;
    
    // Check for user@example.com
    $stmt->execute(['user@example.com']);
    $userUserExists = (int) $stmt->fetchColumn() > 0;
    
    if (!$testUserExists) {
        echo "👤 Creating test@example.com user...\n";
        
        $testUser = [
            'id' => 'user-' . uniqid(),
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'user', // ✅ Regular user role
            'status' => 'active',
            'email_verified' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $stmt = $connection->prepare("
            INSERT INTO users (id, email, name, password_hash, role, status, email_verified, created_at, updated_at)
            VALUES (:id, :email, :name, :password_hash, :role, :status, :email_verified, :created_at, :updated_at)
        ");
        
        $stmt->execute($testUser);
        echo "   ✅ Created test@example.com / password123\n";
    } else {
        echo "   ✅ test@example.com already exists\n";
    }
    
    if (!$userUserExists) {
        echo "👤 Creating user@example.com user...\n";
        
        $userUser = [
            'id' => 'user-' . uniqid(),
            'email' => 'user@example.com',
            'name' => 'Example User',
            'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
            'role' => 'user', // ✅ Regular user role
            'status' => 'active',
            'email_verified' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $stmt->execute($userUser);
        echo "   ✅ Created user@example.com / user123\n";
    } else {
        echo "   ✅ user@example.com already exists\n";
    }
    
    // Get statistics
    $stats = $userManager->getStatistics();
    
    echo "\n📊 User Database Statistics:\n";
    foreach ($stats as $table => $count) {
        echo "   • {$table}: {$count} records\n";
    }
    
    echo "\n✅ User database initialization completed!\n";
    echo "\n👥 Application Users Created:\n";
    echo "   • test@example.com / password123 (role: user)\n";
    echo "   • user@example.com / user123 (role: user)\n";
    echo "\n🎯 Protocol Compliance: ✅ NO mark users in user.db\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
