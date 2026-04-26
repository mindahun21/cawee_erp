<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT;

use App\Filament\Resources\BRT\HouseholdResource\Pages\CreateHousehold;
use App\Filament\Resources\BRT\HouseholdResource\Pages\EditHousehold;
use App\Filament\Resources\BRT\HouseholdResource\Pages\ListHouseholds;
use App\Filament\Resources\BRT\HouseholdResource\Pages\ViewHousehold;
use App\Filament\Resources\BRT\HouseholdResource\RelationManagers\BeneficiariesRelationManager;
use App\Models\ME\MeHousehold;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class HouseholdResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = MeHousehold::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Beneficiary Registry & Project Tracking';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationLabel = 'Households';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'household_code';

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Household Identification')
                ->description('Core details of the household unit.')
                ->icon('heroicon-o-home-modern')
                ->columns(2)
                ->schema([
                    TextInput::make('household_code')
                        ->label('Household Code')
                        ->required()
                        ->maxLength(30)
                        ->unique(MeHousehold::class, 'household_code', ignoreRecord: true),

                    TextInput::make('head_of_household')
                        ->label('Head of Household')
                        ->maxLength(100),

                    TextInput::make('family_size')
                        ->label('Family Size')
                        ->numeric()
                        ->default(1),

                    Select::make('vulnerability_status')
                        ->label('Vulnerability Status')
                        ->options([
                            'low'      => 'Low',
                            'medium'   => 'Medium',
                            'high'     => 'High',
                            'critical' => 'Critical',
                        ])
                        ->default('low')
                        ->required(),

                    Select::make('income_level')
                        ->label('Income Level')
                        ->options([
                            'none'     => 'None',
                            'very_low' => 'Very Low',
                            'low'      => 'Low',
                            'medium'   => 'Medium',
                            'high'     => 'High',
                        ])
                        ->default('none'),

                    Select::make('project_id')
                        ->label('Linked Project')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->preload(),
                ]),

            Section::make('Location')
                ->icon('heroicon-o-map-pin')
                ->columns(2)
                ->schema([
                    TextInput::make('kebele')->maxLength(100),
                    TextInput::make('woreda')->maxLength(100),
                    TextInput::make('zone')->maxLength(100),
                    TextInput::make('region')->maxLength(100),
                    Textarea::make('address')->columnSpanFull()->rows(2),
                    Textarea::make('notes')->label('Notes')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    // ── Infolist ──────────────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Household Identification')
                ->icon('heroicon-o-home-modern')
                ->columns(3)
                ->schema([
                    TextEntry::make('household_code')->badge()->color('info'),
                    TextEntry::make('head_of_household')->placeholder('—'),
                    TextEntry::make('family_size')->label('Family Size')->suffix(' members'),
                    TextEntry::make('vulnerability_status')
                        ->badge()
                        ->color(fn (MeHousehold $record): string => $record->vulnerability_color),
                    TextEntry::make('income_level')->label('Income Level')->placeholder('—'),
                    TextEntry::make('project.name')->label('Project')->placeholder('—'),
                ]),

            Section::make('Location')
                ->icon('heroicon-o-map-pin')
                ->columns(2)
                ->schema([
                    TextEntry::make('kebele')->placeholder('—'),
                    TextEntry::make('woreda')->placeholder('—'),
                    TextEntry::make('zone')->placeholder('—'),
                    TextEntry::make('region')->placeholder('—'),
                    TextEntry::make('address')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
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
                    ->placeholder('—'),

                TextColumn::make('family_size')
                    ->label('Family Size')
                    ->sortable(),

                TextColumn::make('vulnerability_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high'     => 'warning',
                        'medium'   => 'info',
                        default    => 'success',
                    }),

                TextColumn::make('income_level')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('woreda')->placeholder('—'),
                TextColumn::make('region')->placeholder('—')->toggleable(),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('beneficiaries_count')
                    ->counts('beneficiaries')
                    ->label('Members')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('vulnerability_status')
                    ->label('Vulnerability')
                    ->options([
                        'low'      => 'Low',
                        'medium'   => 'Medium',
                        'high'     => 'High',
                        'critical' => 'Critical',
                    ]),

                SelectFilter::make('income_level')
                    ->label('Income Level')
                    ->options([
                        'none'     => 'None',
                        'very_low' => 'Very Low',
                        'low'      => 'Low',
                        'medium'   => 'Medium',
                        'high'     => 'High',
                    ]),

                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    // ── Relations & Pages ─────────────────────────────────────────────────────

    public static function getRelationManagers(): array
    {
        return [
            BeneficiariesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListHouseholds::route('/'),
            'create' => CreateHousehold::route('/create'),
            'view'   => ViewHousehold::route('/{record}'),
            'edit'   => EditHousehold::route('/{record}/edit'),
        ];
    }
}
