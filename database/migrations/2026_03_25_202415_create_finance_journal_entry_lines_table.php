<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_journal_entry_lines', function (Blueprint $table) {
            $table->id();

            // Parent journal entry — cascade delete keeps lines in sync with the header
            $table->foreignId('journal_entry_id')
                ->constrained('finance_journal_entries')
                ->cascadeOnDelete();

            // The GL account this line posts to (leaf accounts only — enforced at service layer)
            $table->foreignId('account_id')
                ->constrained('finance_chart_of_accounts')
                ->restrictOnDelete();

            // Double-entry amounts — exactly one of these must be > 0 per line
            $table->decimal('debit',  15, 2)->default(0.00);
            $table->decimal('credit', 15, 2)->default(0.00);

            // ── 4-Dimension NGO Coding ────────────────────────────────────────────
            // Every line may be tagged with the four dimensions required for
            // donor accountability and budget control.

            $table->foreignId('cost_center_id')
                ->nullable()
                ->constrained('finance_cost_centers')
                ->nullOnDelete();

            $table->foreignId('donor_id')
                ->nullable()
                ->constrained('donors')
                ->nullOnDelete();

            $table->foreignId('project_id')
                ->nullable()
                ->constrained('hr_projects')
                ->nullOnDelete();

            // Activity code links the line to a specific budget line item
            $table->string('activity_code', 50)->nullable();

            // Human-readable description for this specific line
            $table->string('narration', 500)->nullable();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────────────────
            // Fast lookup by account (General Ledger posting, balance queries)
            $table->index('account_id');
            // Fast lookup by the 4 NGO dimensions for reporting
            $table->index('cost_center_id');
            $table->index('donor_id');
            $table->index('project_id');
            $table->index('activity_code');
        });

        // ── Deferred: add the GL → JEL FK now that JEL table exists ─────────
        // finance_general_ledgers is created by a sibling 202415 migration that
        // runs BEFORE this one alphabetically (g < j). By the time this
        // migration runs, the GL table already exists but still lacks the
        // journal_entry_line_id FK (which was deferred there). We add it here.
        if (Schema::hasTable('finance_general_ledgers')) {
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

            if ($existing->isEmpty()) {
                Schema::table('finance_general_ledgers', function (Blueprint $table) {
                    $table->unique('journal_entry_line_id', 'gl_je_line_unique_idx');

                    $table->foreign('journal_entry_line_id', 'gl_je_line_fk')
                        ->references('id')
                        ->on('finance_journal_entry_lines')
                        ->restrictOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_journal_entry_lines');
    }
};
