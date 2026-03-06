<?php

namespace App\Filament\Resources\HR\Employees\EmployeeResource\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TimeRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'timeRecords';

    protected static ?string $title = 'Time Records';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('work_date')->required(),

            Select::make('project_id')
                ->label('Project')
                ->relationship('project', 'project_name')
                ->searchable()
                ->preload()
                ->nullable(),

            TextInput::make('hours_worked')
                ->numeric()
                ->minValue(0)
                ->maxValue(24)
                ->step(0.5)
                ->required(),

            Select::make('leave_type')
                ->options([
                    'None'     => 'None',
                    'Vacation' => 'Vacation',
                    'Sick'     => 'Sick',
                    'Holiday'  => 'Holiday',
                    'Personal' => 'Personal',
                    'Other'    => 'Other',
                ])
                ->default('None')
                ->required(),

            Textarea::make('remarks')->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('work_date')->date()->sortable(),
                TextColumn::make('project.project_name')->label('Project'),
                TextColumn::make('hours_worked')->numeric(decimalPlaces: 1)->suffix(' hrs'),
                TextColumn::make('leave_type')->badge(),
                TextColumn::make('remarks')->limit(40)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('leave_type')
                    ->options([
                        'None'     => 'None',
                        'Vacation' => 'Vacation',
                        'Sick'     => 'Sick',
                        'Holiday'  => 'Holiday',
                        'Personal' => 'Personal',
                        'Other'    => 'Other',
                    ]),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('work_date', 'desc');
    }
}
