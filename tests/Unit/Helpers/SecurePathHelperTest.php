<?php

declare(strict_types=1);

namespace HdmBoot\Tests\Unit\Helpers;

use InvalidArgumentException;
use HdmBoot\Shared\Helpers\SecurePathHelper;
use HdmBoot\Tests\TestCase;

/**
 * Unit tests for SecurePathHelper.
 */
class SecurePathHelperTest extends TestCase
{
    private SecurePathHelper $pathHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pathHelper = $this->container->get(SecurePathHelper::class);
    }

    public function testSecurePathWithValidPath(): void
    {
        $path = $this->pathHelper->securePath('test.txt', 'storage');

        $this->assertIsString($path);
        $this->assertStringContainsString('storage', $path);
        $this->assertStringContainsString('test.txt', $path);
        $this->assertStringNotContainsString('..', $path);
    }

    public function testSecurePathWithPathTraversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path traversal detected');

        $this->pathHelper->securePath('../../../etc/passwd', 'storage');
    }

    public function testSecurePathWithInvalidDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base directory');

        $this->pathHelper->securePath('test.txt', 'invalid_directory');
    }

    public function testSecurePathWithEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path cannot be empty');

        $this->pathHelper->securePath('', 'storage');
    }

    public function testSecurePathWithAbsolutePath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Absolute paths are not allowed');

        $this->pathHelper->securePath('/etc/passwd', 'storage');
    }

    public function testFileExists(): void
    {
        // Test with non-existent file
        $exists = $this->pathHelper->fileExists('non-existent.txt', 'storage');
        $this->assertFalse($exists);

        // Test with invalid path (should return false, not throw exception)
        $exists = $this->pathHelper->fileExists('../../../etc/passwd', 'storage');
        $this->assertFalse($exists);
    }

    public function testCreateSecureFile(): void
    {
        $content = 'Test file content';
        $path = $this->pathHelper->createSecureFile('test-file.txt', $content, 'storage');

        $this->assertIsString($path);
        $this->assertStringContainsString('test-file.txt', $path);

        // Verify file was created and has correct content
        $this->assertTrue($this->pathHelper->fileExists('test-file.txt', 'storage'));

        // Clean up
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function testCreateSecureFileWithInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->pathHelper->createSecureFile('../../../malicious.txt', 'content', 'storage');
    }

    public function testGetAllowedDirectories(): void
    {
        $directories = $this->pathHelper->getAllowedDirectories();

        $this->assertIsArray($directories);
        $this->assertNotEmpty($directories);
        $this->assertContains('storage', array_keys($directories));
        $this->assertContains('cache', array_keys($directories));
        $this->assertContains('logs', array_keys($directories));
    }

    public function testValidatePathSecurity(): void
    {
        // Valid path should not throw exception
        $this->pathHelper->validatePathSecurity('valid/path.txt');

        // Path traversal should throw exception
        $this->expectException(InvalidArgumentException::class);
        $this->pathHelper->validatePathSecurity('../../../etc/passwd');
    }

    public function testNormalizePath(): void
    {
        // Test basic normalization
        $normalized = $this->pathHelper->normalizePath('path//to///file.txt');
        $this->assertSame('path/to/file.txt', $normalized);

        // Test Windows path separators
        $normalized = $this->pathHelper->normalizePath('path\\to\\file.txt');
        $this->assertSame('path/to/file.txt', $normalized);

        // Test mixed separators
        $normalized = $this->pathHelper->normalizePath('path\\to//file.txt');
        $this->assertSame('path/to/file.txt', $normalized);
    }

    public function testSecurePathWithDifferentDirectories(): void
    {
        $directories = ['storage', 'cache', 'logs', 'uploads'];

        foreach ($directories as $directory) {
            $path = $this->pathHelper->securePath('test.txt', $directory);
            $this->assertIsString($path);
            $this->assertStringContainsString($directory, $path);
        }
    }

    public function testSecurePathWithSubdirectories(): void
    {
        $path = $this->pathHelper->securePath('subdir/test.txt', 'storage');

        $this->assertIsString($path);
        $this->assertStringContainsString('storage', $path);
        $this->assertStringContainsString('subdir', $path);
        $this->assertStringContainsString('test.txt', $path);
    }

    public function testSecurePathWithSpecialCharacters(): void
    {
        // Test with safe special characters
        $path = $this->pathHelper->securePath('test-file_123.txt', 'storage');
        $this->assertIsString($path);

        // Test with potentially dangerous characters
        $this->expectException(InvalidArgumentException::class);
        $this->pathHelper->securePath('test<script>.txt', 'storage');
    }

    public function testPathSecurityWithNullBytes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->pathHelper->securePath("test\0.txt", 'storage');
    }

    public function testCreateSecureFileCreatesDirectory(): void
    {
        $content = 'Test content';
        $path = $this->pathHelper->createSecureFile('newdir/test.txt', $content, 'storage');

        $this->assertIsString($path);
        $this->assertTrue(is_dir(dirname($path)));

        // Clean up
        if (file_exists($path)) {
            unlink($path);
            rmdir(dirname($path));
        }
    }

    public function testSecurePathPerformance(): void
    {
        $startTime = microtime(true);

        // Test multiple path validations
        for ($i = 0; $i < 1000; $i++) {
            $this->pathHelper->securePath("test-{$i}.txt", 'storage');
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete 1000 validations in under 1 second
        $this->assertLessThan(1.0, $duration, 'Path validation should be fast');
    }
}
