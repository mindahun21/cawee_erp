<?php

namespace App\Filament\Resources\Finance\Journals;

use App\Filament\Resources\Finance\Journals\Pages\CreateJournalEntry;
use App\Filament\Resources\Finance\Journals\Pages\EditJournalEntry;
use App\Filament\Resources\Finance\Journals\Pages\ListJournalEntries;
use App\Filament\Resources\Finance\Journals\Pages\ViewJournalEntry;
use App\Models\Currency;
use App\Models\Donor;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\CostCenter;
use App\Models\Finance\FinanceAuditLog;
use App\Models\Finance\JournalEntry;
use App\Models\Project;
use App\Models\User;
use App\Services\Finance\GeneralLedgerService;
use App\Services\Finance\JournalEntryService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    // ── Navigation ────────────────────────────────────────────────────

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Journal Entries';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'reference_number';

    protected static bool $shouldSkipAuthorization = true;

    // ── Authorization ─────────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }

    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($r): bool   { return $r->isEditable(); }
    public static function canDelete($r): bool { return $r->isDraft() && static::canViewAny(); }

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── Header ────────────────────────────────────────────────
            Section::make('Journal Entry Header')
                ->description('Identify the entry, its period, source and functional currency.')
                ->icon('heroicon-o-document-text')
                ->columns(3)
                ->schema([

                    TextInput::make('reference_number')
                        ->label('Reference Number')
                        ->placeholder('Auto-generated on save')
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Format: JE-{YEAR}-{SEQUENCE}'),

                    Select::make('accounting_period_id')
                        ->label('Accounting Period')
                        ->options(AccountingPeriod::openOptions())
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->preload()
                        ->helperText('Only open periods are available for new postings.'),

                    DatePicker::make('transaction_date')
                        ->label('Transaction Date')
                        ->required()
                        ->native(false)
                        ->default(now()->toDateString()),

                    Textarea::make('description')
                        ->label('Description / Memo')
                        ->required()
                        ->rows(2)
                        ->maxLength(1000)
                        ->columnSpanFull()
                        ->placeholder('Brief description of the transaction purpose…'),

                    Select::make('source')
                        ->label('Source')
                        ->options(JournalEntry::sources())
                        ->default('manual')
                        ->required()
                        ->native(false)
                        ->helperText('System-generated entries (payroll, bank) will be set automatically.'),

                    Select::make('currency_id')
                        ->label('Currency')
                        ->options(fn () => Currency::orderBy('code')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(fn () => Currency::where('code', 'ETB')->value('id'))
                        ->native(false)
                        ->live(),

                    TextInput::make('exchange_rate_to_base')
                        ->label('Exchange Rate to ETB')
                        ->numeric()
                        ->default(1.000000)
                        ->step(0.000001)
                        ->minValue(0.000001)
                        ->required()
                        ->helperText('Set to 1.000000 for ETB transactions.')
                        ->extraInputAttributes(['class' => 'font-mono']),
                ]),

            // ── Journal Entry Lines ───────────────────────────────────
            Section::make('Journal Entry Lines')
                ->description(
                    'Each row posts to a single GL account. ' .
                    'Every entry MUST balance: Σ Debit = Σ Credit.'
                )
                ->icon('heroicon-o-table-cells')
                ->schema([
                    Repeater::make('lines')
                        ->relationship('lines')
                        ->label('')
                        ->live()
                        ->addActionLabel('+ Add Line')
                        ->minItems(2)
                        ->defaultItems(2)
                        ->collapsible()
                        ->cloneable()
                        ->columns(12)
                        ->itemLabel(function (array $state): string {
                            $accountId = $state['account_id'] ?? null;
                            $debit     = (float) ($state['debit']  ?? 0);
                            $credit    = (float) ($state['credit'] ?? 0);

                            $accountLabel = $accountId
                                ? (ChartOfAccount::find($accountId)?->name ?? "Account #{$accountId}")
                                : 'New Line';

                            $amountPart = $debit > 0
                                ? '  ·  DR ' . number_format($debit, 2)
                                : ($credit > 0
                                    ? '  ·  CR ' . number_format($credit, 2)
                                    : '');

                            return $accountLabel . $amountPart;
                        })
                        ->schema([

                            // Account — spans 4 of 12 cols
                            Select::make('account_id')
                                ->label('Account')
                                ->options(ChartOfAccount::transactionOptions())
                                ->searchable()
                                ->required()
                                ->native(false)
                                ->columnSpan(4)
                                ->helperText('Leaf accounts only — header accounts cannot receive postings.')
                                ->live(onBlur: true),

                            // Debit — spans 2
                            TextInput::make('debit')
                                ->label('Debit (DR)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->step(0.01)
                                ->live(debounce: 400)
                                ->columnSpan(2)
                                ->extraInputAttributes(['class' => 'font-mono text-right'])
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    if ((float) $state > 0) {
                                        $set('credit', 0);
                                    }
                                }),

                            // Credit — spans 2
                            TextInput::make('credit')
                                ->label('Credit (CR)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->step(0.01)
                                ->live(debounce: 400)
                                ->columnSpan(2)
                                ->extraInputAttributes(['class' => 'font-mono text-right'])
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    if ((float) $state > 0) {
                                        $set('debit', 0);
                                    }
                                }),

                            // Cost Centre — spans 3
                            Select::make('cost_center_id')
                                ->label('Cost Centre')
                                ->options(CostCenter::activeOptions())
                                ->searchable()
                                ->nullable()
                                ->native(false)
                                ->columnSpan(3),

                            // Donor — spans 3
                            Select::make('donor_id')
                                ->label('Donor')
                                ->options(fn () => Donor::orderBy('organization_name')
                                    ->get()
                                    ->mapWithKeys(fn ($d) => [
                                        $d->id => $d->full_name ?? $d->organization_name,
                                    ])
                                    ->toArray()
                                )
                                ->searchable()
                                ->nullable()
                                ->native(false)
                                ->columnSpan(3)
                                ->helperText('Required for donor-restricted fund accounts.'),

                            // Project — spans 3
                            Select::make('project_id')
                                ->label('Project')
                                ->options(fn () => Project::orderBy('project_name')
                                    ->pluck('project_name', 'id')
                                    ->toArray()
                                )
                                ->searchable()
                                ->nullable()
                                ->native(false)
                                ->columnSpan(3),

                            // Activity Code — spans 3
                            TextInput::make('activity_code')
                                ->label('Activity Code')
                                ->maxLength(50)
                                ->nullable()
                                ->columnSpan(3)
                                ->extraInputAttributes(['class' => 'font-mono'])
                                ->helperText('Links to a budget line item.'),

                            // Narration — spans 6
                            TextInput::make('narration')
                                ->label('Line Narration')
                                ->maxLength(500)
                                ->nullable()
                                ->columnSpan(6)
                                ->placeholder('Brief description of this specific line…'),
                        ]),
                ]),

            // ── Live Balance Summary ──────────────────────────────────
            Section::make('Balance Check')
                ->description('This section updates live as you enter line amounts.')
                ->icon('heroicon-o-scale')
                ->columns(4)
                ->schema([
                    Placeholder::make('total_debit')
                        ->label('Total Debit (DR)')
                        ->content(function (Get $get): HtmlString {
                            $lines = $get('lines') ?? [];
                            $total = collect($lines)->sum(fn ($l) => (float) ($l['debit'] ?? 0));
                            return new HtmlString(
                                '<span class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">' .
                                number_format($total, 2) .
                                '</span>'
                            );
                        }),

                    Placeholder::make('total_credit')
                        ->label('Total Credit (CR)')
                        ->content(function (Get $get): HtmlString {
                            $lines = $get('lines') ?? [];
                            $total = collect($lines)->sum(fn ($l) => (float) ($l['credit'] ?? 0));
                            return new HtmlString(
                                '<span class="font-mono font-semibold text-blue-600 dark:text-blue-400">' .
                                number_format($total, 2) .
                                '</span>'
                            );
                        }),

                    Placeholder::make('difference')
                        ->label('Difference')
                        ->content(function (Get $get): HtmlString {
                            $lines  = $get('lines') ?? [];
                            $dr     = collect($lines)->sum(fn ($l) => (float) ($l['debit']  ?? 0));
                            $cr     = collect($lines)->sum(fn ($l) => (float) ($l['credit'] ?? 0));
                            $diff   = abs($dr - $cr);
                            $color  = $diff < 0.005 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400';
                            return new HtmlString(
                                "<span class=\"font-mono font-semibold {$color}\">" .
                                number_format($diff, 2) .
                                '</span>'
                            );
                        }),

                    Placeholder::make('balance_status')
                        ->label('Status')
                        ->content(function (Get $get): HtmlString {
                            $lines  = $get('lines') ?? [];
                            $dr     = collect($lines)->sum(fn ($l) => (float) ($l['debit']  ?? 0));
                            $cr     = collect($lines)->sum(fn ($l) => (float) ($l['credit'] ?? 0));
                            $ok     = abs($dr - $cr) < 0.005 && count($lines) >= 2;

                            if ($ok) {
                                return new HtmlString(
                                    '<span class="inline-flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">' .
                                    '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>' .
                                    ' Balanced — Ready to Submit' .
                                    '</span>'
                                );
                            }

                            $lineCount = count($lines);
                            if ($lineCount < 2) {
                                return new HtmlString(
                                    '<span class="inline-flex items-center gap-1 font-semibold text-amber-600 dark:text-amber-400">' .
                                    '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>' .
                                    ' Add at least 2 lines' .
                                    '</span>'
                                );
                            }

                            return new HtmlString(
                                '<span class="inline-flex items-center gap-1 font-semibold text-rose-600 dark:text-rose-400">' .
                                '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>' .
                                ' Unbalanced — Cannot Post' .
                                '</span>'
                            );
                        }),
                ]),

            // ── Notes ─────────────────────────────────────────────────
            Section::make('Internal Notes')
                ->icon('heroicon-o-pencil-square')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('notes')
                        ->label('')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull()
                        ->placeholder('Optional internal notes for finance staff…'),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->badge()
                    ->color('primary')
                    ->copyable()
                    ->copyMessage('Reference copied!'),

                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('period.name')
                    ->label('Period')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(45)
                    ->tooltip(fn ($state) => $state),

                TextColumn::make('source')
                    ->label('Source')
                    ->formatStateUsing(fn ($state) => JournalEntry::sources()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'manual'          => 'gray',
                        'payroll'         => 'info',
                        'bank'            => 'primary',
                        'petty_cash'      => 'warning',
                        'procurement'     => 'success',
                        'perdiem'         => 'amber',
                        'opening_balance' => 'violet',
                        default           => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => JournalEntry::statuses()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft'            => 'gray',
                        'pending_approval' => 'warning',
                        'approved'         => 'info',
                        'posted'           => 'success',
                        'reversed'         => 'danger',
                        default            => 'gray',
                    }),

                // Total Debit (computed from lines)
                TextColumn::make('total_debit')
                    ->label('Total DR')
                    ->getStateUsing(fn (JournalEntry $record) =>
                        $record->lines()->sum('debit')
                    )
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->color('success')
                    ->toggleable(),

                TextColumn::make('currency.code')
                    ->label('CCY')
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('preparedBy.name')
                    ->label('Prepared By')
                    ->searchable(['users.name'])
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('posted_at')
                    ->label('Posted At')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('status')
                    ->options(JournalEntry::statuses())
                    ->label('Status'),

                SelectFilter::make('source')
                    ->options(JournalEntry::sources())
                    ->label('Source'),

                SelectFilter::make('accounting_period_id')
                    ->label('Accounting Period')
                    ->options(fn () => AccountingPeriod::orderByDesc('fiscal_year')
                        ->orderByDesc('period_number')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable(),

                SelectFilter::make('currency_id')
                    ->label('Currency')
                    ->options(fn () => Currency::orderBy('code')->pluck('code', 'id')),
            ])

            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (JournalEntry $record) => $record->isEditable()),

                // ── Submit for Approval ───────────────────────────────
                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (JournalEntry $record) =>
                        $record->isDraft() && static::canViewAny()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Submit Journal Entry for Approval')
                    ->modalDescription('This will send the entry to a Finance Manager for review and approval before it can be posted to the General Ledger.')
                    ->action(function (JournalEntry $record) {
                        try {
                            app(JournalEntryService::class)->submit($record, auth()->user());
                            Notification::make()
                                ->title("JE [{$record->reference_number}] submitted for approval.")
                                ->info()->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Cannot submit')
                                ->body($e->getMessage())
                                ->danger()->send();
                        }
                    }),

                // ── Approve ───────────────────────────────────────────
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (JournalEntry $record) =>
                        $record->isPendingApproval() &&
                        (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin())
                    )
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('comments')
                            ->label('Approval Comments (optional)')
                            ->rows(3)
                            ->nullable(),
                    ])
                    ->action(function (JournalEntry $record, array $data) {
                        try {
                            app(JournalEntryService::class)->approve(
                                $record, auth()->user(), $data['comments'] ?? ''
                            );
                            Notification::make()
                                ->title("JE [{$record->reference_number}] approved.")
                                ->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Cannot approve')
                                ->body($e->getMessage())
                                ->danger()->send();
                        }
                    }),

                // ── Post to GL ────────────────────────────────────────
                Action::make('post')
                    ->label('Post to GL')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->visible(fn (JournalEntry $record) =>
                        $record->isApproved() &&
                        (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin())
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Post Journal Entry to General Ledger')
                    ->modalDescription(
                        'This action is irreversible. The entry will be written to the General Ledger and its status will change to Posted. ' .
                        'Any errors will require a Reversal entry to correct.'
                    )
                    ->action(function (JournalEntry $record) {
                        try {
                            app(JournalEntryService::class)->post($record, auth()->user());
                            Notification::make()
                                ->title("JE [{$record->reference_number}] posted to General Ledger.")
                                ->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Posting failed')
                                ->body($e->getMessage())
                                ->danger()->send();
                        }
                    }),

                // ── Return for Revision ───────────────────────────────
                Action::make('return')
                    ->label('Return')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (JournalEntry $record) =>
                        $record->isPendingApproval() &&
                        (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin())
                    )
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('comments')
                            ->label('Reason for Returning')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (JournalEntry $record, array $data) {
                        try {
                            app(JournalEntryService::class)->returnForRevision(
                                $record, auth()->user(), $data['comments'] ?? ''
                            );
                            Notification::make()
                                ->title("JE [{$record->reference_number}] returned to draft.")
                                ->warning()->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Cannot return')
                                ->body($e->getMessage())
                                ->danger()->send();
                        }
                    }),

                // ── Reverse ───────────────────────────────────────────
                Action::make('reverse')
                    ->label('Reverse')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->visible(fn (JournalEntry $record) =>
                        $record->isPosted() &&
                        (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin())
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Reverse Posted Journal Entry')
                    ->modalDescription(
                        'A new "mirror" journal entry will be created with all debits and credits swapped, ' .
                        'and immediately posted to the General Ledger. The original entry will be marked Reversed.'
                    )
                    ->form([
                        Textarea::make('reason')
                            ->label('Reason for Reversal')
                            ->required()
                            ->rows(3)
                            ->placeholder('Explain why this entry needs to be reversed…'),
                    ])
                    ->action(function (JournalEntry $record, array $data) {
                        try {
                            $reversal = app(JournalEntryService::class)->reverse(
                                $record, auth()->user(), $data['reason']
                            );
                            Notification::make()
                                ->title("Reversal [{$reversal->reference_number}] created and posted.")
                                ->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Reversal failed')
                                ->body($e->getMessage())
                                ->danger()->send();
                        }
                    }),
            ])

            ->defaultSort('transaction_date', 'desc')
            ->striped()
            ->paginated([25, 50, 100])
            ->deferLoading()
            ->poll('120s');
    }

    // ── Infolist (View Page) ──────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([

            // ── Entry Header ──────────────────────────────────────────
            Section::make('Journal Entry Details')
                ->icon('heroicon-o-document-text')
                ->columns(4)
                ->schema([
                    TextEntry::make('reference_number')
                        ->label('Reference Number')
                        ->fontFamily('mono')
                        ->badge()
                        ->color('primary')
                        ->copyable(),

                    TextEntry::make('status')
                        ->label('Status')
                        ->formatStateUsing(fn ($state) => JournalEntry::statuses()[$state] ?? $state)
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'draft'            => 'gray',
                            'pending_approval' => 'warning',
                            'approved'         => 'info',
                            'posted'           => 'success',
                            'reversed'         => 'danger',
                            default            => 'gray',
                        }),

                    TextEntry::make('source')
                        ->label('Source')
                        ->formatStateUsing(fn ($state) => JournalEntry::sources()[$state] ?? $state)
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('transaction_date')
                        ->label('Transaction Date')
                        ->date('d F Y'),

                    TextEntry::make('period.name')
                        ->label('Accounting Period')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('currency.code')
                        ->label('Currency')
                        ->badge()
                        ->color('info'),

                    TextEntry::make('exchange_rate_to_base')
                        ->label('Exchange Rate to ETB')
                        ->fontFamily('mono'),

                    TextEntry::make('preparedBy.name')
                        ->label('Prepared By'),

                    TextEntry::make('approvedBy.name')
                        ->label('Approved By')
                        ->placeholder('Not yet approved'),

                    TextEntry::make('posted_at')
                        ->label('Posted At')
                        ->dateTime('d M Y H:i')
                        ->placeholder('Not yet posted'),

                    TextEntry::make('description')
                        ->label('Description / Memo')
                        ->columnSpanFull()
                        ->placeholder('No memo'),
                ]),

            // ── Reversal Link ─────────────────────────────────────────
            Section::make('Reversal Information')
                ->icon('heroicon-o-arrow-path')
                ->columns(2)
                ->hidden(fn (JournalEntry $record) =>
                    ! $record->reversal_of_id && $record->reversals()->doesntExist()
                )
                ->schema([
                    TextEntry::make('reversalOf.reference_number')
                        ->label('Reversal Of')
                        ->fontFamily('mono')
                        ->badge()
                        ->color('warning')
                        ->placeholder('—'),

                    TextEntry::make('notes')
                        ->label('Reversal Reason')
                        ->placeholder('—'),
                ]),

            // ── Journal Entry Lines ───────────────────────────────────
            Section::make('Journal Entry Lines')
                ->icon('heroicon-o-table-cells')
                ->schema([
                    RepeatableEntry::make('lines')
                        ->label('')
                        ->columns(6)
                        ->schema([
                            TextEntry::make('account.code')
                                ->label('Code')
                                ->fontFamily('mono')
                                ->badge()
                                ->color('gray'),

                            TextEntry::make('account.name')
                                ->label('Account')
                                ->weight('semibold')
                                ->columnSpan(2),

                            TextEntry::make('debit')
                                ->label('Debit (DR)')
                                ->formatStateUsing(fn ($state) =>
                                    (float) $state > 0
                                        ? number_format((float) $state, 2)
                                        : '—'
                                )
                                ->fontFamily('mono')
                                ->color(fn ($state) => (float) $state > 0 ? 'success' : 'gray')
                                ->weight(fn ($state) => (float) $state > 0 ? 'bold' : 'normal')
                                ->alignEnd(),

                            TextEntry::make('credit')
                                ->label('Credit (CR)')
                                ->formatStateUsing(fn ($state) =>
                                    (float) $state > 0
                                        ? number_format((float) $state, 2)
                                        : '—'
                                )
                                ->fontFamily('mono')
                                ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'gray')
                                ->weight(fn ($state) => (float) $state > 0 ? 'bold' : 'normal')
                                ->alignEnd(),

                            // Combined dimension column: shows first non-empty dimension
                            TextEntry::make('activity_code')
                                ->label('Details')
                                ->getStateUsing(fn ($record) => collect([
                                    $record->costCenter?->code ? 'CC: ' . $record->costCenter->code : null,
                                    $record->donor?->organization_name ? 'Donor: ' . $record->donor->organization_name : null,
                                    $record->activity_code ? 'Act: ' . $record->activity_code : null,
                                ])->filter()->implode(' · ') ?: '—')
                                ->fontFamily('mono')
                                ->color('gray')
                                ->placeholder('—'),
                        ]),
                ]),

            // ── Balance Summary ───────────────────────────────────────
            Section::make('Balance Summary')
                ->icon('heroicon-o-scale')
                ->columns(3)
                ->schema([
                    TextEntry::make('total_debit')
                        ->label('Total Debit (DR)')
                        ->getStateUsing(fn (JournalEntry $record) =>
                            number_format($record->lines()->sum('debit'), 2)
                        )
                        ->fontFamily('mono')
                        ->color('success')
                        ->weight('bold'),

                    TextEntry::make('total_credit')
                        ->label('Total Credit (CR)')
                        ->getStateUsing(fn (JournalEntry $record) =>
                            number_format($record->lines()->sum('credit'), 2)
                        )
                        ->fontFamily('mono')
                        ->color('danger')
                        ->weight('bold'),

                    TextEntry::make('balance_status')
                        ->label('Balance Status')
                        ->getStateUsing(fn (JournalEntry $record) => $record->isBalanced()
                            ? 'balanced'
                            : 'unbalanced'
                        )
                        ->formatStateUsing(fn ($state, JournalEntry $record) => $state === 'balanced'
                            ? 'Balanced ✓'
                            : 'Unbalanced ✗  (diff: ' . number_format($record->balance_difference, 2) . ')'
                        )
                        ->badge()
                        ->color(fn ($state) => $state === 'balanced' ? 'success' : 'danger'),
                ]),

            // ── Audit Trail ───────────────────────────────────────────
            Section::make('Audit Trail')
                ->icon('heroicon-o-shield-check')
                ->collapsible()
                ->collapsed()
                ->schema([
                    RepeatableEntry::make('auditLogs')
                        ->label('')
                        ->columns(5)
                        ->schema([
                            TextEntry::make('action')
                                ->label('Action')
                                ->formatStateUsing(fn ($state) => FinanceAuditLog::actions()[$state] ?? $state)
                                ->badge()
                                ->color(fn ($state) => FinanceAuditLog::actionColor($state)),

                            TextEntry::make('actor.name')
                                ->label('By')
                                ->placeholder('System'),

                            TextEntry::make('created_at')
                                ->label('When')
                                ->since()
                                ->tooltip(fn ($state) => $state),

                            TextEntry::make('old_values')
                                ->label('Before')
                                ->formatStateUsing(fn ($state) =>
                                    is_array($state)
                                        ? collect($state)->map(fn ($v, $k) => "{$k}: {$v}")->implode(' | ')
                                        : '—'
                                )
                                ->placeholder('—')
                                ->limit(60),

                            TextEntry::make('new_values')
                                ->label('After')
                                ->formatStateUsing(fn ($state) =>
                                    is_array($state)
                                        ? collect($state)->map(fn ($v, $k) => "{$k}: {$v}")->implode(' | ')
                                        : '—'
                                )
                                ->placeholder('—')
                                ->limit(60),
                        ]),
                ]),
        ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListJournalEntries::route('/'),
            'create' => CreateJournalEntry::route('/create'),
            'view'   => ViewJournalEntry::route('/{record}'),
            'edit'   => EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
