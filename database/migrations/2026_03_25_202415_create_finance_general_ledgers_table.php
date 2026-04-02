<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 2 – Chart of Accounts & General Ledger
     *
     * Denormalized, append-only ledger table that stores every posted journal
     * entry line with a pre-computed running balance per account.
     *
     * Design principles:
     *  • Never soft-deleted — the GL is the permanent audit record.
     *  • running_balance is always relative to the account's normal balance
     *    (positive = favourable for that account type).
     *  • One GL row is created for every finance_journal_entry_lines row when
     *    the parent JournalEntry transitions to status = 'posted'.
     *  • Running balances can be fully recomputed from debit/credit columns
     *    via GeneralLedgerService::recalculateRunningBalance().
     *
     * FK ordering note:
     *  All three 202415-prefixed migrations share the same timestamp and are
     *  executed alphabetically: general_ledgers (g) → journal_entries (j) →
     *  journal_entry_lines (j). Because this table references both
     *  finance_journal_entry_lines AND finance_journal_entries, those FKs are
     *  added via deferred Schema::table() blocks guarded with hasTable() so
     *  the migration is safe regardless of execution order.
     */
    public function up(): void
    {
        Schema::create('finance_general_ledgers', function (Blueprint $table) {

            $table->id();

            // ── Core accounting dimensions ────────────────────────────
            $table->foreignId('account_id')
                ->comment('FK → finance_chart_of_accounts (added at 202414, always exists)')
                ->constrained('finance_chart_of_accounts')
                ->restrictOnDelete();

            // Deferred FKs — see Schema::table() blocks below
            $table->unsignedBigInteger('journal_entry_line_id')
                ->comment('FK → finance_journal_entry_lines (deferred — table may not exist yet)');

            // ── Date & period ─────────────────────────────────────────
            $table->date('transaction_date')
                ->comment('Copied from parent journal entry; used for range queries');

            $table->foreignId('period_id')
                ->comment('FK → finance_accounting_periods; copied from parent JE')
                ->constrained('finance_accounting_periods')
                ->restrictOnDelete();

            // ── Amounts (always positive; direction conveyed by debit/credit) ──
            $table->decimal('debit', 15, 2)->default(0.00)
                ->comment('Amount on the debit side (0 if this is a credit line)');

            $table->decimal('credit', 15, 2)->default(0.00)
                ->comment('Amount on the credit side (0 if this is a debit line)');

            /**
             * Pre-computed running balance for this account up to and including
             * this row, ordered by (transaction_date, id).
             *
             * For DEBIT-normal accounts (assets, expenses):
             *   running_balance += debit - credit
             *
             * For CREDIT-normal accounts (liabilities, equity, income):
             *   running_balance -= debit - credit  (i.e. += credit - debit)
             *
             * A positive value always means the account is "on its normal side".
             */
            $table->decimal('running_balance', 15, 2)->default(0.00)
                ->comment('Cumulative running balance for this account, normal-balance adjusted');

            // ── Currency ──────────────────────────────────────────────
            $table->foreignId('currency_id')
                ->comment('Transaction currency (copied from parent JE)')
                ->constrained('currencies')
                ->restrictOnDelete();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────
            // Primary query pattern: "all GL entries for account X, ordered by date"
            $table->index(['account_id', 'transaction_date', 'id'],
                'gl_account_date_seq_idx');

            // Period-level aggregation: "all entries for period P"
            $table->index(['period_id', 'transaction_date'],
                'gl_period_date_idx');

            // Currency-scoped balance queries
            $table->index(['account_id', 'currency_id'],
                'gl_account_currency_idx');
        });

        // ── Deferred: journal_entry_line_id FK ───────────────────────────────
        // finance_journal_entry_lines is created by a sibling 202415 migration
        // that runs AFTER this one alphabetically (g < j). We add the unique
        // constraint + FK only once that table exists.
        if (Schema::hasTable('finance_journal_entry_lines')) {
            $this->addJournalEntryLineFk();
        }
    }

    // ── Down ─────────────────────────────────────────────────────────────────

    public function down(): void
    {
        // Drop deferred FK before dropping the table (if it was ever added)
        if (Schema::hasTable('finance_general_ledgers')) {
            try {
                Schema::table('finance_general_ledgers', function (Blueprint $table) {
                    $table->dropForeign('gl_je_line_fk');
                });
            } catch (\Throwable) {
                // FK may not exist — safe to ignore
            }
        }

        Schema::dropIfExists('finance_general_ledgers');
    }

    // ── Helper: add the JEL FK + unique index ───────────────────────────────

    public function addJournalEntryLineFk(): void
    {
        // Guard: only add if not already present
        $existing = collect(
            DB::select(
                "SELECT CONSTRAINT_NAME
                   FROM information_schema.TABLE_CONSTRAINTS
                  WHERE TABLE_SCHEMA    = DATABASE()
                    AND TABLE_NAME      = 'finance_general_ledgers'
                    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                    AND CONSTRAINT_NAME = 'gl_je_line_fk'"
            )
        );

        if ($existing->isNotEmpty()) {
            return;
        }

        Schema::table('finance_general_ledgers', function (Blueprint $table) {
            // Each JE line produces exactly one GL row → unique constraint
            $table->unique('journal_entry_line_id', 'gl_je_line_unique_idx');

            $table->foreign('journal_entry_line_id', 'gl_je_line_fk')
                ->references('id')
                ->on('finance_journal_entry_lines')
                ->restrictOnDelete();
        });
    }
};
