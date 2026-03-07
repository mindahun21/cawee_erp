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
        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreignId('department_id')->nullable()->after('user_id')->constrained('departments')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->after('department_id')->constrained('projects')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->after('project_id')->constrained('locations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });
    }
};
