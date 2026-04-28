<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_journal_entry_lines', function (Blueprint $table) {
            $table->foreignId('supplier_id')
                ->nullable()
                ->after('project_id')
                ->constrained('procurement_suppliers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_journal_entry_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });
    }
};
