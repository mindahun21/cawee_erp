<?php

namespace App\Filament\Resources\Finance\ChartOfAccounts\Pages;

use App\Filament\Resources\Finance\ChartOfAccounts\ChartOfAccountResource;
use App\Models\Finance\AccountingPeriod;
use App\Services\Finance\ImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListChartOfAccounts extends ListRecords
{
    protected static string $resource = ChartOfAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Import Action ─────────────────────────────────────────
            Action::make('import_coa')
                ->label('Import from Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Import Chart of Accounts')
                ->modalDescription('Upload an Excel (.xlsx) file. Required columns: Type, sub_type, account_code, account_name, sub_account_of, Bank.')
                ->modalIcon('heroicon-o-table-cells')
                ->modalWidth('xl')
                ->form([
                    Placeholder::make('template_info')
                        ->label('Expected Excel Columns')
                        ->content(new \Illuminate\Support\HtmlString(
                            '<div class="text-sm space-y-3">' .
                            '<p class="text-gray-600 dark:text-gray-400">Row 1 must be the header row with <strong class="text-gray-800 dark:text-gray-200">exactly these column names</strong>. Existing accounts (same code) will be updated; new codes will be created.</p>' .
                            '<table class="w-full text-xs border-collapse">' .
                            '<thead>' .
                            '<tr class="border-b border-gray-200 dark:border-gray-700">' .
                            '<th class="text-left py-1.5 pr-4 font-semibold text-gray-700 dark:text-gray-300 w-1/3">Column Name</th>' .
                            '<th class="text-left py-1.5 font-semibold text-gray-700 dark:text-gray-300">Description</th>' .
                            '</tr>' .
                            '</thead>' .
                            '<tbody class="divide-y divide-gray-100 dark:divide-gray-800">' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">Type</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Account classification: Bank, Assets, Liability, Income, Expense&hellip;</td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">sub_type</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Sub-category label (stored in notes)</td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">account_code</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Unique account code, e.g. <em>D11000</em></td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">account_name</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Full display name</td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">sub_account_of</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Parent account code (optional, leave blank for root)</td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">Bank</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Bank SWIFT/identifier (optional)</td></tr>' .
                            '</tbody></table>' .
                            '</div>'
                        )),

                    FileUpload::make('import_file')
                        ->label('Excel File')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->maxSize(10240) // 10 MB
                        ->required()
                        ->disk('local')
                        ->directory('finance-imports/coa')
                        ->preserveFilenames(false),
                ])
                ->action(function (array $data) {
                    $filePath = Storage::disk('local')->path($data['import_file']);

                    try {
                        $result = app(ImportService::class)->importChartOfAccounts($filePath);

                        // Delete temp file
                        Storage::disk('local')->delete($data['import_file']);

                        $body = "✓ {$result['imported']} accounts imported. " .
                                "{$result['skipped']} skipped (already exist / updated).";

                        if (! empty($result['errors'])) {
                            $body .= "\n⚠ " . count($result['errors']) . " warning(s): " .
                                     implode('; ', array_slice($result['errors'], 0, 3));
                        }

                        Notification::make()
                            ->title('Chart of Accounts Import Complete')
                            ->body($body)
                            ->color(empty($result['errors']) ? 'success' : 'warning')
                            ->icon(empty($result['errors']) ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle')
                            ->persistent()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            CreateAction::make()->label('New Account'),
        ];
    }
}
