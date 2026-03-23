<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Migrate data from projects to hr_projects if projects table exists
        if (Schema::hasTable('projects') && Schema::hasTable('hr_projects')) {
            $projects = DB::table('projects')->get();
            foreach ($projects as $project) {
                // Determine the ID (it's project_id in the legacy table)
                $id = $project->project_id;
                
                if (!DB::table('hr_projects')->where('id', $id)->exists()) {
                    DB::table('hr_projects')->insert([
                        'id' => $id,
                        'project_name' => $project->project_name,
                        'project_code' => $project->project_code,
                        'location_id' => $project->location_id,
                        'created_at' => $project->created_at,
                        'updated_at' => $project->updated_at,
                    ]);
                }
            }
        }

        // 2. Fix asset_assignments foreign key
        if (Schema::hasTable('asset_assignments') && Schema::hasTable('hr_projects')) {
            Schema::table('asset_assignments', function (Blueprint $table) {
                try {
                    $table->dropForeign(['project_id']);
                } catch (\Exception $e) {
                    // Ignore if foreign key doesn't exist or cannot be dropped
                }
                
                // Point to the correct table hr_projects
                $table->foreign('project_id')->references('id')->on('hr_projects')->onDelete('set null');
            });
        }

        // 3. Do not drop legacy projects table automatically to avoid data loss.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback is complex as it involves recreating the legacy table structure and moving data back.
        // For simplicity and safety in this context, we leave it as is or focus on fixing the mismatch.
    }
};
