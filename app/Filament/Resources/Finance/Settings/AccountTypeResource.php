<?php

namespace App\Filament\Resources\Finance\Settings;

use App\Models\Finance\AccountType;
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
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class AccountTypeResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = AccountType::class;

    // ── Navigation ────────────────────────────────────────────────────
    // Hidden from the sidebar — accessed via the 'Finance Settings' NavigationItem.
    protected static bool $shouldRegisterNavigation = false;
    protected static bool $shouldSkipAuthorization  = true;


    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance / Settings';

    protected static ?string $navigationParentItem = 'Finance Settings';

    protected static ?string $navigationLabel = 'Account Types';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    // ── Policy bypasses ───────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return true;
        }

        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }

    public static function canCreate(): bool  { return static::canViewAny(); }
    public static function canEdit($r): bool  { return static::canViewAny(); }
    public static function canDelete($r): bool { return static::canViewAny(); }

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account Type Details')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->required()
                        ->maxLength(20)
                        ->placeholder('e.g., ASSET, LIABILITY, EXPENSE')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('code', strtoupper($state ?? '')))
                        ->unique(AccountType::class, 'code', ignoreRecord: true),

                    TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('e.g., Asset, Liability, Expense'),

                    Select::make('classification')
                        ->label('Classification')
                        ->options(AccountType::classifications())
                        ->required()
                        ->native(false),

                    Select::make('normal_balance')
                        ->label('Normal Balance')
                        ->options(AccountType::normalBalances())
                        ->required()
                        ->native(false)
                        ->helperText('Debit for assets/expenses; Credit for liabilities/equity/income.'),

                    Textarea::make('description')
                        ->columnSpanFull()
                        ->rows(2)
                        ->nullable(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpanFull()
                        ->helperText('Inactive account types cannot be used on new Chart of Account entries.'),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->badge()
                    ->color('info'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('classification')
                    ->formatStateUsing(fn ($state) => AccountType::classifications()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'asset'     => 'success',
                        'liability' => 'danger',
                        'equity'    => 'warning',
                        'income'    => 'info',
                        'expense'   => 'gray',
                        default     => 'gray',
                    }),

                TextColumn::make('normal_balance')
                    ->label('Normal Balance')
                    ->badge()
                    ->color(fn ($state) => $state === 'debit' ? 'success' : 'primary'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                SelectFilter::make('classification')
                    ->label('Classification')
                    ->options(AccountType::classifications()),
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
            'index' => Pages\ManageAccountTypes::route('/'),
        ];
    }
}
