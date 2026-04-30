<?php

namespace App\Filament\Resources\Finance\Journals\Pages;

use App\Filament\Resources\Finance\Journals\JournalEntryResource;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\JournalEntry;
use App\Services\Finance\ImportService;
use App\Services\Finance\JournalEntryService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;

    // ── Header Actions ────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            // ── Import Action ─────────────────────────────────────────
            Action::make('import_journal_entries')
                ->label('Import from Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Import Journal Entries')
                ->modalDescription('Upload an Excel (.xlsx) file. Rows are grouped by Reference number — each unique reference becomes one Journal Entry.')
                ->modalIcon('heroicon-o-document-plus')
                ->modalWidth('2xl')
                ->form([
                    Placeholder::make('template_info')
                        ->label('Expected Excel Columns')
                        ->content(new \Illuminate\Support\HtmlString(
                            '<div class="text-sm space-y-3">' .
                            '<p class="text-gray-600 dark:text-gray-400">Row 1 must be the header row. Rows with the <strong class="text-gray-800 dark:text-gray-200">same reference</strong> are grouped into one Journal Entry. Each row = one journal entry line.</p>' .
                            '<table class="w-full text-xs border-collapse">' .
                            '<thead><tr class="border-b border-gray-200 dark:border-gray-700">' .
                            '<th class="text-left py-1.5 pr-4 font-semibold text-gray-700 dark:text-gray-300 w-1/3">Column Name</th>' .
                            '<th class="text-left py-1.5 font-semibold text-gray-700 dark:text-gray-300">Description</th>' .
                            '</tr></thead>' .
                            '<tbody class="divide-y divide-gray-100 dark:divide-gray-800">' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">number</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Row sequence number (informational)</td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">journal_date</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Transaction date (Excel serial or text date)</td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">reference</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-200">Groups rows into one JE</strong> — e.g. JV-25/003</td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">description</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">JE memo and line narration</td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">account</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">GL account code — <strong class="text-gray-800 dark:text-gray-200">must exist</strong> in Chart of Accounts</td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">budget_code</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Activity / budget code (optional)</td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">debit</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Debit amount (0 or blank for credit rows)</td></tr>' .
                            '<tr><td class="py-1.5 pr-4"><code class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1.5 py-0.5 rounded">credit</code></td><td class="py-1.5 text-gray-600 dark:text-gray-400">Credit amount (0 or blank for debit rows)</td></tr>' .
                            '</tbody></table>' .
                            '<p class="text-xs text-gray-500 dark:text-gray-400">&#9888; All imported Journal Entries are saved as <strong class="text-gray-700 dark:text-gray-300">Draft</strong> — review and submit for approval before posting to GL.</p>' .
                            '</div>'
                        )),

                    Select::make('accounting_period_id')
                        ->label('Accounting Period')
                        ->options(fn () => AccountingPeriod::where('status', 'open')
                            ->orderByDesc('fiscal_year')
                            ->orderByDesc('period_number')
                            ->pluck('name', 'id')
                            ->toArray()
                        )
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->helperText('Journal entries will be assigned to this accounting period.'),

                    FileUpload::make('import_file')
                        ->label('Excel File')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->maxSize(20480) // 20 MB for large JE files
                        ->required()
                        ->disk('local')
                        ->directory('finance-imports/journal-entries')
                        ->preserveFilenames(false)
                        ->helperText('Maximum 20 MB. Large files with hundreds of rows are supported.'),
                ])
                ->action(function (array $data) {
                    $filePath = Storage::disk('local')->path($data['import_file']);

                    try {
                        $result = app(ImportService::class)->importJournalEntries(
                            $filePath,
                            (int) $data['accounting_period_id']
                        );

                        Storage::disk('local')->delete($data['import_file']);

                        $body = "✓ {$result['imported']} journal entries imported as Draft. " .
                                "{$result['skipped']} reference groups skipped (duplicate or blank).";

                        if (! empty($result['errors'])) {
                            $errorCount = count($result['errors']);
                            $preview    = implode('; ', array_slice($result['errors'], 0, 5));
                            $body .= "\n⚠ {$errorCount} line warning(s): {$preview}";
                        }

                        Notification::make()
                            ->title('Journal Entry Import Complete')
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

            CreateAction::make()
                ->label('New Journal Entry')
                ->icon('heroicon-o-plus'),
        ];
    }

    // ── Bulk Actions ──────────────────────────────────────────────────

    protected function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([

                // ── Bulk Approve (Draft → Approved) ──────────────────
                BulkAction::make('bulk_approve')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Selected Journal Entries')
                    ->modalDescription('Selected Draft entries will be marked as Approved and become eligible for posting to the GL.')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $service = app(JournalEntryService::class);
                        $user    = auth()->user();
                        $done    = 0;
                        $failed  = 0;

                        foreach ($records as $je) {
                            try {
                                if ($je->isDraft()) {
                                    $service->approve($je, $user);
                                    $done++;
                                }
                            } catch (\Throwable) {
                                $failed++;
                            }
                        }

                        Notification::make()
                            ->title("Bulk Approve: {$done} approved" . ($failed ? ", {$failed} failed." : '.'))
                            ->color($failed ? 'warning' : 'success')
                            ->send();
                    }),

                // ── Bulk Post to GL (Approved or Draft → Posted) ────────
                BulkAction::make('bulk_post')
                    ->label('Post to GL')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Post Selected Entries to General Ledger')
                    ->modalDescription('Selected Approved entries will be posted to the GL. This action cannot be undone — corrections require a Reversal entry.')
                    ->modalSubmitActionLabel('Yes, Post to GL')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $service = app(JournalEntryService::class);
                        $user    = auth()->user();
                        $done    = 0;
                        $failed  = [];

                        foreach ($records as $je) {
                            try {
                                if ($je->isApproved()) {
                                    $service->post($je, $user);
                                    $done++;
                                }
                            } catch (\Throwable $e) {
                                $failed[] = "{$je->reference_number}: " . $e->getMessage();
                            }
                        }

                        $body = "{$done} entr" . ($done === 1 ? 'y' : 'ies') . " posted to GL.";
                        if ($failed) {
                            $body .= ' Errors: ' . implode('; ', array_slice($failed, 0, 3));
                        }

                        Notification::make()
                            ->title('Bulk Post to GL')
                            ->body($body)
                            ->color($failed ? 'warning' : 'success')
                            ->persistent()
                            ->send();
                    }),

                // ── Bulk Delete (Soft Delete) ───────────────────────────
                DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Selected Journal Entries')
                    ->modalDescription('Are you sure? This will soft-delete the selected entries (setup phase only).'),
            ]),
        ];
    }

    // ── Tab-based filtering by status ─────────────────────────────────

    public function getTabs(): array
    {
        $counts = JournalEntry::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'all' => Tab::make('All')
                ->badge($counts->sum())
                ->badgeColor('gray'),

            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge($counts->get('draft', 0))
                ->badgeColor('gray'),

            'pending_approval' => Tab::make('Pending Approval')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending_approval'))
                ->badge($counts->get('pending_approval', 0))
                ->badgeColor('warning'),

            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badge($counts->get('approved', 0))
                ->badgeColor('info'),

            'posted' => Tab::make('Posted')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'posted'))
                ->badge($counts->get('posted', 0))
                ->badgeColor('success'),

            'reversed' => Tab::make('Reversed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'reversed'))
                ->badge($counts->get('reversed', 0))
                ->badgeColor('danger'),
        ];
    }
}
