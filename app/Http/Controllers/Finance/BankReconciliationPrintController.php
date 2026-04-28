<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\BankReconciliation;
use Illuminate\View\View;

class BankReconciliationPrintController extends Controller
{
    /**
     * Render the Bank Reconciliation Summary report.
     */
    public function summary(BankReconciliation $record): View
    {
        $record->loadMissing(['bankAccount', 'period', 'items']);
        $companyName = env('APP_NAME', 'Company Name'); // Or fetch from a setting if available

        return view('finance.reports.bank-reconciliation-summary', compact('record', 'companyName'));
    }

    /**
     * Render the Bank Reconciliation Detail report.
     */
    public function detail(BankReconciliation $record): View
    {
        $record->loadMissing(['bankAccount', 'period', 'items']);
        $companyName = env('APP_NAME', 'Company Name'); // Or fetch from a setting if available

        return view('finance.reports.bank-reconciliation-detail', compact('record', 'companyName'));
    }
}
