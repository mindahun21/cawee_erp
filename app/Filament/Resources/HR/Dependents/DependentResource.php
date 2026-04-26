<?php

namespace App\Filament\Resources\HR\Dependents;

use App\Models\Dependent;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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
use App\Traits\BelongsToModule;

class DependentResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = Dependent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'Dependents';

    protected static ?int $navigationSort = 9;

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

                TextInput::make('full_name')
                    ->label('Dependent Full Name')
                    ->required()
                    ->maxLength(150),

                Select::make('relationship')
                    ->options([
                        'Spouse'  => 'Spouse',
                        'Child'   => 'Child',
                        'Parent'  => 'Parent',
                        'Sibling' => 'Sibling',
                        'Other'   => 'Other',
                    ])
                    ->required(),

                DatePicker::make('date_of_birth'),

                TextInput::make('national_id')
                    ->label('National ID')
                    ->maxLength(50),

                TextInput::make('phone_number')
                    ->tel()
                    ->maxLength(20),

                Toggle::make('is_beneficiary')
                    ->label('Is Beneficiary?')
                    ->helperText('Check if this dependent is listed as a beneficiary on company benefits.')
                    ->inline(false),
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
                    ->weight('semibold')
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label('Dependent')
                    ->searchable(),

                TextColumn::make('relationship')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Spouse'  => 'info',
                        'Child'   => 'success',
                        'Parent'  => 'warning',
                        default   => 'gray',
                    }),

                TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_beneficiary')
                    ->label('Beneficiary')
                    ->boolean(),
            ])
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('relationship')
                    ->options(['Spouse' => 'Spouse', 'Child' => 'Child', 'Parent' => 'Parent', 'Sibling' => 'Sibling', 'Other' => 'Other']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDependents::route('/'),
            'create' => Pages\CreateDependent::route('/create'),
            'edit'   => Pages\EditDependent::route('/{record}/edit'),
        ];
    }
}
