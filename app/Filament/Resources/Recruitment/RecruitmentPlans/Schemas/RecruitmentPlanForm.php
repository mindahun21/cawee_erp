<?php

namespace App\Filament\Resources\Recruitment\RecruitmentPlans\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class RecruitmentPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('General Information')
                    ->columnSpanFull()
                    ->columns(['default' => 2])
                    ->schema([
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->required()
                            ->live()
                            ->searchable()
                            ->preload(),
                        Select::make('job_position_id')
                            ->relationship(
                                name: 'jobPosition',
                                titleAttribute: 'title',
                                modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query, \Filament\Schemas\Components\Utilities\Get $get) => $query->where('department_id', $get('department_id'))
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('vacancies_needed')
                            ->label('Vacancies Needed')
                            ->required()
                            ->numeric()
                            ->default(1),
                        DatePicker::make('expected_hire_date')
                            ->label('Expected Hire Date')
                            ->required(),
                        TextInput::make('budget')
                            ->numeric()
                            ->prefix('ETB'),
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'approved' => 'Approved',
                                'closed' => 'Closed',
                            ])
                            ->required()
                            ->default('draft'),
                    ]),

                \Filament\Schemas\Components\Section::make('Additional Details')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Forms\Components\RichEditor::make('notes')
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Hidden::make('created_by')
                            ->default(auth()->id())
                            ->required(),
                    ]),
            ]);
    }
}
