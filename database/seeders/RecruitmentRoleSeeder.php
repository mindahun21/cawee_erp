<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Recruitment Role Seeder
 *
 * Ensures all recruitment model permissions exist and grants them
 * to the existing HR roles (hr_director, hr_officer, hr_supervisor, hr_staff).
 *
 * Candidate role will use a separate guard (not web) — handled separately.
 *
 * Run with: php artisan db:seed --class=RecruitmentRoleSeeder
 */
class RecruitmentRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles & permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ── Ensure recruitment permissions exist ─────────────────────

        $this->ensureRecruitmentPermissionsExist();

        if (Permission::count() === 0) {
            $this->command->warn('No permissions found. Run: php artisan shield:generate --all --panel=admin');
            $this->command->warn('Then re-run this seeder.');
            return;
        }

        // ── Define permission groups ─────────────────────────────────

        $allRecruitmentPermissions = Permission::where('name', 'like', '%Recruitment%')
            ->pluck('name')
            ->unique()
            ->toArray();

        $readOnlyRecruitmentPermissions = Permission::where('name', 'like', 'View%Recruitment%')
            ->pluck('name')
            ->unique()
            ->toArray();

        // ── Update existing HR roles with recruitment permissions ─────

        // hr_director: full access to all recruitment resources
        Role::where('name', 'hr_director')->first()?->givePermissionTo($allRecruitmentPermissions);

        // hr_officer: full access to all recruitment resources (recruiter)
        Role::where('name', 'hr_officer')->first()?->givePermissionTo($allRecruitmentPermissions);

        // hr_supervisor: read-only recruitment access (hiring manager)
        Role::where('name', 'hr_supervisor')->first()?->givePermissionTo($readOnlyRecruitmentPermissions);

        // hr_staff: read-only recruitment access
        Role::where('name', 'hr_staff')->first()?->givePermissionTo($readOnlyRecruitmentPermissions);

        // ── Output ───────────────────────────────────────────────────

        $this->command->info(' Recruitment roles seeded:');
        $this->command->table(
            ['Role', 'Guard', 'Permissions'],
            [
                ['hr_director',   'web', count($allRecruitmentPermissions) . ' (recruitment)'],
                ['hr_officer',    'web', count($allRecruitmentPermissions) . ' (recruitment)'],
                ['hr_supervisor', 'web', count($readOnlyRecruitmentPermissions) . ' (recruitment)'],
                ['hr_staff',      'web', count($readOnlyRecruitmentPermissions) . ' (recruitment)'],
            ]
        );
    }

    /**
     * Ensure all recruitment model permissions exist in the DB.
     *
     * Follows the Action:Resource naming pattern used by Filament Shield.
     */
    private function ensureRecruitmentPermissionsExist(): void
    {
        $models = [
            'RecruitmentPlan', 'RecruitmentSkill', 'RecruitmentSkillCategory',
            'RecruitmentApplication', 'RecruitmentCampaign', 'RecruitmentCandidate',
            'RecruitmentChannel', 'RecruitmentEvaluationCriteria', 'RecruitmentEvaluationForm',
            'RecruitmentEvaluationScore', 'RecruitmentInterview', 'RecruitmentJobPosting',
            'RecruitmentOffer', 'RecruitmentInterviewSchedule',
            'RecruitmentScheduleCandidate', 'RecruitmentScheduleInterviewer',
        ];

        $actions = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'ForceDelete', 'Replicate', 'Reorder'];

        foreach ($models as $model) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action}:{$model}", 'guard_name' => 'web']);
            }
        }

        Permission::firstOrCreate(['name' => 'View:RecruitmentDashboard', 'guard_name' => 'web']);
    }
}
