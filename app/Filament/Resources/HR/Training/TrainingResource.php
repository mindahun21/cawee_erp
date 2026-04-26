<?php

namespace App\Filament\Resources\HR\Training;

use App\Models\Training;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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
use App\Traits\BelongsToModule;

class TrainingResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = Training::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'Training Management';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable()
                    ->required(),

                Select::make('training_type_id')
                    ->label('Training Type')
                    ->relationship('trainingType', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                TextInput::make('title')
                    ->label('Training Title')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                TextInput::make('institution')
                    ->label('Institution / Provider')
                    ->maxLength(200),

                DatePicker::make('start_date'),
                DatePicker::make('end_date')->afterOrEqual('start_date'),

                TextInput::make('duration_days')
                    ->label('Duration (Days)')
                    ->numeric()
                    ->minValue(1),

                TextInput::make('cost')
                    ->numeric()
                    ->prefix('ETB')
                    ->minValue(0),

                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable(['first_name', 'last_name'])
                    ->weight('semibold'),

                TextColumn::make('title')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('trainingType.name')
                    ->label('Type')
                    ->badge()
                    ->color('info'),

                TextColumn::make('institution')->limit(30)->toggleable(),

                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->sortable(),

                TextColumn::make('duration_days')
                    ->label('Days')
                    ->alignCenter(),

                TextColumn::make('cost')
                    ->prefix('ETB ')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(),
            ])
            ->defaultSort('start_date', 'desc')
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('training_type_id')
                    ->label('Training Type')
                    ->relationship('trainingType', 'name'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTrainings::route('/'),
            'create' => Pages\CreateTraining::route('/create'),
            'edit'   => Pages\EditTraining::route('/{record}/edit'),
        ];
    }
}
