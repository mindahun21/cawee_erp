<?php

namespace App\Filament\Resources\HR\SalaryGrades;

use App\Filament\Resources\HR\SalaryGrades\Pages\ManageSalaryGrades;
use App\Models\SalaryGrade;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalaryGradeResource extends Resource
{
    protected static ?string $model = SalaryGrade::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'Salary Grades';

    protected static ?int $navigationSort = 16;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('grade')
                ->options(['I' => 'Grade I', 'II' => 'Grade II', 'III' => 'Grade III', 'IV' => 'Grade IV'])
                ->required(),

            TextInput::make('step')
                ->numeric()
                ->minValue(1)
                ->maxValue(15)
                ->required()
                ->label('Step (1-15)'),

            TextInput::make('basic_salary')->numeric()->prefix('ETB')->required(),
            TextInput::make('transport_allowance')->numeric()->prefix('ETB')->default(0),
            TextInput::make('house_allowance')->numeric()->prefix('ETB')->default(0),
            TextInput::make('communications_allowance')->numeric()->prefix('ETB')->default(0),

            DatePicker::make('effective_from')->required(),
            DatePicker::make('effective_to'),

            Toggle::make('is_active')->default(true)->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grade')->sortable()->badge()->color('primary'),
                TextColumn::make('step')->sortable(),
                TextColumn::make('basic_salary')->prefix('ETB ')->numeric(2)->sortable(),
                TextColumn::make('transport_allowance')->prefix('ETB ')->numeric(2)->toggleable(),
                TextColumn::make('house_allowance')->prefix('ETB ')->numeric(2)->toggleable(),
                TextColumn::make('effective_from')->date()->sortable(),
                TextColumn::make('is_active')->label('Active')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                    ->badge()->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->defaultSort('grade')
            ->filters([
                SelectFilter::make('grade')
                    ->options(['I' => 'Grade I', 'II' => 'Grade II', 'III' => 'Grade III', 'IV' => 'Grade IV']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSalaryGrades::route('/'),
        ];
    }
}
