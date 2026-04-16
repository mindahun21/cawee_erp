<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend the source ENUM to include all Phase-3 transaction types.
        // Using raw ALTER TABLE because Laravel's ENUM change via ->change()
        // drops and recreates the column which is risky on a live table.
        DB::statement("
            ALTER TABLE finance_journal_entries
            MODIFY COLUMN `source` ENUM(
                'manual',
                'payroll',
                'bank',
                'petty_cash',
                'procurement',
                'perdiem',
                'opening_balance',
                'fund_transfer',
                'crv',
                'payment_voucher',
                'petty_cash_replenishment'
            ) NOT NULL DEFAULT 'manual'
        ");
    }

    public function down(): void
    {
        // Revert — only safe if no rows use the new values
        DB::statement("
            ALTER TABLE finance_journal_entries
            MODIFY COLUMN `source` ENUM(
                'manual',
                'payroll',
                'bank',
                'petty_cash',
                'procurement',
                'perdiem',
                'opening_balance'
            ) NOT NULL DEFAULT 'manual'
        ");
    }
};
