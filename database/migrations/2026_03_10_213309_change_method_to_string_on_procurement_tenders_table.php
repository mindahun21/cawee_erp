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
        Schema::table('procurement_tenders', function (Blueprint $table) {
            // SQLite does not support changing Enum easily via modifying, but in typical DBs this converts an enum to a string
            $table->string('method')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurement_tenders', function (Blueprint $table) {
            $table->string('method', 255)->change();
        });
    }
};
