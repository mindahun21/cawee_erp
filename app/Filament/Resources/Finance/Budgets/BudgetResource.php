<?php

namespace App\Filament\Resources\Finance\Budgets;

use App\Filament\Resources\Finance\Budgets\Pages\CreateBudgets;
use App\Filament\Resources\Finance\Budgets\Pages\EditBudgets;
use App\Filament\Resources\Finance\Budgets\Pages\ListBudgets;
use App\Filament\Resources\Finance\Budgets\Pages\ViewBudgets;
use App\Models\Donor;
use App\Models\Finance\Budget;
use App\Models\Finance\BudgetType;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\CostCenter;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\Action as TblAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class BudgetResource extends Resource
{
    protected static ?string $model                          = Budget::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static string|UnitEnum|null $navigationGroup  = 'Finance';
    protected static ?string $navigationLabel               = 'Budgets';
    protected static ?int    $navigationSort                = 80;
    protected static ?string $slug                          = 'finance/budgets';
    protected static bool $shouldSkipAuthorization          = true;

    public static function canViewAny(): bool  { $u = auth()->user(); return $u && ($u->isFinanceOfficer() || $u->isFinanceManager() || $u->isSuperAdmin()); }
    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($r): bool   { return $r->isDraft() && static::canViewAny(); }
    public static function canDelete($r): bool { return $r->isDraft() && static::canViewAny(); }
    public static function canView($r): bool   { return static::canViewAny(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Budget Header')->icon('heroicon-o-document-text')->columns(3)->schema([
                TextInput::make('budget_code')->label('Budget Code')->disabled()->dehydrated()->placeholder('Auto-generated'),
                TextInput::make('name')->label('Budget Name')->required()->maxLength(200)->columnSpan(2),
                Select::make('budget_type_id')->label('Budget Type')->required()->native(false)
                    ->options(fn () => BudgetType::orderBy('name')->pluck('name', 'id')),
                TextInput::make('fiscal_year')->label('Fiscal Year')->numeric()->required()->default(now()->year),
                Select::make('currency_id')->label('Currency')->native(false)->nullable()
                    ->options(fn () => \App\Models\Currency::orderBy('code')->pluck('code', 'id')),
            ]),

            Section::make('4-Dimension Coding')->icon('heroicon-o-tag')->columns(3)->schema([
                Select::make('donor_id')->label('Donor')->native(false)->nullable()->searchable()
                    ->options(fn () => Donor::orderBy('first_name')->get()->mapWithKeys(fn ($d) => [$d->id => $d->full_name])),
                Select::make('project_id')->label('Project')->native(false)->nullable()->searchable()
                    ->options(fn () => Project::orderBy('project_name')->pluck('project_name', 'id')),
                Select::make('cost_center_id')->label('Cost Center')->native(false)->nullable()
                    ->options(fn () => CostCenter::where('is_active', true)->pluck('name', 'id')),
            ]),

            Section::make('Budget Lines')->icon('heroicon-o-list-bullet')->schema([
                Repeater::make('lines')->relationship('lines')->schema([
                    Select::make('account_id')->label('GL Account')->required()->native(false)->searchable()
                        ->options(fn () => ChartOfAccount::where('is_active', true)->orderBy('code')
                            ->get()->mapWithKeys(fn ($a) => [$a->id => "[{$a->code}] {$a->name}"]))->columnSpan(3),
                    TextInput::make('activity_code')->label('Activity Code')->nullable(),
                    TextInput::make('activity_description')->label('Description')->nullable()->columnSpan(2),
                    TextInput::make('q1_amount')->label('Q1')->numeric()->default(0),
                    TextInput::make('q2_amount')->label('Q2')->numeric()->default(0),
                    TextInput::make('q3_amount')->label('Q3')->numeric()->default(0),
                    TextInput::make('q4_amount')->label('Q4')->numeric()->default(0),
                    TextInput::make('total_budgeted')->label('Total')->disabled()->dehydrated()
                        ->numeric()->default(0),
                ])->columns(8)->addActionLabel('Add Budget Line')->columnSpanFull(),
            ]),

            Section::make('Notes')->schema([Textarea::make('notes')->rows(2)->nullable()->columnSpanFull()])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('budget_code')->label('Code')->badge()->color('primary')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('name')->label('Budget Name')->limit(30)->searchable(),
                TextColumn::make('budgetType.name')->label('Type')->badge()->color('gray'),
                TextColumn::make('fiscal_year')->label('Year')->sortable(),
                TextColumn::make('donor.first_name')->label('Donor')
                    ->formatStateUsing(fn ($s, $r) => $r->donor?->full_name ?? '—'),
                TextColumn::make('project.project_name')->label('Project')->limit(20)->placeholder('—'),
                TextColumn::make('total_budget_amount')->label('Budget')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono'),
                TextColumn::make('actual_spent')->label('Actual Spent')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono')
                    ->color(fn ($s, $r) => (float)$s > (float)$r->total_budget_amount ? 'danger' : 'success'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($s) => match($s) {
                        'draft' => 'gray', 'approved' => 'warning', 'active' => 'success',
                        'closed' => 'info', 'cancelled' => 'danger', default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->options(Budget::statuses()),
                SelectFilter::make('fiscal_year')->options(fn () =>
                    Budget::distinct()->orderByDesc('fiscal_year')->pluck('fiscal_year', 'fiscal_year')->toArray()
                ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (Budget $r) => $r->isDraft()),
                DeleteAction::make()->visible(fn (Budget $r) => $r->isDraft()),

                TblAction::make('approve')->label('Approve')->icon('heroicon-o-check-badge')->color('success')->button()
                    ->visible(fn (Budget $r) =>
                        $r->isDraft() && (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin()))
                    ->requiresConfirmation()
                    ->action(function (Budget $record) {
                        $record->forceFill([
                            'status'      => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ])->save();
                        Notification::make()->success()->title('Budget approved.')->send();
                    }),

                TblAction::make('activate')->label('Activate')->icon('heroicon-o-play')->color('info')->button()
                    ->visible(fn (Budget $r) =>
                        $r->isApproved() && (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin()))
                    ->requiresConfirmation()
                    ->action(function (Budget $record) {
                        $record->forceFill(['status' => 'active'])->save();
                        Notification::make()->success()->title('Budget activated.')->send();
                    }),
            ])
            ->defaultSort('fiscal_year', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Budget Details')->icon('heroicon-o-chart-bar')->columns(4)->schema([
                TextEntry::make('budget_code')->label('Code')->badge()->color('primary')->fontFamily('mono'),
                TextEntry::make('name')->label('Budget Name')->columnSpan(2),
                TextEntry::make('status')->label('Status')->badge()
                    ->color(fn ($s) => match($s) {
                        'draft' => 'gray', 'approved' => 'warning', 'active' => 'success',
                        'closed' => 'info', 'cancelled' => 'danger', default => 'gray',
                    }),
                TextEntry::make('budgetType.name')->label('Type')->badge()->color('gray'),
                TextEntry::make('fiscal_year')->label('Fiscal Year'),
                TextEntry::make('currency.code')->label('Currency')->badge()->color('gray'),
                TextEntry::make('approvedBy.name')->label('Approved By')->placeholder('Pending'),
            ]),
            Section::make('4-Dimension Coding')->icon('heroicon-o-tag')->columns(3)->schema([
                TextEntry::make('donor.first_name')->label('Donor')
                    ->formatStateUsing(fn ($s, $r) => $r->donor?->full_name ?? '—')->placeholder('—'),
                TextEntry::make('project.project_name')->label('Project')->placeholder('—'),
                TextEntry::make('costCenter.name')->label('Cost Center')->placeholder('—'),
            ]),
            Section::make('Budget Utilization')->icon('heroicon-o-calculator')->columns(4)->schema([
                TextEntry::make('total_budget_amount')->label('Total Budget')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold'),
                TextEntry::make('committed_amount')->label('Committed')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextEntry::make('encumbered_amount')->label('Encumbered')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextEntry::make('actual_spent')->label('Actual Spent')->numeric(decimalPlaces: 2)->fontFamily('mono')
                    ->color(fn ($s, $r) => (float)$s > (float)$r->total_budget_amount ? 'danger' : 'success'),
                TextEntry::make('id')->label('Remaining')
                    ->state(fn (Budget $r) => number_format($r->remaining(), 2))
                    ->fontFamily('mono')->weight('bold')
                    ->color(fn ($s, $r) => $r->remaining() < 0 ? 'danger' : 'success'),
                TextEntry::make('id')->label('Utilization %')
                    ->state(fn (Budget $r) => $r->utilizationPct() . '%')
                    ->badge()->color(fn ($s, $r) => $r->utilizationPct() > 90 ? 'danger' : ($r->utilizationPct() > 70 ? 'warning' : 'success')),
            ]),
            Section::make('Budget Lines')->icon('heroicon-o-list-bullet')->schema([
                RepeatableEntry::make('lines')->schema([
                    TextEntry::make('account.code')->label('Code')->fontFamily('mono'),
                    TextEntry::make('account.name')->label('Account'),
                    TextEntry::make('activity_code')->label('Activity')->placeholder('—'),
                    TextEntry::make('q1_amount')->label('Q1')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('q2_amount')->label('Q2')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('q3_amount')->label('Q3')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('q4_amount')->label('Q4')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('total_budgeted')->label('Total')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold'),
                    TextEntry::make('actual')->label('Actual')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                ])->columns(9),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBudgets::route('/'),
            'create' => CreateBudgets::route('/create'),
            'view'   => ViewBudgets::route('/{record}'),
            'edit'   => EditBudgets::route('/{record}/edit'),
        ];
    }
}
