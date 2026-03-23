<?php

namespace App\Filament\Resources\AssetAssignments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Assignment Info')
                    ->columns(2)
                    ->schema([
                        TextInput::make('assignment_no')
                            ->label('Reference No')
                            ->prefix(fn () => \App\Models\PrefixSetting::getPrefix('asset_assignment_no'))
                            ->default(fn () => \App\Models\PrefixSetting::where('key', 'asset_assignment_no')->value('next_number'))
                            ->dehydrateStateUsing(fn ($state) => \App\Models\PrefixSetting::getPrefix('asset_assignment_no') . $state)
                            ->unique(\App\Models\AssetAssignment::class, 'assignment_no', ignoreRecord: true)
                            ->live(onBlur: true)
                            ->required(),
                        Select::make('asset_id')
                            ->relationship('asset', 'name', fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('is_fixed_asset', true))
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Select::make('employee_id')
                            ->label('Assigned to staff')
                            ->relationship('employee', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->searchable()
                            ->preload(),
                        TextInput::make('purpose')
                            ->label('Purpose / Reason')
                            ->maxLength(255),
                        Select::make('department_id')
                            ->label('Assigned To Department')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('project_id')
                            ->label('Assigned To Project')
                            ->relationship('project', 'project_name')
                            ->searchable()
                            ->preload(),
                        Select::make('location_id')
                            ->label('Assigned To Location')
                            ->relationship('location', 'location_name')
                            ->searchable()
                            ->preload(),
                        DatePicker::make('assigned_date')
                            ->default(now())
                            ->required(),
                        DatePicker::make('due_date'),
                        DatePicker::make('expected_return_date'),
                        DatePicker::make('returned_date'),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'returned' => 'Returned',
                                'overdue' => 'Overdue',
                            ])
                            ->default('active')
                            ->required(),
                    ]),
                Section::make('Condition & Remarks')
                    ->columns(2)
                    ->schema([
                        TextInput::make('condition_on_assignment')
                            ->maxLength(255),
                        TextInput::make('condition_on_return')
                            ->maxLength(255),
                        Textarea::make('remarks')
                            ->columnSpanFull(),
                        \Filament\Forms\Components\FileUpload::make('attachments')
                            ->multiple()
                            ->directory('assignments')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
