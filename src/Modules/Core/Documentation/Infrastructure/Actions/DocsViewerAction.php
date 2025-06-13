<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Documentation\Infrastructure\Actions;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Documentation Viewer Action.
 *
 * Provides a web interface for viewing project documentation.
 */
final class DocsViewerAction
{
    private readonly string $docsPath;

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly LoggerInterface $logger,
        private readonly Paths $paths
    ) {
        $this->docsPath = $this->paths->base() . '/docs';
    }

    /**
     * Handle documentation viewer request.
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getAttribute('path', '');

        try {
            if (empty($path) || $path === '/') {
                return $this->renderDocsIndex();
            }

            if (str_ends_with($path, '.md')) {
                return $this->renderMarkdownFile($path);
            }

            return $this->renderDocsIndex();
        } catch (\Exception $e) {
            $this->logger->error('Documentation viewer error', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);

            return $this->createErrorResponse('Documentation not found');
        }
    }

    /**
     * Render documentation index.
     */
    private function renderDocsIndex(): ResponseInterface
    {
        $docsStructure = $this->getDocsStructure();

        $html = $this->renderHtmlTemplate('Documentation Index', $this->renderDocsTree($docsStructure));

        $response = $this->responseFactory->createResponse(200);
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($html);

        return $response;
    }

    /**
     * Render markdown file.
     */
    private function renderMarkdownFile(string $path): ResponseInterface
    {
        $filePath = $this->docsPath . '/' . ltrim($path, '/');

        if (!file_exists($filePath) || !is_file($filePath)) {
            return $this->createErrorResponse('Documentation file not found');
        }

        $content = file_get_contents($filePath);
        $htmlContent = $this->convertMarkdownToHtml($content);

        $title = $this->extractTitleFromMarkdown($content) ?: basename($path, '.md');
        $html = $this->renderHtmlTemplate($title, $htmlContent);

        $response = $this->responseFactory->createResponse(200);
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($html);

        return $response;
    }

    /**
     * Get documentation structure.
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
                $structure[] = [
                    'path'  => $relativePath,
                    'name'  => $file->getBasename('.md'),
                    'title' => $this->extractTitleFromFile($file->getPathname()),
                ];
            }
        }

        return $structure;
    }

    /**
     * Render documentation tree.
     */
    private function renderDocsTree(array $structure): string
    {
        $html = '<div class="docs-tree">';
        $html .= '<h2>📚 Project Documentation</h2>';

        // Group by directory
        $grouped = [];
        foreach ($structure as $doc) {
            $dir = dirname($doc['path']);
            if ($dir === '.') {
                $dir = 'Root';
            }
            $grouped[$dir][] = $doc;
        }

        foreach ($grouped as $directory => $docs) {
            $html .= "<h3>📁 {$directory}</h3>";
            $html .= '<ul class="docs-list">';

            foreach ($docs as $doc) {
                $title = $doc['title'] ?: $doc['name'];
                $html .= "<li><a href=\"/docs/{$doc['path']}\">{$title}</a></li>";
            }

            $html .= '</ul>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Convert markdown to HTML.
     */
    private function convertMarkdownToHtml(string $markdown): string
    {
        // Simple markdown to HTML conversion
        // In production, you might want to use a proper markdown parser like Parsedown

        $html = $markdown;

        // Headers
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $html);

        // Code blocks
        $html = preg_replace('/```(\w+)?\n(.*?)\n```/s', '<pre><code class="language-$1">$2</code></pre>', $html);

        // Inline code
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

        // Bold and italic
        $html = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $html);

        // Links
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);

        // Tables (markdown format)
        $html = $this->convertMarkdownTables($html);

        // Lists
        $html = preg_replace('/^- (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);

        // Paragraphs
        $html = preg_replace('/\n\n/', '</p><p>', $html);
        $html = '<p>' . $html . '</p>';

        // Clean up
        $html = str_replace('<p></p>', '', $html);
        $html = str_replace('<p><h', '<h', $html);
        $html = str_replace('</h1></p>', '</h1>', $html);
        $html = str_replace('</h2></p>', '</h2>', $html);
        $html = str_replace('</h3></p>', '</h3>', $html);
        $html = str_replace('</h4></p>', '</h4>', $html);
        $html = str_replace('<p><ul>', '<ul>', $html);
        $html = str_replace('</ul></p>', '</ul>', $html);
        $html = str_replace('<p><pre>', '<pre>', $html);
        $html = str_replace('</pre></p>', '</pre>', $html);

        return $html;
    }

    /**
     * Convert markdown tables to HTML.
     */
    private function convertMarkdownTables(string $html): string
    {
        // Match markdown table pattern
        $pattern = '/\|(.+)\|\n\|[-\s\|:]+\|\n((?:\|.+\|\n?)+)/m';

        return preg_replace_callback($pattern, function ($matches) {
            $headerRow = trim($matches[1]);
            $dataRows = trim($matches[2]);

            // Parse header
            $headers = array_map('trim', explode('|', $headerRow));
            $headers = array_filter($headers); // Remove empty elements

            // Parse data rows
            $rows = explode("\n", $dataRows);
            $tableRows = [];

            foreach ($rows as $row) {
                $row = trim($row);
                if (empty($row)) {
                    continue;
                }

                $cells = array_map('trim', explode('|', $row));
                $cells = array_filter($cells); // Remove empty elements
                $tableRows[] = $cells;
            }

            // Build HTML table
            $tableHtml = '<table class="docs-table">';

            // Header
            $tableHtml .= '<thead><tr>';
            foreach ($headers as $header) {
                $tableHtml .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $tableHtml .= '</tr></thead>';

            // Body
            $tableHtml .= '<tbody>';
            foreach ($tableRows as $row) {
                $tableHtml .= '<tr>';
                foreach ($row as $cell) {
                    $formattedCell = $this->formatTableCell($cell);
                    $tableHtml .= '<td>' . $formattedCell . '</td>';
                }
                $tableHtml .= '</tr>';
            }
            $tableHtml .= '</tbody></table>';

            return $tableHtml;
        }, $html);
    }

    /**
     * Format table cell content with special styling.
     */
    private function formatTableCell(string $cell): string
    {
        $cell = trim($cell);

        // Handle status badges
        if (str_contains($cell, '✅ Default')) {
            return '<span class="status-badge status-default">✅ Default</span>';
        }
        if (str_contains($cell, '✅ Enabled')) {
            return '<span class="status-badge status-enabled">✅ Enabled</span>';
        }
        if (str_contains($cell, '⚙️ Configurable')) {
            return '<span class="status-badge status-configurable">⚙️ Configurable</span>';
        }

        // Handle emoji flags and other emojis
        if (preg_match('/^(🇺🇸|🇸🇰|🇨🇿|🇩🇪|🇫🇷|🇪🇸|🇮🇹|🇵🇱|[🎯🔧🌐📚🏗️🎉✨🔍📝📊🏥🚀])/', $cell)) {
            return '<span class="emoji">' . htmlspecialchars($cell) . '</span>';
        }

        return htmlspecialchars($cell);
    }

    /**
     * Extract title from markdown content.
     */
    private function extractTitleFromMarkdown(string $content): ?string
    {
        if (preg_match('/^# (.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Extract title from file.
     */
    private function extractTitleFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        return $this->extractTitleFromMarkdown($content);
    }

    /**
     * Render HTML template.
     */
    private function renderHtmlTemplate(string $title, string $content): string
    {
        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>{$title} - MVA Bootstrap Documentation</title>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background: #f5f5f5; color: #333; }
                    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); color: #333; }
                    h1 { color: #1a1a1a; font-size: 2.5em; font-weight: 700; border-bottom: 4px solid #3498db; padding-bottom: 15px; margin-bottom: 25px; text-shadow: 0 1px 2px rgba(0,0,0,0.1); }
                    h2 { color: #2c3e50; font-size: 1.8em; font-weight: 600; border-bottom: 2px solid #ecf0f1; padding-bottom: 8px; margin-top: 30px; margin-bottom: 15px; }
                    h3 { color: #34495e; font-size: 1.4em; font-weight: 500; margin-top: 25px; margin-bottom: 12px; }
                    code { background: #f8f9fa; color: #e74c3c; padding: 3px 6px; border-radius: 4px; font-family: 'Monaco', 'Consolas', 'Courier New', monospace; font-size: 0.9em; border: 1px solid #e1e8ed; }
                    pre { background: #2c3e50; color: #ecf0f1; padding: 20px; border-radius: 8px; overflow-x: auto; margin: 15px 0; border-left: 4px solid #3498db; }
                    pre code { background: none; padding: 0; color: #ecf0f1; border: none; }
                    .docs-tree { margin: 20px 0; }
                    .docs-list { list-style: none; padding-left: 20px; }
                    .docs-list li { margin: 8px 0; }
                    .docs-list a { color: #3498db; text-decoration: none; padding: 5px 10px; border-radius: 3px; display: inline-block; }
                    .docs-list a:hover { background: #ecf0f1; }
                    .nav { margin-bottom: 20px; padding: 10px; background: #ecf0f1; border-radius: 5px; }
                    .nav a { color: #3498db; text-decoration: none; margin-right: 15px; }
                    .nav a:hover { text-decoration: underline; }
                    ul { padding-left: 20px; }
                    li { margin: 5px 0; }
                    strong { color: #2c3e50; }
                    em { color: #7f8c8d; }
                    table, .docs-table { border-collapse: collapse; width: 100%; margin: 20px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
                    th, td { border: 1px solid #e1e8ed; padding: 12px 16px; text-align: left; vertical-align: top; }
                    th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 600; text-transform: uppercase; font-size: 0.85em; letter-spacing: 0.5px; }
                    tbody tr { transition: background-color 0.2s ease; }
                    tbody tr:nth-child(even) { background-color: #f8f9fa; }
                    tbody tr:hover { background-color: #e3f2fd; }
                    td { font-size: 0.9em; line-height: 1.5; }
                    .docs-table td:first-child { font-weight: 500; }
                    .docs-table td { border-left: none; border-right: none; }
                    .docs-table td:first-child { border-left: 1px solid #e1e8ed; }
                    .docs-table td:last-child { border-right: 1px solid #e1e8ed; }
                    .emoji { font-size: 1.2em; margin-right: 8px; }
                    .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: 500; }
                    .status-default { background: #4caf50; color: white; }
                    .status-enabled { background: #2196f3; color: white; }
                    .status-configurable { background: #ff9800; color: white; }

                    /* Dark mode compatibility */
                    @media (prefers-color-scheme: dark) {
                        body { background: #1a1a1a; color: #e0e0e0; }
                        .container { background: #2d2d2d; color: #e0e0e0; box-shadow: 0 2px 10px rgba(0,0,0,0.3); }
                        h1 { color: #ffffff; text-shadow: 0 1px 2px rgba(0,0,0,0.3); border-bottom-color: #4a90e2; }
                        h2 { color: #e0e0e0; border-bottom-color: #404040; }
                        h3 { color: #b0b0b0; }
                        code { background: #404040; color: #ff6b6b; border-color: #555; }
                        pre { background: #1e1e1e; border-left-color: #4a90e2; }
                        .nav { background: #404040; }
                        .nav a { color: #4a90e2; }
                        .docs-list a { color: #4a90e2; }
                        .docs-list a:hover { background: #404040; }
                        table, .docs-table { box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
                        th, td { border-color: #555; }
                        tbody tr:nth-child(even) { background-color: #333; }
                        tbody tr:hover { background-color: #404040; }
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="nav">
                        <a href="/docs">📚 Documentation Home</a>
                        <a href="/docs/README.md">📖 Main README</a>
                        <a href="/docs/architecture/README.md">🏗️ Architecture</a>
                        <a href="/_status">🏥 Health Status</a>
                    </div>
                    <h1>{$title}</h1>
                    {$content}
                </div>
            </body>
            </html>
            HTML;
    }

    /**
     * Create error response.
     */
    private function createErrorResponse(string $message): ResponseInterface
    {
        $html = $this->renderHtmlTemplate('Error', "<p style='color: red;'>{$message}</p>");

        $response = $this->responseFactory->createResponse(404);
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($html);

        return $response;
    }
}
