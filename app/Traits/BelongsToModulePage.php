<?php

namespace App\Traits;

use App\Support\ModuleManager;

/**
 * Restricts access to a Filament Page based on the active module set.
 */
trait BelongsToModulePage
{
    public static function canAccess(): bool
    {
        if (! ModuleManager::isPageEnabled(static::class)) {
            return false;
        }

        // Only call parent if it exists to avoid errors on pages that don't extend a base with canAccess
        if (method_exists(parent::class, 'canAccess')) {
            return parent::canAccess();
        }

        return true;
    }
}
