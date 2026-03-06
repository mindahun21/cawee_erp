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
use Filament\Tables\Table;

class PerformanceEvaluationsRelationManager extends RelationManager
{
    protected static string $relationship = 'performanceEvaluations';

    protected static ?string $title = 'Performance Evaluations';

    public function form(Schema $schema): Schema
    {
        $criteriaField = fn (string $name, string $label) => TextInput::make($name)
            ->label($label)
            ->numeric()
            ->minValue(1)
            ->maxValue(5)
            ->step(1)
            ->required();

        return $schema->components([
            Select::make('evaluator_id')
                ->label('Evaluator')
                ->relationship('evaluator', 'first_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                ->searchable()
                ->required(),

            Select::make('project_id')
                ->label('Project')
                ->relationship('project', 'project_name')
                ->searchable()
                ->preload()
                ->nullable(),

            DatePicker::make('review_period_start')->required(),
            DatePicker::make('review_period_end')->required()->afterOrEqual('review_period_start'),
            DatePicker::make('evaluation_date')->required()->default(now()),

            $criteriaField('effort_initiative', 'Effort & Initiative'),
            $criteriaField('technical_competence', 'Technical Competence'),
            $criteriaField('teamwork', 'Teamwork'),
            $criteriaField('dependability', 'Dependability'),
            $criteriaField('planning_organizing', 'Planning & Organizing'),
            $criteriaField('quality_quantity', 'Quality & Quantity'),
            $criteriaField('priority_setting', 'Priority Setting'),
            $criteriaField('compliance', 'Compliance'),
            $criteriaField('written_communication', 'Written Communication'),
            $criteriaField('coordination_collaboration', 'Coordination & Collaboration'),

            Textarea::make('general_comments')->rows(4)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('evaluator.full_name')->label('Evaluator'),
                TextColumn::make('review_period_start')->date()->label('Period Start'),
                TextColumn::make('review_period_end')->date()->label('Period End'),
                TextColumn::make('cumulative_average')
                    ->label('Score')
                    ->numeric(decimalPlaces: 2)
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4  => 'success',
                        $state >= 3  => 'warning',
                        default      => 'danger',
                    }),
                TextColumn::make('evaluation_date')->date()->sortable(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('evaluation_date', 'desc');
    }
}
