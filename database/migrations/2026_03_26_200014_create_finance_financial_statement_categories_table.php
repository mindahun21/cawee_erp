<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 2 — Chart of Accounts & General Ledger
     *
     * Financial Statement Categories define where each Chart of Account entry
     * appears in statutory reports:
     *   • Balance Sheet   (asset, liability, equity sections)
     *   • Income Statement (income, expense sections)
     *   • Cash Flow Statement (operating, investing, financing activities)
     *
     * The self-referential parent_id allows nested grouping, e.g.:
     *   Balance Sheet → Current Assets → Cash & Cash Equivalents
     */
    public function up(): void
    {
        Schema::create('finance_financial_statement_categories', function (Blueprint $table) {
            $table->id();

            // ── Identity ──────────────────────────────────────────────────────
            $table->string('code', 20)->unique()
                ->comment('Short mnemonic, e.g. BS-CA, IS-PE, CF-OA');

            $table->string('name', 120)
                ->comment('Display name, e.g. Current Assets, Program Expenses');

            // ── Classification ────────────────────────────────────────────────
            $table->enum('statement_type', [
                'balance_sheet',
                'income_statement',
                'cash_flow',
            ])->comment('Which financial statement this category feeds into');

            // ── Hierarchy & Ordering ──────────────────────────────────────────
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('finance_financial_statement_categories')
                ->nullOnDelete()
                ->comment('Nullable → root-level category; populated → sub-category');

            $table->unsignedTinyInteger('display_order')
                ->default(0)
                ->comment('Controls the rendering sequence within its statement section');

            // ── Details ───────────────────────────────────────────────────────
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true)
                ->comment('Inactive categories cannot be assigned to new CoA entries');

            $table->timestamps();
        });

        // ── Deferred: add the FK from CoA → FSC now that FSC table exists ────
        // Both migrations share the same timestamp prefix so execution order can
        // vary. We add the FK here (from the FSC side) if CoA already exists AND
        // the FK has not yet been created (idempotent guard).
        if (Schema::hasTable('finance_chart_of_accounts')) {
            $existingFKs = collect(
                \Illuminate\Support\Facades\DB::select(
                    "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME   = 'finance_chart_of_accounts'
                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                       AND CONSTRAINT_NAME = 'coa_fsc_fk'"
                )
            );

            if ($existingFKs->isEmpty()) {
                Schema::table('finance_chart_of_accounts', function (Blueprint $table) {
                    $table->foreign('financial_statement_category_id', 'coa_fsc_fk')
                        ->references('id')
                        ->on('finance_financial_statement_categories')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        // Drop the deferred FK on CoA before dropping the FSC table
        if (Schema::hasTable('finance_chart_of_accounts')) {
            try {
                Schema::table('finance_chart_of_accounts', function (Blueprint $table) {
                    $table->dropForeign('coa_fsc_fk');
                });
            } catch (\Throwable) {
                // FK may not exist if the CoA migration ran without it — safe to ignore
            }
        }

        Schema::dropIfExists('finance_financial_statement_categories');
    }
};
