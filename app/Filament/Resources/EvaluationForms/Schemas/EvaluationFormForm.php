<?php

namespace App\Filament\Resources\EvaluationForms\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid as ComponentsGrid;
use Filament\Schemas\Schema;

class EvaluationFormForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('form_name')
                    ->label('Form Name')
                    ->required()
                    ->maxLength(255),

                Select::make('recruitment_position_id')
                    ->label('Recruitment Position')
                    ->options(\App\Models\RecruitmentPosition::pluck('job_position', 'id'))
                    ->required()
                    ->preload()
                    ->searchable(),

                Repeater::make('groups')
                    ->relationship('groups')
                    ->label('Criteria Groups')
                    ->schema([
                        Select::make('criteria_group_id')
                            ->label('Group Criteria')
                            ->options(function () {
                                return \App\Models\EvaluationCriteria::where('criteria_type', 'group criteria')
                                    ->pluck('criteria_name', 'id');
                            })
                            ->required()
                            ->reactive(),
                        Repeater::make('criteria')
                            ->relationship('criteria')
                            ->label('Evaluation Criteria')
                            ->schema([
                                ComponentsGrid::make(12)
                                    ->schema([
                                        Select::make('evaluation_criteria_id')
                                            ->label('Evaluation Criteria')
                                            ->options(function ($get) {
                                                $groupId = $get('../../criteria_group_id');
                                                if (!$groupId) return [];
                                                return \App\Models\EvaluationCriteria::where('criteria_type', 'criteria')
                                                    ->where('group_id', $groupId) // child criteria of selected group
                                                    ->pluck('criteria_name', 'id');
                                            })
                                            ->searchable()
                                            ->required()
                                            ->columnSpan(7),

                                        TextInput::make('proportion')
                                            ->label('Proportion')
                                            ->numeric()
                                            ->suffix('%')
                                            ->required()
                                            ->columnSpan(5),
                                    ]),
                            ])
                            ->columns(12)
                            ->defaultItems(1)
                            ->addActionLabel('Add Criteria'),
                    ])
                    ->defaultItems(1)
                    ->addActionLabel('Add Group'),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3),
            ]);
    }
}
