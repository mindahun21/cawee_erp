<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. ITEMS: item_type
        if (Schema::hasTable('items') && !Schema::hasColumn('items', 'item_type_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->foreignId('item_type_id')->nullable()->constrained('item_types')->onDelete('set null');
            });

            $items = DB::table('items')->get();
            foreach ($items as $item) {
                if ($item->item_type) {
                    $itemTypeId = DB::table('item_types')->updateOrInsert(
                        ['name' => ucfirst(str_replace('_', ' ', $item->item_type))],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                    $newId = DB::table('item_types')->where('name', ucfirst(str_replace('_', ' ', $item->item_type)))->value('id');
                    DB::table('items')->where('id', $item->id)->update(['item_type_id' => $newId]);
                }
            }
        }

        // 2. MAINTENANCES: status, priority
        if (Schema::hasTable('maintenances')) {
            if (!Schema::hasColumn('maintenances', 'status_id')) {
                Schema::table('maintenances', function (Blueprint $table) {
                    $table->foreignId('status_id')->nullable()->constrained('maintenance_statuses')->onDelete('set null');
                });

                $maintenances = DB::table('maintenances')->get();
                foreach ($maintenances as $m) {
                    if ($m->status) {
                        DB::table('maintenance_statuses')->updateOrInsert(
                            ['name' => ucfirst($m->status)],
                            ['created_at' => now(), 'updated_at' => now()]
                        );
                        $newId = DB::table('maintenance_statuses')->where('name', ucfirst($m->status))->value('id');
                        DB::table('maintenances')->where('id', $m->id)->update(['status_id' => $newId]);
                    }
                }
            }

            if (!Schema::hasColumn('maintenances', 'priority_id')) {
                Schema::table('maintenances', function (Blueprint $table) {
                    $table->foreignId('priority_id')->nullable()->constrained('maintenance_priorities')->onDelete('set null');
                });

                $maintenances = DB::table('maintenances')->get();
                foreach ($maintenances as $m) {
                    if ($m->priority) {
                        DB::table('maintenance_priorities')->updateOrInsert(
                            ['name' => ucfirst($m->priority)],
                            ['created_at' => now(), 'updated_at' => now()]
                        );
                        $newId = DB::table('maintenance_priorities')->where('name', ucfirst($m->priority))->value('id');
                        DB::table('maintenances')->where('id', $m->id)->update(['priority_id' => $newId]);
                    }
                }
            }
        }

        // 3. SUPPLIERS: payment_terms
        if (Schema::hasTable('procurement_suppliers') && !Schema::hasColumn('procurement_suppliers', 'payment_term_id')) {
            Schema::table('procurement_suppliers', function (Blueprint $table) {
                $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->onDelete('set null');
            });

            $suppliers = DB::table('procurement_suppliers')->get();
            foreach ($suppliers as $s) {
                if ($s->payment_terms) {
                    DB::table('payment_terms')->updateOrInsert(
                        ['name' => $s->payment_terms],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                    $newId = DB::table('payment_terms')->where('name', $s->payment_terms)->value('id');
                    DB::table('procurement_suppliers')->where('id', $s->id)->update(['payment_term_id' => $newId]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('procurement_suppliers')) {
            Schema::table('procurement_suppliers', function (Blueprint $table) {
                $table->dropForeign(['payment_term_id']);
                $table->dropColumn('payment_term_id');
            });
        }
        if (Schema::hasTable('maintenances')) {
            Schema::table('maintenances', function (Blueprint $table) {
                $table->dropForeign(['status_id']);
                $table->dropForeign(['priority_id']);
                $table->dropColumn(['status_id', 'priority_id']);
            });
        }
        if (Schema::hasTable('items')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropForeign(['item_type_id']);
                $table->dropColumn('item_type_id');
            });
        }
    }
};
