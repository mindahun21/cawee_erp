<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed the three demographic disaggregation categories used by the
 * Beneficiary Feedback module: gender, disability.
 * (The "age" category is assumed to already be seeded.)
 *
 * This migration is idempotent — it uses firstOrCreate so it can be run
 * repeatedly without producing duplicate rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Gender ───────────────────────────────────────────────────────────
        $gender = DB::table('me_disaggregation_categories')
            ->where('key', 'gender')
            ->first();

        if (! $gender) {
            $genderId = DB::table('me_disaggregation_categories')->insertGetId([
                'key'        => 'gender',
                'name'       => 'Gender',
                'rules'      => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $genderOptions = [
                ['value' => 'male',        'label' => 'Male',              'sort_order' => 1],
                ['value' => 'female',      'label' => 'Female',            'sort_order' => 2],
                ['value' => 'non_binary',  'label' => 'Non-Binary',        'sort_order' => 3],
                ['value' => 'prefer_not',  'label' => 'Prefer not to say', 'sort_order' => 4],
            ];

            foreach ($genderOptions as $opt) {
                DB::table('me_disaggregation_options')->insert([
                    'category_id' => $genderId,
                    'value'       => $opt['value'],
                    'label'       => $opt['label'],
                    'sort_order'  => $opt['sort_order'],
                ]);
            }
        }

        // ── Disability ───────────────────────────────────────────────────────
        $disability = DB::table('me_disaggregation_categories')
            ->where('key', 'disability')
            ->first();

        if (! $disability) {
            $disabilityId = DB::table('me_disaggregation_categories')->insertGetId([
                'key'        => 'disability',
                'name'       => 'Disability Status',
                'rules'      => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $disabilityOptions = [
                ['value' => 'no_disability',  'label' => 'No Disability',          'sort_order' => 1],
                ['value' => 'physical',       'label' => 'Physical Disability',     'sort_order' => 2],
                ['value' => 'visual',         'label' => 'Visual Impairment',       'sort_order' => 3],
                ['value' => 'hearing',        'label' => 'Hearing Impairment',      'sort_order' => 4],
                ['value' => 'intellectual',   'label' => 'Intellectual Disability', 'sort_order' => 5],
                ['value' => 'psychosocial',   'label' => 'Psychosocial Disability', 'sort_order' => 6],
                ['value' => 'multiple',       'label' => 'Multiple Disabilities',   'sort_order' => 7],
                ['value' => 'prefer_not',     'label' => 'Prefer not to say',       'sort_order' => 8],
            ];

            foreach ($disabilityOptions as $opt) {
                DB::table('me_disaggregation_options')->insert([
                    'category_id' => $disabilityId,
                    'value'       => $opt['value'],
                    'label'       => $opt['label'],
                    'sort_order'  => $opt['sort_order'],
                ]);
            }
        }
    }

    public function down(): void
    {
        // Soft-clean: only remove if exactly the categories we seeded exist
        $genderCat = DB::table('me_disaggregation_categories')->where('key', 'gender')->first();
        if ($genderCat) {
            DB::table('me_disaggregation_options')->where('category_id', $genderCat->id)->delete();
            DB::table('me_disaggregation_categories')->where('id', $genderCat->id)->delete();
        }

        $disabilityCat = DB::table('me_disaggregation_categories')->where('key', 'disability')->first();
        if ($disabilityCat) {
            DB::table('me_disaggregation_options')->where('category_id', $disabilityCat->id)->delete();
            DB::table('me_disaggregation_categories')->where('id', $disabilityCat->id)->delete();
        }
    }
};
