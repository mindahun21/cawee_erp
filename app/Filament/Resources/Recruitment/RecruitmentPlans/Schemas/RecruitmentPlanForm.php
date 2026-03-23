<?php

namespace App\Filament\Resources\Recruitment\RecruitmentPlans\Schemas;

use App\Models\Recruitment\RecruitmentApprovalWorkflow;
use App\Models\Recruitment\RecruitmentPlan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class RecruitmentPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                /* ── General Information ── */
                \Filament\Schemas\Components\Section::make('General Information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->required()
                            ->live()
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(fn (callable $set) => $set('job_position_id', null))
                            ->disabled(fn (?RecruitmentPlan $record) => $record && ! $record->isEditable()),

                        Select::make('job_position_id')
                            ->relationship(
                                name: 'jobPosition',
                                titleAttribute: 'title',
                                modifyQueryUsing: fn (Builder $query, Get $get) => $query->where('department_id', $get('department_id'))
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (?RecruitmentPlan $record) => $record && ! $record->isEditable()),

                        Select::make('manager_id')
                            ->label('Hiring Manager')
                            ->relationship('manager', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('vacancies_needed')
                            ->label('Vacancies Needed')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),

                        Select::make('working_from')
                            ->label('Working From')
                            ->options(RecruitmentPlan::workingFromOptions())
                            ->required()
                            ->searchable(),

                        TextInput::make('workplace')
                            ->label('Workplace / Location')
                            ->maxLength(255)
                            ->nullable(),
                    ]),

                /* ── Salary & Budget ── */
                \Filament\Schemas\Components\Section::make('Salary & Budget')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('salary_currency')
                            ->label('Currency')
                            ->options(RecruitmentPlan::currencyOptions())
                            ->default('ETB')
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('budget')
                            ->label('Budget')
                            ->numeric()
                            ->nullable()
                            ->live(onBlur: true)
                            ->columnSpan(1),

                        TextInput::make('salary_from')
                            ->label('Starting Salary (From)')
                            ->numeric()
                            ->nullable()
                            ->lte('budget')
                            ->columnSpan(1),

                        TextInput::make('salary_to')
                            ->label('Starting Salary (To)')
                            ->numeric()
                            ->nullable()
                            ->gte('salary_from')
                            ->lte('budget')
                            ->columnSpan(1),
                    ]),

                /* ── Timeline ── */
                \Filament\Schemas\Components\Section::make('Recruitment Timeline')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->live()
                            ->minDate(fn (?RecruitmentPlan $record) => $record?->exists ? null : now()->toDateString())
                            ->native(false),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->required()
                            ->afterOrEqual('start_date')
                            ->native(false),
                    ]),

                /* ── Approval Workflow ── */
                \Filament\Schemas\Components\Section::make('Approval Workflow')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('approval_workflow_id')
                            ->label('Approval Workflow')
                            ->options(fn () => RecruitmentApprovalWorkflow::query()
                                ->where('document_type', 'recruitment_plan')
                                ->where('is_active', true)
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Select the approval workflow that will be used when this plan is submitted.'),
                    ]),

                /* ── Details ── */
                \Filament\Schemas\Components\Section::make('Details')
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('reason')
                            ->label('Reason for Recruitment')
                            ->required()
                            ->columnSpanFull(),

                        RichEditor::make('job_description')
                            ->label('Job Description')
                            ->columnSpanFull(),

                        \Filament\Forms\Components\Hidden::make('created_by')
                            ->default(auth()->id())
                            ->required(),
                    ]),
            ]);
    }
}
