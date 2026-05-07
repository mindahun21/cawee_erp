<?php

namespace App\Filament\Resources\Maintenances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                Section::make('Maintenance Request')
                    ->columns(2)
                    ->schema([
                        Select::make('asset_id')
                            ->label('Asset / Vehicle')
                            ->relationship('asset', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name . ($record->asset_tag ? " ({$record->asset_tag})" : ""))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Asset Name / Vehicle Model')
                                    ->required(),
                                TextInput::make('asset_tag')
                                    ->label('Asset Tag / Plate Number')
                                    ->required()
                                    ->unique('assets', 'asset_tag')
                                    ->placeholder('e.g. VEH-123 or AST-001'),
                                TextInput::make('serial_number')
                                    ->label('Serial Number (if applicable)'),
                            ]),

                        Select::make('maintenance_type_id')
                            ->relationship('maintenanceType', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')->required()->unique('maintenance_types', 'name'),
                            ]),

                        TextInput::make('title')
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Scope of Work / Description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Select::make('status_id')
                            ->label('Status')
                            ->relationship('statusRecord', 'name')
                            ->default(fn () => \App\Models\MaintenanceStatus::where('name', 'Scheduled')->first()?->id)
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')->required()->unique('maintenance_statuses', 'name'),
                            ]),

                        Select::make('priority_id')
                            ->label('Priority')
                            ->relationship('priorityRecord', 'name')
                            ->default(fn () => \App\Models\MaintenancePriority::where('name', 'Normal')->first()?->id)
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')->required()->unique('maintenance_priorities', 'name'),
                            ]),
                    ]),

                Section::make('Schedule & Service Provider')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('start_date')
                            ->required(),

                        DatePicker::make('completion_date'),

                        DatePicker::make('next_scheduled_date')
                            ->label('Next Scheduled Maintenance'),

                        Select::make('supplier_id')
                            ->label('Service Provider (External)')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                TextInput::make('email')->email(),
                            ]),

                        Select::make('performed_by_id')
                            ->label('Performed By (Internal Staff)')
                            ->relationship('performedBy', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('first_name')->required(),
                                TextInput::make('last_name')->required(),
                            ]),

                        Toggle::make('is_warranty_improvement')
                            ->label('Warranty Improvement?'),
                    ]),

                Section::make('Cost & Downtime')
                    ->columns(2)
                    ->schema([
                        Select::make('currency_id')
                            ->relationship('currency', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('code')->required()->maxLength(3),
                                TextInput::make('name')->required(),
                                TextInput::make('symbol')->required(),
                                TextInput::make('exchange_rate')->numeric()->required(),
                            ]),

                        TextInput::make('cost')
                            ->numeric()
                            ->prefix('Amount'),

                        TextInput::make('downtime_hours')
                            ->label('Asset Downtime (hours)')
                            ->numeric()
                            ->suffix('hrs'),
                    ]),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
