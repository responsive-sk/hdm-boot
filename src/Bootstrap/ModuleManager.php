<?php

declare(strict_types=1);

namespace MvaBootstrap\Bootstrap;

use MvaBootstrap\SharedKernel\Services\PathsFactory;

/**
 * Simplified Module Manager.
 *
 * Handles loading of core application modules with minimal complexity.
 */
final class ModuleManager
{
    /** @var array<string> */
    private array $coreModules = ['User', 'Security', 'Language'];

    /** @var array<string> */
    private array $loadedModules = [];

    /**
     * Load core modules.
     */
    public function loadCoreModules(): void
    {
        $paths = PathsFactory::create();

        foreach ($this->coreModules as $moduleName) {
            $modulePath = $paths->getPath($paths->src('Modules'), "Core/{$moduleName}");

            if (is_dir($modulePath)) {
                $this->loadedModules[] = $moduleName;
            }
        }
    }

    /**
     * Get loaded modules.
     *
     * @return array<string>
     */
    public function getLoadedModules(): array
    {
        return $this->loadedModules;
    }

    /**
     * Check if module is loaded.
     */
    public function isModuleLoaded(string $moduleName): bool
    {
        return in_array($moduleName, $this->loadedModules, true);
    }
}
