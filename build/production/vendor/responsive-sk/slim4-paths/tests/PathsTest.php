<?php

declare(strict_types=1);

namespace ResponsiveSk\Slim4Paths\Tests;

use PHPUnit\Framework\TestCase;
use ResponsiveSk\Slim4Paths\Paths;

class PathsTest extends TestCase
{
    private Paths $paths;
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = '/test/project';
        $this->paths = new Paths($this->basePath);
    }

    public function testConstructorSetsBasePath(): void
    {
        $this->assertEquals($this->basePath, $this->paths->base());
    }

    public function testConstructorTrimsTrailingSlashes(): void
    {
        $pathsWithSlash = new Paths('/test/project/');
        $pathsWithBackslash = new Paths('/test/project\\');
        
        $this->assertEquals('/test/project', $pathsWithSlash->base());
        $this->assertEquals('/test/project', $pathsWithBackslash->base());
    }

    public function testGetReturnsCorrectPath(): void
    {
        $this->assertEquals('/test/project/config', $this->paths->get('config'));
        $this->assertEquals('/test/project/templates', $this->paths->get('templates'));
        $this->assertEquals('/test/project/var/logs', $this->paths->get('logs'));
    }

    public function testGetThrowsExceptionForInvalidPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Path 'invalid' not found");
        
        $this->paths->get('invalid');
    }

    public function testPathCreatesRelativePath(): void
    {
        $this->assertEquals('/test/project/custom/file.txt', $this->paths->path('custom/file.txt'));
        $this->assertEquals('/test/project/custom/file.txt', $this->paths->path('/custom/file.txt'));
    }

    public function testHasReturnsTrueForExistingPath(): void
    {
        $this->assertTrue($this->paths->has('config'));
        $this->assertTrue($this->paths->has('templates'));
        $this->assertFalse($this->paths->has('invalid'));
    }

    public function testAllReturnsAllPaths(): void
    {
        $allPaths = $this->paths->all();
        
        $this->assertIsArray($allPaths);
        $this->assertArrayHasKey('base', $allPaths);
        $this->assertArrayHasKey('config', $allPaths);
        $this->assertArrayHasKey('templates', $allPaths);
        $this->assertEquals('/test/project', $allPaths['base']);
        $this->assertEquals('/test/project/config', $allPaths['config']);
    }

    public function testConvenienceMethodsReturnCorrectPaths(): void
    {
        $this->assertEquals('/test/project', $this->paths->base());
        $this->assertEquals('/test/project/config', $this->paths->config());
        $this->assertEquals('/test/project/templates', $this->paths->templates());
        $this->assertEquals('/test/project/var/logs', $this->paths->logs());
        $this->assertEquals('/test/project/var/storage', $this->paths->storage());
        $this->assertEquals('/test/project/var/cache', $this->paths->cache());
        $this->assertEquals('/test/project/public/assets', $this->paths->assets());
        $this->assertEquals('/test/project/public/uploads', $this->paths->uploads());
    }

    public function testConvenienceMethodsWithFiles(): void
    {
        $this->assertEquals('/test/project/config/database.php', $this->paths->config('database.php'));
        $this->assertEquals('/test/project/templates/home.php', $this->paths->templates('home.php'));
        $this->assertEquals('/test/project/var/logs/app.log', $this->paths->logs('app.log'));
        $this->assertEquals('/test/project/var/storage/file.txt', $this->paths->storage('file.txt'));
    }

    public function testConvenienceMethodsHandleLeadingSlashes(): void
    {
        $this->assertEquals('/test/project/config/database.php', $this->paths->config('/database.php'));
        $this->assertEquals('/test/project/templates/home.php', $this->paths->templates('/home.php'));
    }

    public function testCustomPathsOverrideDefaults(): void
    {
        $customPaths = [
            'config' => '/custom/config',
            'custom' => '/custom/path'
        ];
        
        $paths = new Paths($this->basePath, $customPaths);
        
        $this->assertEquals('/custom/config', $paths->get('config'));
        $this->assertEquals('/custom/path', $paths->get('custom'));
        $this->assertEquals('/test/project/templates', $paths->get('templates')); // Default not overridden
    }

    public function testCustomPathsAreAccessibleViaConvenienceMethods(): void
    {
        $customPaths = [
            'config' => '/custom/config'
        ];
        
        $paths = new Paths($this->basePath, $customPaths);
        
        $this->assertEquals('/custom/config', $paths->config());
        $this->assertEquals('/custom/config/database.php', $paths->config('database.php'));
    }
}
