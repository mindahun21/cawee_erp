<?php

namespace App\Filament\Resources\Finance\Bank\Pages;

use App\Filament\Resources\Finance\Bank\BankAccountResource;
use App\Services\Finance\ImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListBankAccounts extends ListRecords
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Import Action ─────────────────────────────────────────
            Action::make('import_bank_accounts')
                ->label('Import from Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Import Bank Accounts')
                ->modalDescription('Upload an Excel (.xlsx) file containing bank account data. Only rows with Type = "Bank" will be imported.')
                ->modalIcon('heroicon-o-building-library')
                ->modalWidth('xl')
                ->form([
                    Placeholder::make('template_info')
                        ->label('Expected Excel Columns')
                        ->content(new \Illuminate\Support\HtmlString(
                            '<div class="text-sm space-y-3">' .
                            '<p class="text-gray-600 dark:text-gray-400">Row 1 must be the header row with <strong class="text-gray-800 dark:text-gray-200">exactly these column names</strong>. Only rows where <code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1 py-0.5 rounded text-xs">Type = Bank</code> will be imported.</p>' .
                            '<table class="w-full text-xs border-collapse">' .
                            '<thead>' .
                            '<tr class="border-b border-gray-200 dark:border-gray-700">' .
                            '<th class="text-left py-1.5 pr-4 font-semibold text-gray-700 dark:text-gray-300 w-1/3">Column Name</th>' .
                            '<th class="text-left py-1.5 font-semibold text-gray-700 dark:text-gray-300">Description</th>' .
                            '</tr>' .
                            '</thead>' .
                            '<tbody class="divide-y divide-gray-100 dark:divide-gray-800">' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">Type</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Must be <strong class="text-gray-800 dark:text-gray-200">Bank</strong> — other types are skipped</td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">sub_type</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Bank name + account number, e.g. <em>Dashen Bank ETB(0012107632011)</em></td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">account_code</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Short code, e.g. <em>D11000 ETB</em></td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">account_name</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Display name for this bank account</td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">sub_account_of</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Parent account code (optional)</td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">Bank</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Bank SWIFT/identifier code</td></tr>' .
                            '</tbody></table>' .
                            '<p class="text-xs text-gray-500 dark:text-gray-400">💡 Currency is auto-detected from the account code (USD, EUR, ETB, etc.).</p>' .
                            '</div>'
                        )),

                    FileUpload::make('import_file')
                        ->label('Excel File')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->maxSize(10240)
                        ->required()
                        ->disk('local')
                        ->directory('finance-imports/bank-accounts')
                        ->preserveFilenames(false),
                ])
                ->action(function (array $data) {
                    $filePath = Storage::disk('local')->path($data['import_file']);

                    try {
                        $result = app(ImportService::class)->importBankAccounts($filePath);

                        Storage::disk('local')->delete($data['import_file']);

                        $body = "✓ {$result['imported']} bank accounts imported. " .
                                "{$result['skipped']} rows skipped (non-bank or duplicates).";

                        if (! empty($result['errors'])) {
                            $body .= "\n⚠ Warnings: " . implode('; ', array_slice($result['errors'], 0, 3));
                        }

                        Notification::make()
                            ->title('Bank Accounts Import Complete')
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

            CreateAction::make()->label('New Bank Account'),
        ];
    }
}
