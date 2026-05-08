<?php

namespace App\Services\Finance;

use App\Models\Finance\FinancialStatement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

/**
 * FinanceReportService
 *
 * Handles generating PDF and Excel reports for the Finance module.
 */
class FinanceReportService
{
    public function __construct(private readonly GeneralLedgerService $glService) {}

    /**
     * Generate a Trial Balance PDF report.
     */
    public function generateTrialBalancePdf(FinancialStatement $statement): \Barryvdh\DomPDF\PDF
    {
        $periodId = $statement->accounting_period_id;
        $trialBalanceData = collect();

        if ($periodId) {
            $trialBalanceData = $this->glService->getTrialBalance($periodId);
        }

        // We prepare variables for the view
        $data = [
            'statement' => $statement,
            'records'   => $trialBalanceData,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'generatedBy' => auth()->user()?->name ?? 'System',
            'companyName' => 'Cawee', // Can be pulled from settings
        ];

        // Ensure the view exists or use a fallback simple template
        if (View::exists('finance.reports.trial-balance')) {
            $view = 'finance.reports.trial-balance';
        } else {
            $view = 'finance.reports.fallback-report';
        }

        return Pdf::loadView($view, $data)->setPaper('a4', 'landscape');
    }

    /**
     * Generate an Income Statement (P&L) PDF report.
     */
    public function generateIncomeStatementPdf(FinancialStatement $statement): \Barryvdh\DomPDF\PDF
    {
        // In a real implementation this would fetch revenues and expenses grouped by category
        $data = [
            'statement' => $statement,
            'records'   => collect(), // Placeholder for data
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'generatedBy' => auth()->user()?->name ?? 'System',
        ];

        $view = View::exists('finance.reports.income-statement') ? 'finance.reports.income-statement' : 'finance.reports.fallback-report';

        return Pdf::loadView($view, $data)->setPaper('a4', 'portrait');
    }

    /**
     * Generate a Balance Sheet PDF report.
     */
    public function generateBalanceSheetPdf(FinancialStatement $statement): \Barryvdh\DomPDF\PDF
    {
        $data = [
            'statement' => $statement,
            'records'   => collect(), // Placeholder for data
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'generatedBy' => auth()->user()?->name ?? 'System',
        ];

        $view = View::exists('finance.reports.balance-sheet') ? 'finance.reports.balance-sheet' : 'finance.reports.fallback-report';

        return Pdf::loadView($view, $data)->setPaper('a4', 'portrait');
    }

    /**
     * Download the appropriate report based on statement_type.
     */
    public function downloadPdf(FinancialStatement $statement)
    {
        $pdf = match ($statement->statement_type) {
            'trial_balance'    => $this->generateTrialBalancePdf($statement),
            'income_statement' => $this->generateIncomeStatementPdf($statement),
            'balance_sheet'    => $this->generateBalanceSheetPdf($statement),
            default => $this->generateGenericPdf($statement),
        };

        $filename = "{$statement->statement_type}_{$statement->reference}.pdf";
        return response()->streamDownload(fn () => print($pdf->output()), $filename);
    }

    public function generateGenericPdf(FinancialStatement $statement): \Barryvdh\DomPDF\PDF
    {
        $data = [
            'statement' => $statement,
            'records'   => collect(),
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'generatedBy' => auth()->user()?->name ?? 'System',
        ];

        return Pdf::loadView('finance.reports.fallback-report', $data)->setPaper('a4', 'portrait');
    }
}
