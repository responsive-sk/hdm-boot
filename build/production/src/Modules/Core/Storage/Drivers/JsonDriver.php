<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Storage\Drivers;

use SplFileInfo;

/**
 * JSON Driver.
 *
 * Handles JSON files for structured data storage.
 */
class JsonDriver extends AbstractFileDriver
{
    public function getExtension(): string
    {
        return 'json';
    }

    public function parseFile(SplFileInfo $file): array
    {
        $content = file_get_contents($file->getPathname());
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return [];
        }

        /** @var array<string, mixed> $typedData */
        $typedData = $data;

        return $typedData;
    }

    public function dumpContent(array $data): string
    {
        // Remove internal fields
        $cleanData = $data;
        unset($cleanData['_file_path'], $cleanData['_file_name'], $cleanData['_modified_at']);

        $result = json_encode($cleanData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $result !== false ? $result : '{}';
    }
}
