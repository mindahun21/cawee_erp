<?php

namespace App\Filament\Resources\HR\Employees\EmployeeResource\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HrProcessesRelationManager extends RelationManager
{
    protected static string $relationship = 'hrProcesses';

    protected static ?string $title = 'Onboarding / Offboarding';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('process_type')
                ->options([
                    'Onboarding'  => 'Onboarding',
                    'Offboarding' => 'Offboarding',
                ])
                ->required(),

            TextInput::make('document_name')
                ->required()
                ->maxLength(200),

            Toggle::make('document_signed'),

            DatePicker::make('completion_date'),

            Textarea::make('remarks')->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('process_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Onboarding'  => 'success',
                        'Offboarding' => 'danger',
                        default       => 'gray',
                    }),
                TextColumn::make('document_name')->searchable(),
                IconColumn::make('document_signed')->boolean(),
                TextColumn::make('completion_date')->date()->sortable(),
                TextColumn::make('remarks')->limit(40)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('process_type')
                    ->options(['Onboarding' => 'Onboarding', 'Offboarding' => 'Offboarding']),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
