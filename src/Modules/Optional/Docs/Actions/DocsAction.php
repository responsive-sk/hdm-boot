<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Optional\Docs\Actions;

use HdmBoot\SharedKernel\Services\ViewRenderer;

/**
 * Docs Action.
 * 
 * Theme-aware documentation viewer with modern UI.
 */
class DocsAction
{
    private ViewRenderer $viewRenderer;
    private string $docsPath;

    public function __construct()
    {
        $this->viewRenderer = new ViewRenderer();
        // Use __DIR__ to get base path instead of Paths service
        $this->docsPath = dirname(__DIR__, 4) . '/docs';
    }

    /**
     * Documentation index page.
     */
    public function index(?string $path = null): string
    {
        $path = $path ?? '';
        
        if (empty($path) || $path === '/') {
            return $this->renderDocsIndex();
        }

        if (str_ends_with($path, '.md')) {
            return $this->renderMarkdownFile($path);
        }

        // Try to find index file in directory
        $dirPath = rtrim($path, '/');
        $indexFile = $dirPath . '/README.md';
        
        if ($this->fileExists($indexFile)) {
            return $this->renderMarkdownFile($indexFile);
        }

        return $this->renderDocsIndex();
    }

    /**
     * Render documentation index.
     */
    private function renderDocsIndex(): string
    {
        $docsStructure = $this->getDocsStructure();
        $readmeContent = '';
        
        // Try to load main README
        $readmePath = $this->docsPath . '/README.md';
        if (file_exists($readmePath)) {
            $readmeContent = $this->convertMarkdownToHtml(file_get_contents($readmePath) ?: '');
        }

        return $this->viewRenderer->render('docs.index', [
            'docsStructure' => $docsStructure,
            'readmeContent' => $readmeContent,
            'currentPath' => '',
            'title' => 'HDM Boot Documentation',
        ]);
    }

    /**
     * Render markdown file.
     */
    private function renderMarkdownFile(string $path): string
    {
        $filePath = $this->docsPath . '/' . ltrim($path, '/');
        
        if (!$this->fileExists($path) || !file_exists($filePath)) {
            return $this->render404();
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return $this->render404();
        }

        $htmlContent = $this->convertMarkdownToHtml($content);
        $title = $this->extractTitleFromMarkdown($content);
        $docsStructure = $this->getDocsStructure();

        return $this->viewRenderer->render('docs.article', [
            'content' => $htmlContent,
            'title' => $title,
            'currentPath' => $path,
            'docsStructure' => $docsStructure,
            'breadcrumbs' => $this->generateBreadcrumbs($path),
        ]);
    }

    /**
     * Check if file exists and is safe.
     */
    private function fileExists(string $path): bool
    {
        $filePath = $this->docsPath . '/' . ltrim($path, '/');
        
        // Security: ensure path is within docs directory
        $realDocsPath = realpath($this->docsPath);
        $realFilePath = realpath($filePath);
        
        if (!$realDocsPath || !$realFilePath) {
            return false;
        }
        
        return str_starts_with($realFilePath, $realDocsPath) && file_exists($filePath);
    }

    /**
     * Get documentation structure.
     *
     * @return array<string, mixed>
     */
    private function getDocsStructure(): array
    {
        $structure = [];
        
        if (!is_dir($this->docsPath)) {
            return $structure;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->docsPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $relativePath = str_replace($this->docsPath . '/', '', $file->getPathname());
                $pathParts = explode('/', $relativePath);
                
                $current = &$structure;
                foreach ($pathParts as $i => $part) {
                    if ($i === count($pathParts) - 1) {
                        // This is the file
                        if (!isset($current['files'])) {
                            $current['files'] = [];
                        }
                        $current['files'][] = [
                            'name' => $part,
                            'path' => $relativePath,
                            'title' => $this->getFileTitle($file->getPathname()),
                        ];
                    } else {
                        // This is a directory
                        if (!isset($current['dirs'])) {
                            $current['dirs'] = [];
                        }
                        // @phpstan-ignore-next-line
                        if (!isset($current['dirs'][$part])) {
                            $current['dirs'][$part] = ['files' => [], 'dirs' => []];
                        }
                        $current = &$current['dirs'][$part];
                    }
                }
            }
        }

        return $structure;
    }

    /**
     * Get file title from markdown.
     */
    private function getFileTitle(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return basename($filePath, '.md');
        }

        return $this->extractTitleFromMarkdown($content);
    }

    /**
     * Extract title from markdown content.
     */
    private function extractTitleFromMarkdown(string $content): string
    {
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '# ')) {
                return trim(substr($line, 2));
            }
        }
        
        return 'Documentation';
    }

    /**
     * Convert markdown to HTML.
     */
    private function convertMarkdownToHtml(string $markdown): string
    {
        // Enhanced markdown conversion
        $html = htmlspecialchars($markdown);

        // Headers
        $html = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $html) ?? $html;
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html) ?? $html;
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html) ?? $html;
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html) ?? $html;

        // Bold and italic
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html) ?? $html;
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html) ?? $html;

        // Code blocks
        $html = preg_replace('/```(.+?)```/s', '<pre><code>$1</code></pre>', $html) ?? $html;
        $html = preg_replace('/`(.+?)`/', '<code>$1</code>', $html) ?? $html;

        // Links
        $html = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $html) ?? $html;

        // Lists
        $html = preg_replace('/^- (.+)$/m', '<li>$1</li>', $html) ?? $html;
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html) ?? $html;

        // Line breaks
        $html = nl2br($html);

        return $html;
    }

    /**
     * Generate breadcrumbs.
     *
     * @return array<int, array<string, string>>
     */
    private function generateBreadcrumbs(string $path): array
    {
        $breadcrumbs = [['name' => 'Documentation', 'path' => '']];
        
        $pathParts = array_filter(explode('/', $path));
        $currentPath = '';
        
        foreach ($pathParts as $part) {
            $currentPath .= '/' . $part;
            $name = str_replace(['-', '_'], ' ', ucfirst(basename($part, '.md')));
            $breadcrumbs[] = ['name' => $name, 'path' => ltrim($currentPath, '/')];
        }
        
        return $breadcrumbs;
    }

    /**
     * Render 404 page.
     */
    private function render404(): string
    {
        return $this->viewRenderer->render('docs.404', [
            'title' => 'Documentation Not Found',
            'docsStructure' => $this->getDocsStructure(),
        ]);
    }
}
