<?php

declare(strict_types=1);

namespace App\Filament\Resources\ME;

use App\Filament\Resources\ME\HouseholdResource\Pages;
use App\Models\ME\MeHousehold;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class HouseholdResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = MeHousehold::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring and Evaluation';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Households';

    protected static ?int $navigationSort = 6;

    /**
     * Households are managed inline via the BRT Beneficiary module.
     * This resource is kept for direct access but hidden from main navigation.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'household_code';

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Household Details')
                ->columns(2)
                ->schema([
                    \Filament\Forms\Components\TextInput::make('household_code')
                        ->label('Household Code')
                        ->required()
                        ->maxLength(30),

                    \Filament\Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->preload(),

                    \Filament\Forms\Components\TextInput::make('family_size')
                        ->label('Family Size')
                        ->numeric()
                        ->default(1),

                    \Filament\Forms\Components\Select::make('vulnerability_status')
                        ->options([
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                            'critical' => 'Critical',
                        ])
                        ->default('low'),

                    \Filament\Forms\Components\Select::make('income_level')
                        ->options([
                            'none' => 'None',
                            'very_low' => 'Very Low',
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                        ])
                        ->default('none'),

                    \Filament\Forms\Components\TextInput::make('head_of_household')
                        ->label('Head of Household Name')
                        ->maxLength(100),
                ]),

            Section::make('Location')->columns(2)->schema([
                \Filament\Forms\Components\TextInput::make('kebele')->maxLength(100),
                \Filament\Forms\Components\TextInput::make('woreda')->maxLength(100),
                \Filament\Forms\Components\TextInput::make('zone')->maxLength(100),
                \Filament\Forms\Components\TextInput::make('region')->maxLength(100),
            ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('household_code')
                    ->label('Code')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('head_of_household')
                    ->label('Head of Household')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('family_size')
                    ->label('Size')
                    ->sortable(),

                TextColumn::make('vulnerability_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        default => 'success',
                    }),

                TextColumn::make('income_level')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('zone')->label('Zone')->placeholder('—'),
                TextColumn::make('woreda')->label('Woreda')->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('vulnerability_status')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ]),
                SelectFilter::make('income_level')
                    ->options([
                        'none' => 'None',
                        'very_low' => 'Very Low',
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHouseholds::route('/'),
            'create' => Pages\CreateHousehold::route('/create'),
            'edit' => Pages\EditHousehold::route('/{record}/edit'),
        ];
    }
}
