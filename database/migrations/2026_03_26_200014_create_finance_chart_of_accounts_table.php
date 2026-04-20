<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_chart_of_accounts', function (Blueprint $table) {
            $table->id();

            // ── Identity ───────────────────────────────────────────────────────
            $table->string('code', 20)->unique()
                ->comment('Unique account code, e.g. 1101, 5310');
            $table->string('name', 200)
                ->comment('Full descriptive name of the account');

            // ── Classification ─────────────────────────────────────────────────
            $table->foreignId('account_type_id')
                ->constrained('finance_account_types')
                ->restrictOnDelete()
                ->comment('Links to Asset / Liability / Equity / Income / Expense');

            // FSC FK is deferred below (after FSC table is guaranteed to exist)
            $table->unsignedBigInteger('financial_statement_category_id')
                ->nullable()
                ->comment('Which section of the financial statement this account maps to');

            // ── Hierarchy (self-referential tree) ──────────────────────────────
            $table->unsignedBigInteger('parent_id')
                ->nullable()
                ->comment('Parent account — null for top-level / root accounts');

            $table->foreign('parent_id', 'coa_parent_fk')
                ->references('id')
                ->on('finance_chart_of_accounts')
                ->nullOnDelete();

            $table->unsignedTinyInteger('level')
                ->default(0)
                ->comment('Depth in the hierarchy: 0 = root, 1 = section, 2 = sub-section, 3 = leaf');

            $table->boolean('is_header')
                ->default(false)
                ->comment('Header accounts group children; they cannot receive direct journal postings');

            // ── Multi-currency ─────────────────────────────────────────────────
            $table->unsignedBigInteger('currency_id')
                ->nullable()
                ->comment('Override currency for this account; null = use functional currency (ETB)');

            $table->foreign('currency_id', 'coa_currency_fk')
                ->references('id')
                ->on('currencies')
                ->nullOnDelete();

            // ── Control account flags ──────────────────────────────────────────
            $table->enum('is_control_account', ['none', 'ap', 'ar', 'bank'])
                ->default('none')
                ->comment('Marks the account as a control account for AP, AR, or Bank sub-ledgers');

            $table->boolean('is_donor_fund_account')
                ->default(false)
                ->comment('Flags donor-restricted fund accounts for NGO reporting');

            // ── Status ─────────────────────────────────────────────────────────
            $table->boolean('is_active')
                ->default(true)
                ->comment('Inactive accounts are hidden from dropdowns and block new postings');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // ── Deferred: FSC FK (added only once FSC table is confirmed to exist) ──
        // Both migrations share the same timestamp prefix so execution order can
        // vary. We guard with hasTable() so this block is idempotent.
        if (Schema::hasTable('finance_financial_statement_categories')) {
            Schema::table('finance_chart_of_accounts', function (Blueprint $table) {
                $table->foreign('financial_statement_category_id', 'coa_fsc_fk')
                    ->references('id')
                    ->on('finance_financial_statement_categories')
                    ->nullOnDelete();
            });
        }

        // ── Indexes ────────────────────────────────────────────────────────────
        Schema::table('finance_chart_of_accounts', function (Blueprint $table) {
            $table->index(['parent_id', 'is_active', 'code'], 'coa_parent_active_code');
            $table->index(['account_type_id', 'is_active'],   'coa_type_active');
            $table->index(['is_header', 'is_active'],         'coa_header_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_chart_of_accounts');
    }
};
