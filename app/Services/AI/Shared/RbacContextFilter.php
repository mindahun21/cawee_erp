<?php

namespace App\Services\AI\Shared;

use App\Models\User;

class RbacContextFilter
{
    /**
     * Builds the system prompt preamble based on user permissions.
     */
    public function buildPermissionPreamble(User $user): string
    {
        $modules = $this->allowedModules($user);
        $moduleList = empty($modules) ? 'None' : implode(', ', $modules);

        return <<<PROMPT
You are assisting {$user->name}, who has access to the following ERP modules: {$moduleList}.
DO NOT reference data, suggest actions, or generate queries outside of these explicitly allowed modules.
If the user asks for something outside these modules, politely inform them they lack permission.
PROMPT;
    }

    /**
     * Returns the list of module keys this user can access.
     */
    public function allowedModules(User $user): array
    {
        // Check if user is a super admin
        $roles = $user->roles->pluck('name')->toArray();
        
        if (in_array('super_admin', $roles) || in_array('admin', $roles) || in_array('Admin', $roles) || $user->email === 'admin@elisoft.com') {
            return ['All Modules (Super Admin Access)'];
        }

        // Dynamically add their roles to context
        foreach ($roles as $role) {
            $modules[] = ucwords(str_replace('_', ' ', $role));
        }

        // Pass their explicit direct permissions to the LLM to understand micro-access
        $permissions = $user->getDirectPermissions()->pluck('name')->toArray();
        foreach ($permissions as $perm) {
            $modules[] = $perm;
        }
        
        // Ensure array is unique
        return array_unique($modules);
    }
}
