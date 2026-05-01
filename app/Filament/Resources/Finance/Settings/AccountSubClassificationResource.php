<?php

namespace App\Filament\Resources\Finance\Settings;

use App\Models\Finance\AccountSubClassification;
use App\Models\User;
use BackedEnum;
use Filament\Actions\CreateAction;
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

class AccountSubClassificationResource extends Resource
{
    protected static ?string $model = AccountSubClassification::class;

    // ── Navigation ────────────────────────────────────────────────────
    // Hidden from sidebar — accessed via the Finance Settings tabs.
    protected static bool $shouldRegisterNavigation = false;
    protected static bool $shouldSkipAuthorization  = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance / Settings';

    protected static ?string $navigationLabel = 'Account Sub-Classifications';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    // ── Policy bypasses ───────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) return true;
        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }

    public static function canCreate(): bool    { return static::canViewAny(); }
    public static function canEdit($r): bool    { return static::canViewAny(); }
    public static function canDelete($r): bool  { return static::canViewAny(); }

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sub-Classification Details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('e.g. Cash and Cash Equivalents')
                        ->columnSpanFull(),

                    TextInput::make('code')
                        ->label('Short Code')
                        ->maxLength(30)
                        ->nullable()
                        ->placeholder('e.g. CCE')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('code', strtoupper($state ?? '')))
                        ->unique(AccountSubClassification::class, 'code', ignoreRecord: true)
                        ->helperText('Optional — used in reports and imports.'),

                    Select::make('classification')
                        ->label('Parent Classification')
                        ->options(AccountSubClassification::classificationLabels())
                        ->required()
                        ->native(false)
                        ->helperText('Which top-level BS/IS category does this belong to?'),

                    TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers appear first in reports.'),

                    Textarea::make('description')
                        ->columnSpanFull()
                        ->rows(2)
                        ->nullable()
                        ->placeholder('Optional description for this sub-classification.'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpanFull()
                        ->helperText('Inactive sub-classifications are hidden from Chart of Accounts forms.'),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('classification')
                    ->label('Category')
                    ->formatStateUsing(fn ($state) => AccountSubClassification::classificationLabels()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'asset'     => 'success',
                        'liability' => 'danger',
                        'equity'    => 'warning',
                        'income'    => 'info',
                        'expense'   => 'gray',
                        default     => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Sub-Classification')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('code')
                    ->label('Code')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('primary')
                    ->placeholder('—'),

                TextColumn::make('chartOfAccounts_count')
                    ->label('Accounts')
                    ->counts('chartOfAccounts')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->defaultSort('classification')
            ->filters([
                SelectFilter::make('classification')
                    ->label('Parent Category')
                    ->options(AccountSubClassification::classificationLabels()),

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
                    ->requiresConfirmation()
                    ->modalDescription('Deleting this sub-classification will unlink it from all Chart of Accounts entries. The accounts themselves will not be deleted.'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->requiresConfirmation(),
            ])
            ->emptyStateIcon('heroicon-o-tag')
            ->emptyStateHeading('No sub-classifications yet')
            ->emptyStateDescription('Add sub-classifications like "Cash and Cash Equivalents", "Bank", "Accounts Receivable", etc. to better organise your Chart of Accounts.')
            ->emptyStateActions([
                CreateAction::make()->label('Add Sub-Classification'),
            ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAccountSubClassifications::route('/'),
        ];
    }
}
