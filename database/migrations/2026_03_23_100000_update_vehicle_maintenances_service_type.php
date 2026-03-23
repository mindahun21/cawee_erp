<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicle_maintenances') && !Schema::hasColumn('vehicle_maintenances', 'service_type_id')) {
            Schema::table('vehicle_maintenances', function (Blueprint $table) {
                $table->foreignId('service_type_id')->nullable()->constrained('vehicle_service_types')->onDelete('set null');
            });

            // Migrate data
            $maintenances = DB::table('vehicle_maintenances')->get();
            foreach ($maintenances as $m) {
                if (!empty($m->service_type)) {
                    DB::table('vehicle_service_types')->updateOrInsert(
                        ['name' => $m->service_type],
                        ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
                    );
                    
                    $newId = DB::table('vehicle_service_types')
                        ->where('name', $m->service_type)
                        ->value('id');
                        
                    DB::table('vehicle_maintenances')
                        ->where('id', $m->id)
                        ->update(['service_type_id' => $newId]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vehicle_maintenances')) {
            Schema::table('vehicle_maintenances', function (Blueprint $table) {
                $table->dropForeign(['service_type_id']);
                $table->dropColumn('service_type_id');
            });
        }
    }
};
