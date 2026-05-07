<?php

namespace App\Filament\Resources\Finance\Bank;

use App\Filament\Resources\Finance\Bank\Pages\CreateBankAccount;
use App\Filament\Resources\Finance\Bank\Pages\EditBankAccount;
use App\Filament\Resources\Finance\Bank\Pages\ListBankAccounts;
use App\Filament\Resources\Finance\Bank\Pages\ViewBankAccount;
use App\Models\Currency;
use App\Models\Donor;
use App\Models\Finance\BankAccount;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\CostCenter;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class BankAccountResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = BankAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Bank Accounts';
    protected static ?int $navigationSort = 30;
    protected static ?string $recordTitleAttribute = 'account_name';
    protected static bool $shouldSkipAuthorization = true;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return true;
        }

        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }

    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($r): bool   { return static::canViewAny(); }
    public static function canDelete($r): bool { return static::canViewAny(); }

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Bank Account Details')
                ->description('Core identification and bank information.')
                ->icon('heroicon-o-building-library')
                ->columns(3)
                ->schema([
                    TextInput::make('account_name')
                        ->label('Account Name')
                        ->required()
                        ->maxLength(120)
                        ->placeholder('e.g. ETB Operations Account'),

                    TextInput::make('bank_name')
                        ->label('Bank Name')
                        ->required()
                        ->maxLength(120)
                        ->placeholder('e.g. Commercial Bank of Ethiopia'),

                    TextInput::make('account_number')
                        ->label('Account Number')
                        ->required()
                        ->unique(BankAccount::class, 'account_number', ignoreRecord: true)
                        ->maxLength(60)
                        ->extraInputAttributes(['class' => 'font-mono']),

                    TextInput::make('branch')
                        ->label('Branch')
                        ->maxLength(100)
                        ->placeholder('e.g. Bole Branch'),

                    TextInput::make('swift_code')
                        ->label('SWIFT / BIC Code')
                        ->maxLength(20)
                        ->nullable()
                        ->extraInputAttributes(['class' => 'font-mono uppercase'])
                        ->helperText('Required for international wire transfers.'),

                    Select::make('account_type')
                        ->label('Account Type')
                        ->options(BankAccount::accountTypes())
                        ->required()
                        ->native(false),
                ]),

            Section::make('Financial Linking')
                ->description('Link this bank account to its GL account and operational dimensions.')
                ->icon('heroicon-o-link')
                ->columns(2)
                ->schema([
                    Select::make('currency_id')
                        ->label('Currency')
                        ->options(Currency::pluck('code', 'id')->toArray())
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->live(),          // triggers CoA dropdown to refresh

                    Select::make('chart_of_account_id')
                        ->label('GL Account (Control Account)')
                        ->options(function ($get) {
                            $currencyId = $get('currency_id');

                            return ChartOfAccount::where('is_active', true)
                                ->where('is_control_account', 'bank')
                                ->when(
                                    $currencyId,
                                    // If a currency is selected, match it exactly
                                    // (currency_id = selected) OR (currency_id is null = ETB default)
                                    fn ($q) => $q->where(function ($q2) use ($currencyId) {
                                        $q2->where('currency_id', $currencyId)
                                           ->orWhereNull('currency_id');
                                    })
                                )
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn ($a) => [$a->id => "[{$a->code}] {$a->name}"])
                                ->toArray();
                        })
                        ->native(false)
                        ->searchable()
                        ->nullable()
                        ->helperText('Only Bank control accounts matching the selected currency are shown.'),

                    Select::make('cost_center_id')
                        ->label('Cost Center')
                        ->options(fn () => CostCenter::where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                        )
                        ->native(false)
                        ->searchable()
                        ->nullable(),

                    Select::make('donor_id')
                        ->label('Donor (if restricted account)')
                        ->options(fn () => Donor::orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn ($d) => [$d->id => $d->full_name])
                            ->toArray()
                        )
                        ->native(false)
                        ->searchable()
                        ->nullable()
                        ->helperText('Set only for donor-restricted project accounts.'),
                ]),

            Section::make('Opening Balance')
                ->description('Record the opening balance when setting up this account in the system.')
                ->icon('heroicon-o-banknotes')
                ->columns(2)
                ->schema([
                    \Filament\Forms\Components\DatePicker::make('balance_as_of_date')
                        ->label('Balance As Of')
                        ->native(false)
                        ->nullable()
                        ->default(now()->toDateString()),

                    TextInput::make('opening_balance')
                        ->label('Opening Balance')
                        ->numeric()
                        ->default(0)
                        ->prefix('ETB')
                        ->extraInputAttributes(['class' => 'font-mono']),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2)
                        ->columnSpanFull()
                        ->nullable(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_number')
                    ->label('Acct No.')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->width('160px'),

                TextColumn::make('account_name')
                    ->label('Account Name')
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('bank_name')
                    ->label('Bank')
                    ->searchable(),

                TextColumn::make('account_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => BankAccount::accountTypes()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'current'          => 'info',
                        'savings'          => 'success',
                        'project_specific' => 'warning',
                        default            => 'gray',
                    }),

                TextColumn::make('currency.code')
                    ->label('Currency')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('current_balance')
                    ->label('Balance')
                    ->getStateUsing(function (BankAccount $record) {
                        return $record->chart_of_account_id
                            ? $record->chartOfAccount?->currentBalance() ?? 0.00
                            : $record->current_balance ?? 0.00;
                    })
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->color(fn ($state) => (float) $state >= 0 ? 'success' : 'danger'),

                TextColumn::make('costCenter.name')
                    ->label('Cost Center')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                SelectFilter::make('account_type')
                    ->label('Account Type')
                    ->options(BankAccount::accountTypes()),

                SelectFilter::make('currency_id')
                    ->label('Currency')
                    ->options(Currency::pluck('code', 'id')->toArray()),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->placeholder('All')
                    ->default(true),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('account_name');
    }

    // ── Infolist ──────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Bank Account Details')
                ->icon('heroicon-o-building-library')
                ->columns(4)
                ->schema([
                    TextEntry::make('account_name')->label('Account Name')->weight('bold'),
                    TextEntry::make('bank_name')->label('Bank'),
                    TextEntry::make('account_number')->label('Account Number')->fontFamily('mono'),
                    TextEntry::make('branch')->label('Branch')->placeholder('—'),
                    TextEntry::make('account_type')
                        ->label('Type')
                        ->badge()
                        ->formatStateUsing(fn ($state) => BankAccount::accountTypes()[$state] ?? $state),
                    TextEntry::make('swift_code')->label('SWIFT/BIC')->placeholder('—')->fontFamily('mono'),
                    TextEntry::make('currency.code')->label('Currency')->badge()->color('primary'),
                    TextEntry::make('chartOfAccount.name')->label('GL Account')->placeholder('Not linked'),
                ]),

            Section::make('Balance')
                ->icon('heroicon-o-banknotes')
                ->columns(3)
                ->schema([
                    TextEntry::make('opening_balance')
                        ->label('Opening Balance')
                        ->numeric(decimalPlaces: 2)
                        ->fontFamily('mono'),
                    TextEntry::make('current_balance')
                        ->label('Current Balance')
                        ->getStateUsing(function (BankAccount $record) {
                            return $record->chart_of_account_id
                                ? $record->chartOfAccount?->currentBalance() ?? 0.00
                                : $record->current_balance ?? 0.00;
                        })
                        ->numeric(decimalPlaces: 2)
                        ->fontFamily('mono')
                        ->weight('bold')
                        ->color(fn ($state) => (float) $state >= 0 ? 'success' : 'danger'),
                    TextEntry::make('balance_as_of_date')->label('As Of')->date(),
                ]),

            Section::make('Dimensions')
                ->icon('heroicon-o-tag')
                ->columns(2)
                ->schema([
                    TextEntry::make('costCenter.name')->label('Cost Center')->placeholder('—'),
                    TextEntry::make('donor.name')->label('Restricted Donor')->placeholder('—'),
                    TextEntry::make('notes')->label('Notes')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListBankAccounts::route('/'),
            'create' => CreateBankAccount::route('/create'),
            'view'   => ViewBankAccount::route('/{record}'),
            'edit'   => EditBankAccount::route('/{record}/edit'),
        ];
    }
}
