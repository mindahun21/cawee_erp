<?php

namespace App\Support;

/**
 * Resolves which ERP modules are active for the current deployment.
 *
 * Module availability is determined by the ENABLED_MODULES environment
 * variable. When the variable is absent or empty, every module defined
 * in config/modules.php is treated as enabled.
 */
class ModuleManager
{
    /** @var string[]|null Cached list of enabled module keys (null = all). */
    protected static ?array $resolved = null;

    /**
     * Determine whether a given module is active.
     */
    public static function isEnabled(string $module): bool
    {
        $modules = static::resolve();

        if ($modules === null) {
            return true;
        }

        return in_array($module, $modules, true);
    }

    /**
     * Determine whether a Filament resource class belongs to an active module.
     *
     * Resources whose namespace does not match any module definition are
     * assumed to be core resources and are always accessible.
     */
    public static function isResourceEnabled(string $resourceClass): bool
    {
        $modules = static::resolve();

        if ($modules === null) {
            return true;
        }

        foreach (config('modules.definitions', []) as $key => $definition) {
            foreach ($definition['resource_namespaces'] ?? [] as $ns) {
                if (str_starts_with($resourceClass, $ns)) {
                    return in_array($key, $modules, true);
                }
            }
        }

        return true;
    }

    /**
     * Determine whether a Filament page class belongs to an active module.
     *
     * Pages not explicitly registered to any module are always accessible.
     */
    public static function isPageEnabled(string $pageClass): bool
    {
        $modules = static::resolve();

        if ($modules === null) {
            return true;
        }

        foreach (config('modules.definitions', []) as $key => $definition) {
            foreach ($definition['pages'] ?? [] as $page) {
                if ($pageClass === $page) {
                    return in_array($key, $modules, true);
                }
            }
        }

        return true;
    }

    /**
     * Determine whether a Filament widget class belongs to an active module.
     *
     * Widgets whose namespace does not match any module definition are
     * always visible.
     */
    public static function isWidgetEnabled(string $widgetClass): bool
    {
        $modules = static::resolve();

        if ($modules === null) {
            return true;
        }

        foreach (config('modules.definitions', []) as $key => $definition) {
            foreach ($definition['widget_namespaces'] ?? [] as $ns) {
                if (str_starts_with($widgetClass, $ns)) {
                    return in_array($key, $modules, true);
                }
            }
        }

        return true;
    }

    /**
     * Determine whether a Filament cluster class belongs to an active module.
     */
    public static function isClusterEnabled(string $clusterClass): bool
    {
        $modules = static::resolve();

        if ($modules === null) {
            return true;
        }

        foreach (config('modules.definitions', []) as $key => $definition) {
            foreach ($definition['clusters'] ?? [] as $cluster) {
                if ($clusterClass === $cluster) {
                    return in_array($key, $modules, true);
                }
            }
        }

        return true;
    }

    /**
     * Return only the navigation groups that belong to active modules.
     *
     * Core groups (e.g. System Administration) are always included.
     */
    public static function getActiveNavigationGroups(): array
    {
        $groups = [];

        foreach (config('modules.definitions', []) as $key => $definition) {
            if (static::isEnabled($key)) {
                $groups = array_merge($groups, $definition['navigation_groups'] ?? []);
            }
        }

        // Core groups that are not tied to any toggleable module.
        $groups[] = 'System Administration';
        $groups[] = 'Settings';
        $groups[] = 'Filament Shield';

        return array_unique($groups);
    }

    /**
     * Resolve the list of enabled module keys from configuration.
     *
     * @return string[]|null Null indicates all modules are enabled.
     */
    protected static function resolve(): ?array
    {
        if (static::$resolved !== null) {
            return static::$resolved;
        }

        $raw = config('modules.enabled');

        if ($raw === null || $raw === '') {
            return null;
        }

        static::$resolved = array_map('trim', explode(',', $raw));

        return static::$resolved;
    }

    /**
     * Reset the cached resolution (useful in tests).
     */
    public static function flush(): void
    {
        static::$resolved = null;
    }
}
