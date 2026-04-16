<?php

namespace App\Filament\Resources\Finance\Bank;

use App\Filament\Resources\Finance\Bank\Pages\CreateFundTransfer;
use App\Filament\Resources\Finance\Bank\Pages\EditFundTransfer;
use App\Filament\Resources\Finance\Bank\Pages\ListFundTransfers;
use App\Filament\Resources\Finance\Bank\Pages\ViewFundTransfer;
use App\Models\Currency;
use App\Models\Donor;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\BankAccount;
use App\Models\Finance\CostCenter;
use App\Models\Finance\FundTransfer;
use App\Models\Finance\FinanceAuditLog;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use App\Models\Finance\FinanceSetting;
use App\Models\Project;
use App\Models\User;
use App\Services\Finance\GeneralLedgerService;
use App\Services\Finance\JournalEntryService;
use BackedEnum;
use Filament\Actions\Action as TblAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class FundTransferResource extends Resource
{
    protected static ?string $model = FundTransfer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Fund Transfers';
    protected static ?int $navigationSort = 35;
    protected static ?string $recordTitleAttribute = 'transfer_number';
    protected static bool $shouldSkipAuthorization = true;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }
    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($r): bool   { return $r->isDraft() && static::canViewAny(); }
    public static function canDelete($r): bool { return $r->isDraft() && static::canViewAny(); }

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Transfer Details')
                ->description('Transfer funds between two bank accounts.')
                ->icon('heroicon-o-arrows-right-left')
                ->columns(3)
                ->schema([
                    TextInput::make('transfer_number')
                        ->label('Transfer Reference')
                        ->disabled()->dehydrated()
                        ->placeholder('Auto-generated on save'),

                    Select::make('accounting_period_id')
                        ->label('Accounting Period')
                        ->options(AccountingPeriod::openOptions())
                        ->required()->native(false),

                    DatePicker::make('transfer_date')
                        ->label('Transfer Date')
                        ->required()->native(false)
                        ->default(now()->toDateString()),

                    Select::make('from_bank_account_id')
                        ->label('From Account (Source)')
                        ->options(BankAccount::activeOptions())
                        ->required()->native(false)->searchable()->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if (!$state) return;
                            $acct = BankAccount::with('currency')->find($state);
                            if ($acct) {
                                $set('currency_id', $acct->currency_id);
                                $set('exchange_rate_to_base', 1);
                            }
                        })
                        ->helperText(fn (Get $get) => $get('from_bank_account_id')
                            ? (function() use ($get) {
                                $acct = BankAccount::with('currency')->find($get('from_bank_account_id'));
                                if (!$acct) return 'Select the source account';
                                $bal  = number_format((float)$acct->current_balance, 2);
                                $ccy  = $acct->currency?->code ?? '';
                                $warn = (float)$acct->current_balance <= 0 ? ' ⛔ ZERO balance!' : '';
                                return "Available balance: {$bal} {$ccy}{$warn}";
                            })()
                            : 'Select the source account'
                        ),

                    Select::make('to_bank_account_id')
                        ->label('To Account (Destination)')
                        ->options(BankAccount::activeOptions())
                        ->required()->native(false)->searchable()->live()
                        ->helperText(fn (Get $get) => $get('to_bank_account_id')
                            ? (function() use ($get) {
                                $from = BankAccount::with('currency')->find($get('from_bank_account_id'));
                                $to   = BankAccount::with('currency')->find($get('to_bank_account_id'));
                                if (!$to) return '';
                                if ($get('from_bank_account_id') === $get('to_bank_account_id'))
                                    return '⛔ Cannot transfer to the same account!';
                                $bal = number_format((float)$to->current_balance, 2);
                                $ccy = $to->currency?->code ?? '';
                                $msg = "Current balance: {$bal} {$ccy}";
                                if ($from && $from->currency_id !== $to->currency_id)
                                    $msg .= ' | ⚠ Cross-currency — verify exchange rate below.';
                                return $msg;
                            })()
                            : 'Select the destination account'
                        ),

                    Select::make('currency_id')
                        ->label('Transfer Currency')
                        ->options(Currency::pluck('code', 'id')->toArray())
                        ->required()->native(false)
                        ->default(fn () => Currency::where('code', 'ETB')->value('id'))
                        ->helperText('Auto-filled when you pick the source account'),

                    TextInput::make('amount')
                        ->label('Transfer Amount')
                        ->required()->numeric()->minValue(0.01)->live(debounce: 600)
                        ->extraInputAttributes(['class' => 'font-mono'])
                        ->helperText(fn (Get $get) => $get('from_bank_account_id')
                            ? (function() use ($get) {
                                $acct    = BankAccount::with('currency')->find($get('from_bank_account_id'));
                                $amount  = (float)($get('amount') ?? 0);
                                if (!$acct || $amount <= 0) return null;
                                $balance = (float)$acct->current_balance;
                                if ($amount > $balance)
                                    return '⛔ Insufficient funds! Available: '
                                        . number_format($balance, 2) . ' ' . ($acct->currency?->code ?? '');
                                return '✅ Sufficient funds available';
                            })()
                            : null
                        ),

                    TextInput::make('exchange_rate_to_base')
                        ->label('Exchange Rate (1 dest. currency = ? ETB)')
                        ->numeric()->default(1)->live(debounce: 600)
                        ->extraInputAttributes(['class' => 'font-mono'])
                        ->helperText(fn (Get $get) => (function() use ($get) {
                            $from   = BankAccount::with('currency')->find($get('from_bank_account_id'));
                            $to     = BankAccount::with('currency')->find($get('to_bank_account_id'));
                            $amount = (float)($get('amount') ?? 0);
                            $rate   = (float)($get('exchange_rate_to_base') ?? 1);
                            if (!$from || !$to || $amount <= 0 || $rate <= 0)
                                return 'Set 1 for same-currency transfers';
                            $fromCcy = $from->currency?->code ?? '';
                            $toCcy   = $to->currency?->code ?? '';
                            if ($from->currency_id === $to->currency_id)
                                return "Same currency ({$fromCcy}) — rate should be 1";
                            $dest = number_format($amount / $rate, 2);
                            return "Recipient receives ≈ {$dest} {$toCcy} (rate: 1 {$toCcy} = {$rate} ETB)";
                        })()),

                    Textarea::make('purpose')
                        ->label('Purpose / Narration')
                        ->required()->rows(2)->columnSpanFull(),
                ]),

            Section::make('Dimension Coding')
                ->icon('heroicon-o-tag')
                ->columns(2)
                ->schema([
                    Select::make('from_cost_center_id')
                        ->label('From Cost Center')
                        ->options(fn () => CostCenter::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                        ->native(false)->searchable()->nullable(),

                    Select::make('to_cost_center_id')
                        ->label('To Cost Center')
                        ->options(fn () => CostCenter::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                        ->native(false)->searchable()->nullable(),

                    Select::make('project_id')
                        ->label('Project')
                        ->options(fn () => Project::orderBy('project_name')
                            ->pluck('project_name', 'id')
                            ->toArray()
                        )
                        ->native(false)->searchable()->nullable(),

                    Select::make('donor_id')
                        ->label('Donor')
                        ->options(fn () => Donor::orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn ($d) => [$d->id => $d->full_name])
                            ->toArray()
                        )
                        ->native(false)->searchable()->nullable(),

                    Textarea::make('notes')
                        ->label('Notes')->rows(2)->columnSpanFull()->nullable(),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transfer_number')
                    ->label('Transfer #')->fontFamily('mono')->badge()->color('primary')->searchable()->sortable(),
                TextColumn::make('transfer_date')->label('Date')->date()->sortable(),
                TextColumn::make('fromBankAccount.account_name')->label('From')->limit(25),
                TextColumn::make('toBankAccount.account_name')->label('To')->limit(25),
                TextColumn::make('amount')
                    ->label('Amount')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono')->weight('semibold'),
                TextColumn::make('currency.code')->label('CCY')->badge()->color('gray'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft'       => 'gray', 'approved' => 'info', 'remitted' => 'warning',
                        'confirmed'   => 'success', 'reconciled' => 'primary', default => 'gray',
                    }),
            ])
            ->filters([SelectFilter::make('status')->options(FundTransfer::statuses())])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (FundTransfer $record) => $record->isDraft()),
                DeleteAction::make()->visible(fn (FundTransfer $record) => $record->isDraft()),

                // ── Inline workflow actions (same pattern as Procurement) ──
                TblAction::make('tbl_approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->button()
                    ->visible(fn (FundTransfer $record) =>
                        $record->isDraft() &&
                        (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin())
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Approve Fund Transfer')
                    ->modalDescription(fn (FundTransfer $record) =>
                        "Approve transfer {$record->transfer_number} of "
                        . number_format((float)$record->amount, 2) . ' '
                        . ($record->currency?->code ?? '') . '?'
                    )
                    ->action(function (FundTransfer $record) {
                        $user = auth()->user();
                        $record->forceFill([
                            'status'      => 'approved',
                            'approved_by' => $user->id,
                            'approved_at' => now(),
                        ])->save();
                        FinanceAuditLog::record('approve', $record,
                            ['status' => 'draft'], ['status' => 'approved']
                        );
                        Notification::make()->success()->title('Transfer approved.')->send();
                    }),

                TblAction::make('tbl_remit')
                    ->label('Mark Remitted')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->button()
                    ->visible(fn (FundTransfer $record) => $record->isApproved())
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Remitted')
                    ->modalDescription(fn (FundTransfer $record) =>
                        "Mark {$record->transfer_number} as sent/remitted to destination?"
                    )
                    ->action(function (FundTransfer $record) {
                        $record->forceFill(['status' => 'remitted'])->save();
                        Notification::make()->success()->title('Marked as remitted.')->send();
                    }),

                TblAction::make('tbl_confirm')
                    ->label('Confirm & Post GL')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->button()
                    ->visible(fn (FundTransfer $record) => $record->isRemitted())
                    ->form([
                        TextInput::make('confirmation_reference')
                            ->label('Bank / Receipt Reference')
                            ->required()->maxLength(80)
                            ->placeholder('e.g. TRN-20240416-001'),
                    ])
                    ->modalHeading('Confirm Receipt & Post GL')
                    ->modalDescription('Enter the bank confirmation reference number to finalize.')
                    ->action(function (FundTransfer $record, array $data) {
                        try {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                                $user      = auth()->user();
                                $jeService = app(JournalEntryService::class);
                                $glService = app(GeneralLedgerService::class);
                                $amount    = (float) $record->amount;

                                $fromCoaId = $record->fromBankAccount?->chart_of_account_id
                                    ?? FinanceSetting::get('default_bank_account_id');
                                $toCoaId   = $record->toBankAccount?->chart_of_account_id
                                    ?? FinanceSetting::get('default_bank_account_id');

                                $je = JournalEntry::create([
                                    'reference_number'      => $jeService->generateReference(now()->year),
                                    'accounting_period_id'  => $record->accounting_period_id,
                                    'transaction_date'      => now()->toDateString(),
                                    'description'           => "Fund Transfer {$record->transfer_number} — {$record->purpose}",
                                    'status'                => 'approved',
                                    'source'                => 'fund_transfer',
                                    'source_type'           => FundTransfer::class,
                                    'source_id'             => $record->id,
                                    'prepared_by'           => $user->id,
                                    'approved_by'           => $user->id,
                                    'currency_id'           => $record->currency_id,
                                    'exchange_rate_to_base' => $record->exchange_rate_to_base,
                                ]);

                                // DR: To account, CR: From account
                                JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $toCoaId,   'debit' => $amount, 'credit' => 0,      'narration' => "Received: {$record->toBankAccount?->account_name}"]);
                                JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $fromCoaId, 'debit' => 0,       'credit' => $amount, 'narration' => "Sent: {$record->fromBankAccount?->account_name}"]);

                                $je->load('lines');
                                $glService->postJournalEntry($je);
                                $je->forceFill(['status' => 'posted', 'posted_at' => now()])->save();

                                BankAccount::where('id', $record->from_bank_account_id)->decrement('current_balance', $amount);
                                BankAccount::where('id', $record->to_bank_account_id)->increment('current_balance', $amount);

                                $record->forceFill([
                                    'status'                 => 'confirmed',
                                    'confirmed_by'           => $user->id,
                                    'confirmed_at'           => now(),
                                    'confirmation_reference' => $data['confirmation_reference'],
                                    'journal_entry_id'       => $je->id,
                                ])->save();

                                FinanceAuditLog::record('post', $record,
                                    ['status' => 'remitted'], ['status' => 'confirmed', 'je' => $je->reference_number]
                                );
                            });
                            Notification::make()->success()->title('Transfer confirmed! GL posted & balances updated.')->send();
                        } catch (\Throwable $e) {
                            Notification::make()->danger()->title('Failed')->body($e->getMessage())->send();
                        }
                    }),
            ])
            ->defaultSort('transfer_date', 'desc');
    }

    // ── Infolist ──────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Transfer Header')
                ->icon('heroicon-o-arrows-right-left')
                ->columns(4)
                ->schema([
                    TextEntry::make('transfer_number')->label('Transfer #')->badge()->color('primary')->fontFamily('mono'),
                    TextEntry::make('transfer_date')->label('Date')->date(),
                    TextEntry::make('currency.code')->label('Currency')->badge()->color('primary'),
                    TextEntry::make('status')->label('Status')->badge()
                        ->color(fn ($state) => match ($state) {
                            'draft' => 'gray', 'approved' => 'info', 'remitted' => 'warning',
                            'confirmed' => 'success', 'reconciled' => 'primary', default => 'gray',
                        }),
                    TextEntry::make('fromBankAccount.account_name')->label('From Account'),
                    TextEntry::make('toBankAccount.account_name')->label('To Account'),
                    TextEntry::make('amount')->label('Amount')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold'),
                    TextEntry::make('exchange_rate_to_base')->label('Exchange Rate')->fontFamily('mono'),
                    TextEntry::make('purpose')->label('Purpose')->columnSpanFull(),
                ]),
            Section::make('Workflow')
                ->icon('heroicon-o-user-circle')
                ->columns(3)
                ->schema([
                    TextEntry::make('preparedBy.name')->label('Prepared By'),
                    TextEntry::make('approvedBy.name')->label('Approved By')->placeholder('—'),
                    TextEntry::make('approved_at')->label('Approved At')->dateTime()->placeholder('—'),
                    TextEntry::make('confirmedBy.name')->label('Confirmed By')->placeholder('—'),
                    TextEntry::make('confirmed_at')->label('Confirmed At')->dateTime()->placeholder('—'),
                    TextEntry::make('confirmation_reference')->label('Confirmation Ref')->placeholder('—'),
                    TextEntry::make('journalEntry.reference_number')->label('Journal Entry')->placeholder('Not yet posted'),
                ]),
        ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListFundTransfers::route('/'),
            'create' => CreateFundTransfer::route('/create'),
            'view'   => ViewFundTransfer::route('/{record}'),
            'edit'   => EditFundTransfer::route('/{record}/edit'),
        ];
    }
}
