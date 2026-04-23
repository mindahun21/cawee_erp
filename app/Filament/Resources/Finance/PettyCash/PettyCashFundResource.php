<?php

namespace App\Filament\Resources\Finance\PettyCash;

use App\Filament\Resources\Finance\PettyCash\Pages\CreatePettyCashFund;
use App\Filament\Resources\Finance\PettyCash\Pages\EditPettyCashFund;
use App\Filament\Resources\Finance\PettyCash\Pages\ListPettyCashFunds;
use App\Filament\Resources\Finance\PettyCash\Pages\ViewPettyCashFund;
use App\Models\Currency;
use App\Models\Finance\Cashier;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\CostCenter;
use App\Models\Finance\PettyCashFund;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PettyCashFundResource extends Resource
{
    protected static ?string $model = PettyCashFund::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Petty Cash Funds';
    protected static ?int $navigationSort = 50;
    protected static ?string $recordTitleAttribute = 'fund_name';
    protected static bool $shouldSkipAuthorization = true;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }
    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($r): bool   { return static::canViewAny(); }
    public static function canDelete($r): bool { return $r->isClosed() && static::canViewAny(); }

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Fund Identity')
                ->description('Name and assign this petty cash fund to a cashier and cost center.')
                ->icon('heroicon-o-wallet')
                ->columns(2)
                ->schema([
                    TextInput::make('fund_name')
                        ->label('Fund Name')
                        ->required()
                        ->maxLength(120)
                        ->placeholder('e.g. Bole Office Petty Cash'),

                    TextInput::make('fund_code')
                        ->label('Fund Code')
                        ->required()
                        ->unique(PettyCashFund::class, 'fund_code', ignoreRecord: true)
                        ->maxLength(20)
                        ->extraInputAttributes(['class' => 'font-mono uppercase'])
                        ->placeholder('e.g. PCF-BOLE-01'),

                    Select::make('cashier_id')
                        ->label('Custodian (Cashier)')
                        ->options(fn () => Cashier::with('employee')
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => $c->employee?->full_name ?? "Cashier #{$c->id}"])
                            ->toArray()
                        )
                        ->required()
                        ->native(false)
                        ->searchable(),

                    Select::make('cost_center_id')
                        ->label('Cost Center')
                        ->options(fn () => CostCenter::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                        ->required()
                        ->native(false)
                        ->searchable(),

                    Select::make('currency_id')
                        ->label('Currency')
                        ->options(Currency::pluck('code', 'id')->toArray())
                        ->required()
                        ->native(false)
                        ->default(fn () => Currency::where('code', 'ETB')->value('id')),

                    Select::make('chart_of_account_id')
                        ->label('GL Account (Petty Cash)')
                        ->options(fn () => ChartOfAccount::where('is_active', true)
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn ($a) => [$a->id => "[{$a->code}] {$a->name}"])
                            ->toArray()
                        )
                        ->native(false)
                        ->searchable()
                        ->nullable(),
                ]),

            Section::make('Fund Limits & Status')
                ->icon('heroicon-o-adjustments-horizontal')
                ->columns(3)
                ->schema([
                    TextInput::make('opening_balance')
                        ->label('Opening Balance')
                        ->numeric()
                        ->default(0)
                        ->extraInputAttributes(['class' => 'font-mono'])
                        ->helperText('Initial float amount for this fund'),

                    TextInput::make('max_limit')
                        ->label('Maximum Fund Limit')
                        ->numeric()
                        ->default(5000)
                        ->required()
                        ->extraInputAttributes(['class' => 'font-mono'])
                        ->helperText('Replenishment triggered when balance < 20% of this limit'),

                    Select::make('status')
                        ->label('Status')
                        ->options(PettyCashFund::statuses())
                        ->required()
                        ->native(false)
                        ->default('active'),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2)
                        ->columnSpanFull()
                        ->nullable(),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fund_code')
                    ->label('Code')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('fund_name')
                    ->label('Fund Name')
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('cashier.employee.full_name')
                    ->label('Custodian')
                    ->placeholder('—'),

                TextColumn::make('costCenter.name')
                    ->label('Cost Center')
                    ->placeholder('—'),

                TextColumn::make('currency.code')
                    ->label('CCY')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('current_balance')
                    ->label('Balance')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->color(fn (PettyCashFund $record) =>
                        $record->needsReplenishment() ? 'danger' : 'success'
                    ),

                TextColumn::make('max_limit')
                    ->label('Limit')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'    => 'success',
                        'suspended' => 'warning',
                        'closed'    => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->options(PettyCashFund::statuses()),
                SelectFilter::make('currency_id')
                    ->label('Currency')
                    ->options(Currency::pluck('code', 'id')->toArray()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->visible(fn ($record) => $record->isClosed()),
            ])
            ->defaultSort('fund_name');
    }

    // ── Infolist ──────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Fund Details')
                ->icon('heroicon-o-wallet')
                ->columns(4)
                ->schema([
                    TextEntry::make('fund_code')->label('Code')->badge()->color('gray')->fontFamily('mono'),
                    TextEntry::make('fund_name')->label('Fund Name')->weight('bold'),
                    TextEntry::make('status')->label('Status')->badge()
                        ->color(fn ($state) => match ($state) {
                            'active' => 'success', 'suspended' => 'warning', 'closed' => 'danger', default => 'gray',
                        }),
                    TextEntry::make('currency.code')->label('Currency')->badge()->color('primary'),
                    TextEntry::make('cashier.employee.full_name')->label('Custodian'),
                    TextEntry::make('costCenter.name')->label('Cost Center'),
                    TextEntry::make('chartOfAccount.name')->label('GL Account')->placeholder('Not linked'),
                    TextEntry::make('notes')->label('Notes')->placeholder('—'),
                ]),

            Section::make('Balance')
                ->icon('heroicon-o-banknotes')
                ->columns(3)
                ->schema([
                    TextEntry::make('opening_balance')->label('Opening Balance')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('current_balance')->label('Current Balance')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold')
                        ->color(fn (PettyCashFund $record) => $record->needsReplenishment() ? 'danger' : 'success'),
                    TextEntry::make('max_limit')->label('Max Limit')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                ]),
        ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListPettyCashFunds::route('/'),
            'create' => CreatePettyCashFund::route('/create'),
            'view'   => ViewPettyCashFund::route('/{record}'),
            'edit'   => EditPettyCashFund::route('/{record}/edit'),
        ];
    }
}
