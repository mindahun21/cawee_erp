<?php

namespace App\Filament\Resources\ME;

use App\Filament\Resources\ME\DisaggregationCategoryResource\Pages\CreateDisaggregationCategory;
use App\Filament\Resources\ME\DisaggregationCategoryResource\Pages\EditDisaggregationCategory;
use App\Filament\Resources\ME\DisaggregationCategoryResource\Pages\ListDisaggregationCategories;
use App\Filament\Resources\ME\DisaggregationCategoryResource\Pages\ViewDisaggregationCategory;
use App\Filament\Resources\ME\Support\MeAuditTrail;
use App\Models\ME\MeDisaggregationCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DisaggregationCategoryResource extends Resource
{
    protected static ?string $model = MeDisaggregationCategory::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-view-columns';

    protected static string | \UnitEnum | null $navigationGroup = 'M&E';

    protected static ?string $navigationLabel = 'Disaggregation';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Category')
                    ->columns(2)
                    ->schema([
                        Select::make('key')
                            ->required()
                            ->options([
                                'gender' => 'Gender',
                                'age' => 'Age',
                                'location' => 'Location',
                                'disability' => 'Disability',
                                'custom' => 'Custom',
                            ]),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('rules')
                            ->label('Rules (JSON)')
                            ->helperText('Example for age: {"buckets":["0-17","18-35","36+"]}')
                            ->formatStateUsing(fn ($state): ?string => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                            ->dehydrateStateUsing(function ($state): ?array {
                                if (blank($state)) {
                                    return null;
                                }

                                return json_decode((string) $state, true);
                            })
                            ->rules(['nullable', 'json'])
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('key')
                            ->badge(),
                        TextEntry::make('name'),
                        TextEntry::make('rules')
                            ->formatStateUsing(fn ($state): string => $state ? json_encode($state, JSON_PRETTY_PRINT) : '-')
                            ->columnSpanFull(),
                    ]),
                MeAuditTrail::section('me_disaggregation_categories'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->badge()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('options_count')
                    ->counts('options')
                    ->label('Options'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DisaggregationCategoryResource\RelationManagers\OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDisaggregationCategories::route('/'),
            'create' => CreateDisaggregationCategory::route('/create'),
            'view' => ViewDisaggregationCategory::route('/{record}'),
            'edit' => EditDisaggregationCategory::route('/{record}/edit'),
        ];
    }
}
