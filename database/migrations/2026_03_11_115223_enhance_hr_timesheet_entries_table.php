<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hr_timesheet_entries', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->change();
            $table->foreignId('location_id')->nullable()->after('project_id')->constrained('hr_locations')->nullOnDelete();
            $table->text('description')->nullable()->after('hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_timesheet_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->dropColumn('description');
            $table->foreignId('project_id')->nullable(false)->change();
        });
    }
};
