<?php

namespace App\Filament\Resources\Finance\Settings;

use App\Models\Currency;
use App\Models\Employee;
use App\Models\Finance\Cashier;
use App\Models\Finance\CostCenter;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CashierResource extends Resource
{
    protected static ?string $model = Cashier::class;

    protected static bool $shouldRegisterNavigation = false;
    protected static bool $shouldSkipAuthorization  = true;
    

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance / Settings';

    protected static ?string $navigationParentItem = 'Finance Settings';

    protected static ?string $navigationLabel = 'Cashiers';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'employee.full_name';

    // ── Policy bypasses ───────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }

    public static function canCreate(): bool  { return static::canViewAny(); }
    public static function canEdit($r): bool  { return static::canViewAny(); }
    public static function canDelete($r): bool { return static::canViewAny(); }

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cashier Details')
                ->columns(2)
                ->schema([
                    Select::make('employee_id')
                        ->label('Employee')
                        ->options(fn () => Employee::orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn ($e) => [$e->id => $e->full_name])
                            ->toArray()
                        )
                        ->searchable()
                        ->required()
                        ->helperText('The employee assigned to manage this petty cash fund.'),

                    Select::make('cost_center_id')
                        ->label('Cost Center')
                        ->options(CostCenter::activeOptions())
                        ->searchable()
                        ->required(),

                    Select::make('currency_id')
                        ->label('Currency')
                        ->options(fn () => Currency::orderBy('code')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(fn () => Currency::where('code', 'ETB')->value('id')),

                    TextInput::make('fund_limit')
                        ->label('Fund Limit')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->helperText('Maximum amount the cashier is authorized to hold.'),

                    Textarea::make('notes')
                        ->columnSpanFull()
                        ->rows(2)
                        ->nullable(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable(['employees.first_name', 'employees.last_name'])
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('costCenter.name')
                    ->label('Cost Center')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('currency.code')
                    ->label('Currency')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('info'),

                TextColumn::make('fund_limit')
                    ->label('Fund Limit')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->prefix(fn ($record) => $record->currency?->symbol . ' '),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCashiers::route('/'),
        ];
    }
}
