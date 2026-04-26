<?php

namespace App\Traits;

use App\Support\ModuleManager;

/**
 * Restricts access to a Filament resource based on the active module set.
 *
 * Apply this trait to any Resource class. It checks the resource's namespace
 * against the module registry and hides the resource when its parent module
 * is disabled for the current deployment.
 */
trait BelongsToModule
{
    public static function canAccess(): bool
    {
        if (! ModuleManager::isResourceEnabled(static::class)) {
            return false;
        }

        return parent::canAccess();
    }
}
