<?php

namespace App\Filament\Resources\Finance\Settings;

use App\Models\Finance\FinanceSetting;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FinanceSettingResource extends Resource
{
    protected static ?string $model = FinanceSetting::class;

    // ── Navigation ────────────────────────────────────────────────────
    // This is the primary landing page for the Finance Settings nav item.
    protected static bool $shouldRegisterNavigation = false;
    protected static bool $shouldSkipAuthorization  = true;
    

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance / Settings';

    protected static ?string $navigationLabel = 'System Defaults';

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'label';

    // ── Policy bypasses ───────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isFinanceManager() || $user->isSuperAdmin());
    }

    public static function canCreate(): bool   { return false; } // Settings are seeded; no new rows
    public static function canDelete($r): bool { return false; } // Settings cannot be deleted
    public static function canEdit($r): bool   { return static::canViewAny(); }

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('System Default')
                ->columns(1)
                ->schema([
                    TextInput::make('label')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('key')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('value')
                        ->label('Value')
                        ->required()
                        ->maxLength(500),

                    Textarea::make('description')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(2),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')
                    ->formatStateUsing(fn ($state) => FinanceSetting::groups()[$state] ?? $state)
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('label')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('key')
                    ->searchable()
                    ->fontFamily('mono')
                    ->color('gray'),

                TextColumn::make('value')
                    ->searchable()
                    ->fontFamily('mono')
                    ->limit(40)
                    ->placeholder('(not set)'),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label('Group')
                    ->options(FinanceSetting::groups()),
            ])
            ->recordActions([
                EditAction::make()->label('Edit Value'),
            ])
            ->defaultSort('group')
            ->paginated(false);  // All settings visible at once
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFinanceSettings::route('/'),
        ];
    }
}
