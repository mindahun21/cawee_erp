<?php

namespace App\Traits;

use App\Support\ModuleManager;

/**
 * Restricts access to a Filament Widget based on the active module set.
 */
trait BelongsToModuleWidget
{
    public static function canView(): bool
    {
        if (! ModuleManager::isWidgetEnabled(static::class)) {
            return false;
        }

        if (method_exists(parent::class, 'canView')) {
            return parent::canView();
        }

        return true;
    }
}
