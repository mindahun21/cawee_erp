<?php

namespace App\Filament\Pages\Finance;

use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankReconciliation;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action as TableAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class Reconcile extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.finance.reconcile';

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-adjustments-horizontal';
    protected static string|UnitEnum|null   $navigationGroup = 'Finance';
    protected static ?string                $navigationLabel = 'Reconcile';
    protected static ?int                   $navigationSort  = 5;
    protected static ?string                $title           = 'Bank Reconciliation';
    protected static ?string                $slug            = 'finance/reconcile';

    // ── Form state ────────────────────────────────────────────────────

    public ?int    $bank_account_id   = null;
    public ?float  $beginning_balance = null;
    public ?float  $ending_balance    = null;
    public ?string $ending_date       = null;

    // ── Auth ──────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user instanceof User &&
               ($user->isFinanceOfficer() || $user->isFinanceManager() || $user->isSuperAdmin());
    }

    // ── Lifecycle ─────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->mountInteractsWithTable();
    }

    // ── Table — full reconciliation list ──────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->heading('All Reconciliations')
            ->description('Full history of bank reconciliation statements. Use Resume to continue an in-progress reconciliation, or View to inspect a completed one.')
            ->query(
                BankReconciliation::query()
                    ->with(['bankAccount', 'period', 'preparedBy'])
                    ->latest('statement_date')
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Reference')
                    ->badge()->color('primary')->fontFamily('mono')
                    ->searchable()->sortable()->copyable(),

                TextColumn::make('bankAccount.account_name')
                    ->label('Bank Account')
                    ->searchable()->weight('semibold')
                    ->description(fn (BankReconciliation $r): string =>
                        (string) ($r->bankAccount?->bank_name . ' — ' . $r->bankAccount?->account_number)
                    ),

                TextColumn::make('period.name')
                    ->label('Period')
                    ->badge()->color('gray')->sortable(),

                TextColumn::make('statement_date')
                    ->label('Statement Date')
                    ->date('d M Y')->sortable(),

                TextColumn::make('statement_balance')
                    ->label('Bank Balance')
                    ->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->color('info'),

                TextColumn::make('gl_balance')
                    ->label('GL Balance')
                    ->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->color('gray'),

                TextColumn::make('difference')
                    ->label('Difference')
                    ->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->badge()
                    // @phpstan-ignore-next-line
                    ->color(fn (BankReconciliation $r): string =>
                        abs((float) $r->difference) < 0.01 ? 'success' : 'danger'
                    )->sortable(),

                TextColumn::make('status')
                    ->label('Status')->badge()
                    ->formatStateUsing(fn ($s): string => BankReconciliation::statuses()[$s] ?? ucfirst((string) $s))
                    // @phpstan-ignore-next-line
                    ->color(fn ($s): string => match ($s) {
                        'reconciled'  => 'success',
                        'in_progress' => 'warning',
                        'locked'      => 'info',
                        default       => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('bank_account_id')
                    ->label('Bank Account')
                    // @phpstan-ignore-next-line
                    ->options(fn () => BankAccount::where('is_active', true)
                        ->orderBy('account_name')
                        ->get()
                        ->mapWithKeys(fn ($b) => [(string) $b->id => "{$b->bank_name} — {$b->account_name}"])
                        ->toArray()
                    )->searchable(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(BankReconciliation::statuses()),
            ])
            ->recordActions([
                TableAction::make('resume')
                    ->label('Resume')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->button()
                    ->visible(fn ($record) => $record->status === 'in_progress')
                    ->url(fn ($record) => route(
                        'filament.admin.resources.finance.bank.reconciliations.edit', $record
                    )),

                TableAction::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn ($record) => $record->status !== 'in_progress')
                    ->url(fn ($record) => route(
                        'filament.admin.resources.finance.bank.reconciliations.view', $record
                    )),

                TableAction::make('mark_reconciled')
                    ->label('Mark Reconciled')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->button()
                    ->visible(fn ($record) =>
                        $record->status === 'in_progress' && abs((float) $record->difference) < 0.01
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->forceFill([
                            'status'        => 'reconciled',
                            'reviewed_by'   => auth()->id(),
                            'reconciled_at' => now(),
                        ])->save();
                        Notification::make()->success()->title('Reconciliation completed.')->send();
                    }),
            ])
            ->defaultSort('statement_date', 'desc')
            ->striped()
            ->paginated(true);
    }

    // ── Livewire hook — auto-fill beginning balance ────────────────────

    public function updatedBankAccountId(?string $value): void
    {
        if (!$value) {
            $this->beginning_balance = null;
            return;
        }

        $last = BankReconciliation::where('bank_account_id', (int) $value)
            ->where('status', 'reconciled')
            ->orderByDesc('statement_date')
            ->value('adjusted_bank_balance');

        $this->beginning_balance = $last !== null ? (float) $last : 0.00;
    }

    // ── Start Reconciling action ───────────────────────────────────────

    public function startReconciling(): void
    {
        $this->validate([
            'bank_account_id'   => 'required|exists:finance_bank_accounts,id',
            'ending_balance'    => 'required|numeric',
            'ending_date'       => 'required|date',
            'beginning_balance' => 'nullable|numeric',
        ]);

        // If an in-progress reconciliation already exists, resume it
        $existing = BankReconciliation::where('bank_account_id', $this->bank_account_id)
            ->where('status', 'in_progress')
            ->latest('statement_date')
            ->first();

        if ($existing) {
            Notification::make()
                ->info()
                ->title('Resuming existing reconciliation')
                ->body("{$existing->reference} was already in progress.")
                ->send();

            $this->redirect(
                route('filament.admin.resources.finance.bank.reconciliations.edit', $existing)
            );
            return;
        }

        // Generate reference
        $year = now()->year;
        $last = BankReconciliation::where('reference', 'like', "BR-{$year}-%")
            ->orderByRaw('LENGTH(reference) DESC')
            ->orderBy('reference', 'desc')
            ->value('reference');
        $seq       = $last ? ((int) last(explode('-', $last))) + 1 : 1;
        $reference = sprintf('BR-%d-%04d', $year, $seq);

        // Match to an accounting period
        $period = AccountingPeriod::where('start_date', '<=', $this->ending_date)
            ->where('end_date', '>=', $this->ending_date)
            ->first();

        $beginningBalance = (float) ($this->beginning_balance ?? 0);
        $endingBalance    = (float) ($this->ending_balance ?? 0);

        $reconciliation = BankReconciliation::create([
            'reference'             => $reference,
            'bank_account_id'       => $this->bank_account_id,
            'accounting_period_id'  => $period?->id,
            'statement_date'        => $this->ending_date,
            'statement_balance'     => $endingBalance,
            'gl_balance'            => $beginningBalance,
            'outstanding_deposits'  => 0,
            'outstanding_cheques'   => 0,
            'adjusted_bank_balance' => $endingBalance,
            'difference'            => $endingBalance - $beginningBalance,
            'status'                => 'in_progress',
            'prepared_by'           => auth()->id(),
        ]);

        Notification::make()
            ->success()
            ->title('Reconciliation started — ' . $reference)
            ->body('Add your outstanding deposits and cheques, then mark items as cleared.')
            ->send();

        $this->redirect(
            route('filament.admin.resources.finance.bank.reconciliations.edit', $reconciliation)
        );
    }

    // ── View data ─────────────────────────────────────────────────────

    protected function getViewData(): array
    {
        $bankAccounts = BankAccount::activeOptions();

        $lastReconciliation = $this->bank_account_id
            ? BankReconciliation::where('bank_account_id', $this->bank_account_id)
                ->where('status', 'reconciled')
                ->orderByDesc('statement_date')
                ->first()
            : null;

        $inProgressReconciliation = $this->bank_account_id
            ? BankReconciliation::where('bank_account_id', $this->bank_account_id)
                ->where('status', 'in_progress')
                ->latest('statement_date')
                ->first()
            : null;

        return compact('bankAccounts', 'lastReconciliation', 'inProgressReconciliation');
    }
}
