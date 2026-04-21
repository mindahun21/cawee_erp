<?php

namespace App\Filament\Resources\Finance\Settings;

use App\Filament\Concerns\HasFinanceSettingsNavigation;
use App\Models\Finance\FinancialStatementCategory;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FinancialStatementCategoryResource extends Resource
{
    protected static ?string $model = FinancialStatementCategory::class;

    // ── Navigation ────────────────────────────────────────────────────
    // Lives under Finance Settings sub-nav alongside AccountTypes, TaxTypes, etc.
    protected static bool $shouldRegisterNavigation = false;
    protected static bool $shouldSkipAuthorization  = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationParentItem = 'Finance Settings';

    protected static ?string $navigationLabel = 'Statement Categories';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    // ── Permissions ───────────────────────────────────────────────────

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

            Section::make('Category Details')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->required()
                        ->maxLength(20)
                        ->placeholder('e.g., BS-CA, IS-PE, CF-OA')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('code', strtoupper($state ?? '')))
                        ->unique(FinancialStatementCategory::class, 'code', ignoreRecord: true)
                        ->helperText('Short mnemonic used in report generation — must be unique.'),

                    TextInput::make('name')
                        ->required()
                        ->maxLength(120)
                        ->placeholder('e.g., Current Assets, Program Expenses'),

                    Select::make('statement_type')
                        ->label('Statement Type')
                        ->options(FinancialStatementCategory::statementTypes())
                        ->required()
                        ->native(false)
                        ->helperText('Which statutory report this category feeds into.'),

                    TextInput::make('display_order')
                        ->label('Display Order')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(255)
                        ->helperText('Controls rendering sequence within the report section (lower = first).'),

                    Select::make('parent_id')
                        ->label('Parent Category')
                        ->options(fn () => FinancialStatementCategory::query()
                            ->where('is_active', true)
                            ->orderBy('statement_type')
                            ->orderBy('display_order')
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => "[{$c->code}] {$c->name}"])
                            ->toArray()
                        )
                        ->searchable()
                        ->nullable()
                        ->helperText('Leave blank for a top-level section header.'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive categories cannot be assigned to new Chart of Account entries.'),

                    Textarea::make('description')
                        ->columnSpanFull()
                        ->rows(2)
                        ->nullable()
                        ->placeholder('Brief description of what accounts belong in this category…'),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        str_starts_with($state, 'BS') => 'primary',
                        str_starts_with($state, 'IS') => 'success',
                        str_starts_with($state, 'CF') => 'warning',
                        default                        => 'gray',
                    }),

                TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('statement_type')
                    ->label('Statement')
                    ->formatStateUsing(fn ($state) => FinancialStatementCategory::statementTypes()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'balance_sheet'    => 'primary',
                        'income_statement' => 'success',
                        'cash_flow'        => 'warning',
                        default            => 'gray',
                    }),

                TextColumn::make('display_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('chartOfAccounts_count')
                    ->label('CoA Entries')
                    ->counts('chartOfAccounts')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->tooltip('Number of Chart of Accounts entries mapped to this category'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                SelectFilter::make('statement_type')
                    ->label('Statement Type')
                    ->options(FinancialStatementCategory::statementTypes()),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (FinancialStatementCategory $record) {
                        if ($record->chartOfAccounts()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot delete')
                                ->body('This category is assigned to one or more Chart of Accounts entries.')
                                ->danger()
                                ->send();

                            $record->skipDelete = true;
                        }
                    }),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('statement_type')
            ->groups([
                \Filament\Tables\Grouping\Group::make('statement_type')
                    ->label('Statement Type')
                    ->getTitleFromRecordUsing(fn ($record) =>
                        FinancialStatementCategory::statementTypes()[$record->statement_type] ?? $record->statement_type
                    )
                    ->collapsible(),
            ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFinancialStatementCategories::route('/'),
        ];
    }
}
