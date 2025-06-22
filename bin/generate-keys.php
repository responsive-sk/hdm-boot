#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * HDM Boot - Secure Key Generator
 * 
 * Generates cryptographically secure keys for production deployment.
 * 
 * Usage:
 *   php bin/generate-keys.php
 *   php bin/generate-keys.php --format=env
 *   php bin/generate-keys.php --format=json
 */

function generateSecureKey(int $bytes): string
{
    return bin2hex(random_bytes($bytes));
}

function printHeader(): void
{
    echo "\n🔐 HDM Boot - Secure Key Generator\n";
    echo "==================================\n\n";
}

function printKeys(array $keys, string $format): void
{
    switch ($format) {
        case 'json':
            echo json_encode($keys, JSON_PRETTY_PRINT) . "\n";
            break;
            
        case 'env':
        default:
            foreach ($keys as $name => $value) {
                echo "{$name}={$value}\n";
            }
            break;
    }
}

function printInstructions(): void
{
    echo "\n📝 Instructions:\n";
    echo "1. Copy the generated keys to your .env file\n";
    echo "2. Never commit these keys to version control\n";
    echo "3. Use different keys for each environment\n";
    echo "4. Store keys securely (password manager, vault, etc.)\n";
    echo "5. Rotate keys regularly for security\n\n";
    
    echo "🔒 Security Notes:\n";
    echo "• JWT_SECRET: Used for JWT token signing (64 chars)\n";
    echo "• SECURITY_KEY: General encryption key (64 chars)\n";
    echo "• REDIS_PASSWORD: Redis authentication (32 chars)\n";
    echo "• DB_PASSWORD: Database password (24 chars)\n";
    echo "• CSRF_SECRET: CSRF token generation (64 chars)\n\n";
}

// Parse command line arguments
$format = 'env';
if (isset($argv[1])) {
    if (str_starts_with($argv[1], '--format=')) {
        $format = substr($argv[1], 9);
    }
}

// Validate format
if (!in_array($format, ['env', 'json'], true)) {
    echo "Error: Invalid format. Use 'env' or 'json'\n";
    exit(1);
}

try {
    printHeader();
    
    // Generate keys
    $keys = [
        'JWT_SECRET' => generateSecureKey(32),      // 64 characters
        'SECURITY_KEY' => generateSecureKey(32),    // 64 characters  
        'REDIS_PASSWORD' => generateSecureKey(16),  // 32 characters
        'DB_PASSWORD' => generateSecureKey(12),     // 24 characters
        'CSRF_SECRET' => generateSecureKey(32),     // 64 characters
    ];
    
    echo "✅ Generated secure keys:\n\n";
    printKeys($keys, $format);
    
    if ($format === 'env') {
        printInstructions();
    }
    
} catch (Exception $e) {
    echo "❌ Error generating keys: " . $e->getMessage() . "\n";
    exit(1);
}
