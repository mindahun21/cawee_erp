<?php

namespace App\Filament\Resources\Finance\Budgets\BudgetCodeResource\Pages;

use App\Filament\Resources\Finance\Budgets\BudgetCodeResource;
use App\Services\Finance\ImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ListBudgetCodes extends ListRecords
{
    protected static string $resource = BudgetCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_budget_codes')
                ->label('Import Budget')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Import Budget Codes')
                ->form([
                    Placeholder::make('template_info')
                        ->label('Expected Columns')
                        ->content(new HtmlString(
                            '<div class="text-sm space-y-2">' .
                            '<p>Upload an Excel file with headers in row 1.</p>' .
                            '<p><code>code</code>, <code>description</code>, <code>cost_category</code></p>' .
                            '<p class="text-xs text-gray-500">Existing codes are updated. New codes are created.</p>' .
                            '</div>'
                        )),
                    FileUpload::make('import_file')
                        ->label('Excel File')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->required()
                        ->disk('local')
                        ->directory('finance-imports/budget-codes')
                        ->preserveFilenames(false),
                ])
                ->action(function (array $data) {
                    $filePath = Storage::disk('local')->path($data['import_file']);

                    try {
                        $result = app(ImportService::class)->importBudgetCodes($filePath);
                        Storage::disk('local')->delete($data['import_file']);

                        $body = "Imported: {$result['imported']}, Updated/Skipped: {$result['skipped']}";
                        if (! empty($result['errors'])) {
                            $body .= "\n" . implode("\n", array_slice($result['errors'], 0, 5));
                        }

                        Notification::make()
                            ->title('Budget code import complete')
                            ->body($body)
                            ->color(empty($result['errors']) ? 'success' : 'warning')
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Import failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            CreateAction::make()
                ->label('New'),
        ];
    }
}
