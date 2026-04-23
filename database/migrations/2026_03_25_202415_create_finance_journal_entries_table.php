<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_journal_entries', function (Blueprint $table) {
            $table->id();

            // ── Auto-generated reference (JE-2026-0001) ───────────────────────
            $table->string('reference_number', 30)->unique()
                ->comment('Auto-generated: {prefix}-{year}-{sequence}');

            // ── Accounting dimension ──────────────────────────────────────────
            $table->foreignId('accounting_period_id')
                ->constrained('finance_accounting_periods')
                ->restrictOnDelete()
                ->comment('Must reference an open period at posting time');

            $table->date('transaction_date');

            $table->text('description')
                ->comment('Human-readable narration / memo for the entire entry');

            // ── Workflow status ───────────────────────────────────────────────
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'posted',
                'reversed',
            ])->default('draft');

            // ── Source tracking (where this JE originated) ────────────────────
            $table->enum('source', [
                'manual',
                'payroll',
                'bank',
                'petty_cash',
                'procurement',
                'perdiem',
                'opening_balance',
            ])->default('manual');

            // Polymorphic-style source link (not a true Laravel morph, intentional)
            $table->string('source_type', 150)->nullable()
                ->comment('FQCN of the originating model, e.g. App\\Models\\Finance\\PaymentVoucher');
            $table->unsignedBigInteger('source_id')->nullable()
                ->comment('Primary key of the originating record');

            // ── Maker / Checker ───────────────────────────────────────────────
            $table->foreignId('prepared_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('posted_at')->nullable();

            // ── Currency & FX ─────────────────────────────────────────────────
            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->restrictOnDelete();

            $table->decimal('exchange_rate_to_base', 10, 6)->default(1.000000)
                ->comment('Rate to convert this entry\'s currency to the functional (ETB) currency');

            // ── Reversal link ─────────────────────────────────────────────────
            $table->foreignId('reversal_of_id')
                ->nullable()
                ->constrained('finance_journal_entries')
                ->nullOnDelete()
                ->comment('Points to the original JE when this entry is a reversal');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ───────────────────────────────────────────────────────
            $table->index(['accounting_period_id', 'transaction_date'], 'je_period_date_idx');
            $table->index(['status', 'transaction_date'],               'je_status_date_idx');
            $table->index(['source_type', 'source_id'],                 'je_source_morph_idx');
            $table->index('prepared_by',                                'je_prepared_by_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_journal_entries');
    }
};
