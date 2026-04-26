<?php

namespace App\Filament\Resources\Finance\Journals;

use App\Filament\Resources\Finance\Journals\Pages\ListGeneralLedgers;
use App\Models\Currency;
use App\Models\Donor;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\CostCenter;
use App\Models\Finance\GeneralLedger;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use App\Traits\BelongsToModule;

class GeneralLedgerResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = GeneralLedger::class;

    // ── Navigation ────────────────────────────────────────────────────

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'General Ledger';

    protected static ?int $navigationSort = 25;

    protected static bool $shouldSkipAuthorization = true;

    // ── Authorization ─────────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }

    // The GL is read-only — no create, edit, or delete
    public static function canCreate(): bool        { return false; }
    public static function canEdit($r): bool        { return false; }
    public static function canDelete($r): bool      { return false; }
    public static function canDeleteAny(): bool     { return false; }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                // Always eager-load relations needed for columns and filters
                GeneralLedger::query()
                    ->with([
                        'account',
                        'account.accountType',
                        'journalEntryLine',
                        'journalEntryLine.journalEntry',
                        'journalEntryLine.costCenter',
                        'journalEntryLine.donor',
                        'journalEntryLine.project',
                        'currency',
                        'period',
                    ])
                    ->orderBy('transaction_date', 'desc')
                    ->orderBy('id', 'desc')
            )
            ->columns([

                // ── Date ─────────────────────────────────────────────
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable()
                    ->width('100px'),

                // ── Account ───────────────────────────────────────────
                TextColumn::make('account.code')
                    ->label('Account Code')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray')
                    ->width('110px'),

                TextColumn::make('account.name')
                    ->label('Account Name')
                    ->searchable()
                    ->sortable()
                    ->limit(35)
                    ->tooltip(fn ($state) => $state),

                // ── Journal Entry Reference ───────────────────────────
                TextColumn::make('journalEntryLine.journalEntry.reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->badge()
                    ->color('primary')
                    ->url(fn (GeneralLedger $record): ?string =>
                        $record->journalEntryLine?->journalEntry?->id
                            ? route('filament.admin.resources.finance.journals.journal-entries.view', [
                                'record' => $record->journalEntryLine->journalEntry->id,
                              ])
                            : null
                    )
                    ->openUrlInNewTab(),

                // ── Narration / Description ───────────────────────────
                TextColumn::make('journalEntryLine.narration')
                    ->label('Narration')
                    ->searchable()
                    ->limit(40)
                    ->placeholder('—')
                    ->tooltip(fn ($state) => $state),

                // ── 4-Dimension NGO Coding ────────────────────────────
                TextColumn::make('journalEntryLine.costCenter.code')
                    ->label('Cost Centre')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('journalEntryLine.donor.organization_name')
                    ->label('Donor')
                    ->limit(20)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('journalEntryLine.activity_code')
                    ->label('Activity')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('warning')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── Currency ──────────────────────────────────────────
                TextColumn::make('currency.code')
                    ->label('CCY')
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── Debit ─────────────────────────────────────────────
                TextColumn::make('debit')
                    ->label('Debit (DR)')
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->formatStateUsing(fn ($state) =>
                        (float) $state > 0
                            ? number_format((float) $state, 2)
                            : '—'
                    )
                    ->color(fn ($state) => (float) $state > 0 ? 'success' : 'gray')
                    ->weight(fn ($state) => (float) $state > 0 ? 'semibold' : 'normal'),

                // ── Credit ────────────────────────────────────────────
                TextColumn::make('credit')
                    ->label('Credit (CR)')
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->formatStateUsing(fn ($state) =>
                        (float) $state > 0
                            ? number_format((float) $state, 2)
                            : '—'
                    )
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'gray')
                    ->weight(fn ($state) => (float) $state > 0 ? 'semibold' : 'normal'),

                // ── Running Balance ───────────────────────────────────
                TextColumn::make('running_balance')
                    ->label('Balance')
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->color(fn ($state) => (float) $state >= 0 ? 'success' : 'danger')
                    ->weight('bold')
                    ->tooltip('Running balance relative to account normal balance. Positive = account on normal side.'),

                // ── Period ────────────────────────────────────────────
                TextColumn::make('period.name')
                    ->label('Period')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // ── Filters ───────────────────────────────────────────────
            ->filters([

                // Account filter — searchable dropdown
                SelectFilter::make('account_id')
                    ->label('Account')
                    ->options(fn () => ChartOfAccount::where('is_active', true)
                        ->where('is_header', false)
                        ->orderBy('code')
                        ->get()
                        ->mapWithKeys(fn ($a) => [$a->id => "[{$a->code}] {$a->name}"])
                        ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                // Accounting period filter
                SelectFilter::make('period_id')
                    ->label('Accounting Period')
                    ->options(fn () => AccountingPeriod::orderByDesc('fiscal_year')
                        ->orderByDesc('period_number')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable(),

                // Currency filter
                SelectFilter::make('currency_id')
                    ->label('Currency')
                    ->options(fn () => Currency::orderBy('code')->pluck('code', 'id')->toArray())
                    ->searchable(),

                // Date from / to
                Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('From Date')
                            ->native(false),
                        DatePicker::make('date_to')
                            ->label('To Date')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn ($q, $v) => $q->where('transaction_date', '>=', $v)
                            )
                            ->when(
                                $data['date_to'],
                                fn ($q, $v) => $q->where('transaction_date', '<=', $v)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from'] ?? null) {
                            $indicators[] = 'From: ' . \Carbon\Carbon::parse($data['date_from'])->format('d M Y');
                        }
                        if ($data['date_to'] ?? null) {
                            $indicators[] = 'To: ' . \Carbon\Carbon::parse($data['date_to'])->format('d M Y');
                        }
                        return $indicators;
                    }),

                // Cost Centre filter (via JournalEntryLine)
                Filter::make('cost_center_id')
                    ->label('Cost Centre')
                    ->form([
                        Select::make('cost_center_id')
                            ->label('Cost Centre')
                            ->options(fn () => CostCenter::where('is_active', true)
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn ($c) => [$c->id => "[{$c->code}] {$c->name}"])
                                ->toArray()
                            )
                            ->searchable()
                            ->nullable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['cost_center_id'] ?? null,
                            fn ($q, $v) => $q->whereHas('journalEntryLine', fn ($jq) =>
                                $jq->where('cost_center_id', $v)
                            )
                        );
                    }),

                // Donor filter (via JournalEntryLine)
                Filter::make('donor_id')
                    ->label('Donor')
                    ->form([
                        Select::make('donor_id')
                            ->label('Donor')
                            ->options(fn () => Donor::orderBy('organization_name')
                                ->get()
                                ->mapWithKeys(fn ($d) => [$d->id => $d->full_name ?? $d->organization_name])
                                ->toArray()
                            )
                            ->searchable()
                            ->nullable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['donor_id'] ?? null,
                            fn ($q, $v) => $q->whereHas('journalEntryLine', fn ($jq) =>
                                $jq->where('donor_id', $v)
                            )
                        );
                    }),

                // Project filter (via JournalEntryLine)
                Filter::make('project_id')
                    ->label('Project')
                    ->form([
                        Select::make('project_id')
                            ->label('Project')
                            ->options(fn () => Project::orderBy('project_name')
                                ->pluck('project_name', 'id')
                                ->toArray()
                            )
                            ->searchable()
                            ->nullable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['project_id'] ?? null,
                            fn ($q, $v) => $q->whereHas('journalEntryLine', fn ($jq) =>
                                $jq->where('project_id', $v)
                            )
                        );
                    }),
            ])

            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->defaultSort('transaction_date', 'desc')
            ->striped()
            ->paginated([50, 100, 200])
            ->poll('60s')  // Auto-refresh every 60 s — keeps the GL view current
            ->deferLoading()
            ->heading('General Ledger')
            ->description('Read-only view of all posted journal entry lines. Use the filters above to drill into a specific account, period, or NGO dimension.')
            ->emptyStateIcon('heroicon-o-table-cells')
            ->emptyStateHeading('No GL entries found')
            ->emptyStateDescription('Post a Journal Entry to see entries appear here. Adjust the active filters if you expected to see data.');
    }

    // ── No form / infolist — GL is read-only ─────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => ListGeneralLedgers::route('/'),
        ];
    }
}
