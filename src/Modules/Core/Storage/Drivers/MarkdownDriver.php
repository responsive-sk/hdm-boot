<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Storage\Drivers;

use SplFileInfo;

/**
 * Markdown Driver.
 *
 * Handles Markdown files with YAML front-matter.
 * Inspired by Laravel Orbit package.
 */
class MarkdownDriver extends AbstractFileDriver
{
    /**
     * Content column name.
     */
    protected string $contentColumn = 'content';

    public function getExtension(): string
    {
        return 'md';
    }

    public function getContentColumn(): ?string
    {
        return $this->contentColumn;
    }

    /**
     * Set content column name.
     */
    public function setContentColumn(string $column): self
    {
        $this->contentColumn = $column;
        return $this;
    }

    public function parseFile(SplFileInfo $file): array
    {
        $content = file_get_contents($file->getPathname());
        if ($content === false) {
            return [];
        }

        return $this->parseMarkdownContent($content);
    }

    public function dumpContent(array $data): string
    {
        // Separate front-matter from content
        $frontMatter = $data;
        $content = $frontMatter[$this->contentColumn] ?? '';
        unset($frontMatter[$this->contentColumn]);

        // Remove internal fields
        unset($frontMatter['_file_path'], $frontMatter['_file_name'], $frontMatter['_modified_at']);

        // Generate YAML front-matter
        $yaml = $this->arrayToYaml($frontMatter);

        // Combine front-matter and content
        if (!empty($frontMatter)) {
            $contentString = is_string($content) ? $content : '';
            return "---\n" . $yaml . "---\n\n" . $contentString;
        }

        return is_string($content) ? $content : '';
    }

    /**
     * Parse Markdown content with YAML front-matter.
     *
     * @return array<string, mixed>
     */
    protected function parseMarkdownContent(string $content): array
    {
        $data = [];

        // Check for YAML front-matter
        if (str_starts_with($content, '---')) {
            $parts = preg_split('/^---\s*$/m', $content, 3);

            if (is_array($parts) && count($parts) >= 3) {
                // Parse YAML front-matter
                $yamlContent = trim($parts[1]);
                if (!empty($yamlContent)) {
                    $data = $this->parseYaml($yamlContent);
                }

                // Get Markdown content
                $markdownContent = trim($parts[2]);
                $data[$this->contentColumn] = $markdownContent;
            } else {
                // No valid front-matter, treat as pure content
                $data[$this->contentColumn] = $content;
            }
        } else {
            // No front-matter, treat as pure content
            $data[$this->contentColumn] = $content;
        }

        return $data;
    }

    /**
     * Parse YAML string to array.
     *
     * @return array<string, mixed>
     */
    protected function parseYaml(string $yaml): array
    {
        // Simple YAML parser for basic key-value pairs
        $data = [];
        $lines = explode("\n", $yaml);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Handle different value types
                $data[$key] = $this->parseYamlValue($value);
            }
        }

        return $data;
    }

    /**
     * Parse YAML value to appropriate PHP type.
     */
    protected function parseYamlValue(string $value): mixed
    {
        // Remove quotes
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            return substr($value, 1, -1);
        }

        // Boolean values
        if (in_array(strtolower($value), ['true', 'yes', 'on'])) {
            return true;
        }
        if (in_array(strtolower($value), ['false', 'no', 'off'])) {
            return false;
        }

        // Null values
        if (in_array(strtolower($value), ['null', '~', ''])) {
            return null;
        }

        // Numeric values
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        // Arrays (simple comma-separated)
        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            $arrayContent = substr($value, 1, -1);
            if (empty(trim($arrayContent))) {
                return [];
            }
            return array_map('trim', explode(',', $arrayContent));
        }

        // Default to string
        return $value;
    }

    /**
     * Convert array to YAML string.
     *
     * @param array<string, mixed> $data
     */
    protected function arrayToYaml(array $data): string
    {
        $yaml = '';

        foreach ($data as $key => $value) {
            $yaml .= $key . ': ' . $this->valueToYaml($value) . "\n";
        }

        return $yaml;
    }

    /**
     * Convert value to YAML format.
     */
    protected function valueToYaml(mixed $value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            if (empty($value)) {
                return '[]';
            }
            return '[' . implode(', ', array_map([$this, 'valueToYaml'], $value)) . ']';
        }

        if (is_string($value)) {
            // Quote strings that contain special characters
            if (preg_match('/[:\[\]{}|>]/', $value) || trim($value) !== $value) {
                return '"' . str_replace('"', '\\"', $value) . '"';
            }
            return $value;
        }

        return is_scalar($value) ? (string) $value : '';
    }
}
