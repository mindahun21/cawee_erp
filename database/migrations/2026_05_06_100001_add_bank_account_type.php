<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds a dedicated "Bank" Account Type so users see "Bank" in the
 * Chart of Accounts type dropdown (matching the Zemen ERP UX).
 *
 * Classification is kept as 'asset' so all existing reports, GL
 * running-balance calculations, and Balance Sheet queries work with
 * zero changes — the classification field is what drives accounting logic,
 * not the display name.
 *
 * normal_balance = 'debit' is correct for bank accounts (an asset).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only insert if it doesn't already exist (idempotent)
        if (DB::table('finance_account_types')->where('code', 'BANK')->doesntExist()) {
            DB::table('finance_account_types')->insert([
                'code'           => 'BANK',
                'name'           => 'Bank',
                'classification' => 'asset',       // keeps all report WHERE-IN clauses working
                'normal_balance' => 'debit',        // bank account = asset = debit-normal
                'description'    => 'Bank and financial institution accounts held by the organisation.',
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('finance_account_types')->where('code', 'BANK')->delete();
    }
};
