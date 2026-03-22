<?php

declare(strict_types=1);

namespace App\Filament\Resources\ME\BeneficiaryResource\RelationManagers;

use App\Models\ME\MeBaselineAssessment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BaselineAssessmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'baselineAssessments';

    protected static ?string $title = 'Baseline Assessments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('assessment_date')
                ->required()->default(now())->native(false),

            Select::make('project_id')
                ->label('Related Project')
                ->relationship('project', 'name')
                ->searchable()->preload(),

            Select::make('education_level')
                ->options([
                    'none'       => 'None',
                    'primary'    => 'Primary',
                    'secondary'  => 'Secondary',
                    'tertiary'   => 'Tertiary',
                    'vocational' => 'Vocational / TVET',
                ]),

            Select::make('nutrition_status')
                ->options([
                    'normal'                => 'Normal',
                    'moderate_malnutrition' => 'Moderate Malnutrition',
                    'severe_malnutrition'   => 'Severe Malnutrition',
                ])
                ->default('normal'),

            TextInput::make('monthly_income')
                ->label('Monthly Income (ETB)')
                ->numeric()->default(0)->prefix('ETB'),

            Textarea::make('health_status')->label('Health Status')->rows(2),
            Textarea::make('livelihood_info')->label('Livelihood / Occupation')->rows(2),
            Textarea::make('assets')->label('Assets Owned')->rows(2),
            Textarea::make('shelter_condition')->label('Shelter Condition')->rows(2),
            Textarea::make('water_sanitation')->label('Water & Sanitation')->rows(2),
            Textarea::make('notes')->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('assessment_date')->date()->sortable(),
                TextColumn::make('education_level')->badge()->placeholder('—'),
                TextColumn::make('nutrition_status')
                    ->badge()
                    ->color(fn (MeBaselineAssessment $record): string => $record->nutrition_color),
                TextColumn::make('monthly_income')->money('ETB')->sortable(),
                TextColumn::make('assessedBy.name')->label('Assessed By')->placeholder('—'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->defaultSort('assessment_date', 'desc');
    }
}
