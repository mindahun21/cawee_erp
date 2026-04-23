<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);                              // e.g. "July 2026"
            $table->smallInteger('fiscal_year');                     // e.g. 2026
            $table->tinyInteger('period_number');                    // 1–13 (Ethiopian cal has 13 months)
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed', 'locked'])->default('open');
            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year', 'period_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_accounting_periods');
    }
};
