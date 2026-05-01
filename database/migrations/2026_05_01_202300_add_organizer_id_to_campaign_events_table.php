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
        if (Schema::hasTable('campaign_events') && !Schema::hasColumn('campaign_events', 'organizer_id')) {
            Schema::table('campaign_events', function (Blueprint $table) {
                // Ensure the referenced table 'employees' exists before adding constraint
                if (Schema::hasTable('employees')) {
                    $table->foreignId('organizer_id')
                        ->nullable()
                        ->after('status')
                        ->constrained('employees')
                        ->nullOnDelete();
                } else {
                    // Fallback to simple integer if employees table isn't found (rare but safe)
                    $table->unsignedBigInteger('organizer_id')->nullable()->after('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('campaign_events') && Schema::hasColumn('campaign_events', 'organizer_id')) {
            Schema::table('campaign_events', function (Blueprint $table) {
                // MySQL requires explicit foreign key drop before column drop
                try {
                    $table->dropForeign(['organizer_id']);
                } catch (\Exception $e) {
                    // Ignore if foreign key doesn't exist or already dropped
                }
                $table->dropColumn('organizer_id');
            });
        }
    }
};
