<?php

namespace App\Traits;

use App\Support\ModuleManager;

/**
 * Restricts access to a Filament Cluster based on the active module set.
 */
trait BelongsToModuleCluster
{
    public static function canAccess(): bool
    {
        if (! ModuleManager::isClusterEnabled(static::class)) {
            return false;
        }

        if (method_exists(parent::class, 'canAccess')) {
            return parent::canAccess();
        }

        return true;
    }
}
