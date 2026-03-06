<?php

namespace Database\Seeders;

use App\Models\ContractType;
use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\FieldOfStudy;
use App\Models\JobPosition;
use App\Models\LayoffChecklistItem;
use App\Models\TrainingType;
use Illuminate\Database\Seeder;

/**
 * Seeds sensible defaults for all HR Settings lookup tables.
 * Safe to re-run — uses firstOrCreate throughout.
 */
class HrSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Departments ──────────────────────────────────────────────
        $departments = [
            ['name' => 'Human Resources',       'code' => 'HR'],
            ['name' => 'Finance & Accounting',  'code' => 'FIN'],
            ['name' => 'Operations',             'code' => 'OPS'],
            ['name' => 'Programs',               'code' => 'PRG'],
            ['name' => 'Administration',         'code' => 'ADM'],
            ['name' => 'Information Technology', 'code' => 'IT'],
            ['name' => 'Legal & Compliance',     'code' => 'LEG'],
        ];

        foreach ($departments as $d) {
            Department::firstOrCreate(['name' => $d['name']], $d);
        }

        $hr  = Department::where('name', 'Human Resources')->first();
        $fin = Department::where('name', 'Finance & Accounting')->first();
        $ops = Department::where('name', 'Operations')->first();
        $prg = Department::where('name', 'Programs')->first();
        $adm = Department::where('name', 'Administration')->first();
        $it  = Department::where('name', 'Information Technology')->first();

        // ── Job Positions ────────────────────────────────────────────
        $positions = [
            ['department_id' => $hr?->id,  'title' => 'HR Director',        'grade' => 'M5'],
            ['department_id' => $hr?->id,  'title' => 'HR Officer',          'grade' => 'P3'],
            ['department_id' => $hr?->id,  'title' => 'HR Coordinator',      'grade' => 'P2'],
            ['department_id' => $fin?->id, 'title' => 'Finance Manager',     'grade' => 'M4'],
            ['department_id' => $fin?->id, 'title' => 'Accountant',          'grade' => 'P3'],
            ['department_id' => $fin?->id, 'title' => 'Finance Officer',     'grade' => 'P2'],
            ['department_id' => $ops?->id, 'title' => 'Operations Manager',  'grade' => 'M4'],
            ['department_id' => $ops?->id, 'title' => 'Field Officer',       'grade' => 'P2'],
            ['department_id' => $prg?->id, 'title' => 'Program Manager',     'grade' => 'M4'],
            ['department_id' => $prg?->id, 'title' => 'Program Officer',     'grade' => 'P3'],
            ['department_id' => $adm?->id, 'title' => 'Executive Director',  'grade' => 'M7'],
            ['department_id' => $adm?->id, 'title' => 'Office Manager',      'grade' => 'M3'],
            ['department_id' => $it?->id,  'title' => 'IT Manager',          'grade' => 'M4'],
            ['department_id' => $it?->id,  'title' => 'System Administrator','grade' => 'P3'],
            ['department_id' => null,       'title' => 'Driver',              'grade' => 'S2'],
            ['department_id' => null,       'title' => 'Cleaner',             'grade' => 'S1'],
            ['department_id' => null,       'title' => 'Security Guard',      'grade' => 'S1'],
        ];

        foreach ($positions as $p) {
            JobPosition::firstOrCreate(['title' => $p['title']], $p);
        }

        // ── Contract Types ───────────────────────────────────────────
        $contractTypes = [
            'Permanent',
            'Fixed-Term Contract',
            'Temporary',
            'Consultancy / Service',
            'Volunteer',
            'Internship',
        ];

        foreach ($contractTypes as $ct) {
            ContractType::firstOrCreate(['name' => $ct], ['name' => $ct, 'is_active' => true]);
        }

        // ── Education Levels ─────────────────────────────────────────
        $levels = [
            ['name' => 'Certificate',          'sort_order' => 1],
            ['name' => 'Diploma',              'sort_order' => 2],
            ['name' => "Bachelor's Degree",    'sort_order' => 3],
            ['name' => "Master's Degree",      'sort_order' => 4],
            ['name' => 'PhD / Doctorate',      'sort_order' => 5],
            ['name' => 'Professional Degree',  'sort_order' => 6],
            ['name' => 'High School / TVET',   'sort_order' => 7],
            ['name' => 'Other',                'sort_order' => 8],
        ];

        foreach ($levels as $l) {
            EducationLevel::firstOrCreate(['name' => $l['name']], $l);
        }

        // ── Fields of Study ──────────────────────────────────────────
        $fields = [
            'Accounting & Finance', 'Business Administration', 'Computer Science',
            'Information Technology', 'Civil Engineering', 'Electrical Engineering',
            'Human Resource Management', 'Public Health', 'Social Work',
            'Development Studies', 'Economics', 'Law', 'Education',
            'Agriculture', 'Natural Resources Management', 'Other',
        ];

        foreach ($fields as $f) {
            FieldOfStudy::firstOrCreate(['name' => $f]);
        }

        // ── Training Types ───────────────────────────────────────────
        $trainingTypes = [
            'Technical / Professional', 'Leadership & Management',
            'Compliance & Legal', 'Safety & Health', 'Orientation / Induction',
            'Soft Skills', 'Digital Literacy', 'Other',
        ];

        foreach ($trainingTypes as $tt) {
            TrainingType::firstOrCreate(['name' => $tt]);
        }

        // ── Layoff Checklist Items ───────────────────────────────────
        $checklist = [
            ['title' => 'Submit resignation letter or receive notice',    'responsible_party' => 'HR',       'sort_order' => 1],
            ['title' => 'Clearance from Finance — settle any advances',   'responsible_party' => 'Finance',  'sort_order' => 2],
            ['title' => 'Return all company assets (laptop, phone, etc)', 'responsible_party' => 'IT',       'sort_order' => 3],
            ['title' => 'Revoke system & email access',                   'responsible_party' => 'IT',       'sort_order' => 4],
            ['title' => 'Hand over projects and documentation',           'responsible_party' => 'Manager',  'sort_order' => 5],
            ['title' => 'Exit interview with HR',                         'responsible_party' => 'HR',       'sort_order' => 6],
            ['title' => 'Process final payslip and severance',            'responsible_party' => 'Finance',  'sort_order' => 7],
            ['title' => 'Issue experience letter',                        'responsible_party' => 'HR',       'sort_order' => 8],
            ['title' => 'Collect all ID cards and badges',                'responsible_party' => 'HR',       'sort_order' => 9],
        ];

        foreach ($checklist as $item) {
            LayoffChecklistItem::firstOrCreate(
                ['title' => $item['title']],
                array_merge($item, ['is_active' => true])
            );
        }

        $this->command->info('  HR Settings seeded: departments, positions, contract types, education levels, fields of study, training types, layoff checklist.');
    }
}
