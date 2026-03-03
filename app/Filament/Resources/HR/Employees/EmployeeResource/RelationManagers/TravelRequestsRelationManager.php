<?php

namespace App\Filament\Resources\HR\Employees\EmployeeResource\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

class TravelRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'travelRequests';

    protected static ?string $title = 'Travel Requests';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('travel_type')
                ->options([
                    'Field'      => 'Field',
                    'Conference' => 'Conference',
                    'Other'      => 'Other',
                ])
                ->required(),

            Select::make('approval_status')
                ->options([
                    'Pending'  => 'Pending',
                    'Approved' => 'Approved',
                    'Rejected' => 'Rejected',
                ])
                ->default('Pending')
                ->required(),

            DatePicker::make('start_date')->required(),
            DatePicker::make('end_date')->required()->afterOrEqual('start_date'),

            TextInput::make('per_diem_amount')
                ->numeric()
                ->prefix('ETB')
                ->minValue(0),

            Toggle::make('vehicle_required'),
            Toggle::make('report_submitted'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('travel_type')->badge(),
                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->sortable(),
                TextColumn::make('per_diem_amount')->prefix('ETB ')->numeric(decimalPlaces: 2),
                TextColumn::make('approval_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default    => 'warning',
                    }),
                IconColumn::make('vehicle_required')->boolean()->toggleable(),
                IconColumn::make('report_submitted')->boolean()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('approval_status')
                    ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected']),
                SelectFilter::make('travel_type')
                    ->options(['Field' => 'Field', 'Conference' => 'Conference', 'Other' => 'Other']),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('start_date', 'desc');
    }
}
