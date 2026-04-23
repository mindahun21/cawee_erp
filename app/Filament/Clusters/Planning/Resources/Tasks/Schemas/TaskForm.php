<?php

namespace App\Filament\Clusters\Planning\Resources\Tasks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                    Select::make('plan_id')
                        ->relationship('plan', 'title')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Select::make('employee_id')
                        ->label('Assigned Staff')
                        ->relationship('employee', 'first_name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                        ->searchable()
                        ->preload(),
                    DatePicker::make('deadline')
                        ->required(),
                    Select::make('priority')
                        ->options([
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                        ])
                        ->required()
                        ->default('medium'),
                    Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'in_progress' => 'In Progress',
                            'completed' => 'Completed',
                        ])
                        ->required()
                        ->default('pending'),
                ]),
                \Filament\Schemas\Components\Section::make('Progress & Files')
                    ->columns(2)
                    ->schema([
                        \Filament\Forms\Components\Slider::make('progress_percentage')
                            ->step(5)
                            ->default(0)
                            ->label('Progress (%)'),
                        \Filament\Forms\Components\FileUpload::make('attachments')
                            ->multiple()
                            ->disk('public')
                            ->directory('task-attachments')
                            ->preserveFilenames(),
                    ]),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
