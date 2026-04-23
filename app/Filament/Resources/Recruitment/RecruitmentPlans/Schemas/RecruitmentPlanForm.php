<?php

namespace App\Filament\Resources\Recruitment\RecruitmentPlans\Schemas;

use App\Models\JobPosition;
use App\Models\Recruitment\RecruitmentApprovalWorkflow;
use App\Models\Recruitment\RecruitmentPlan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

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
                                modifyQueryUsing: fn (Builder $query, Get $get) => $get('department_id')
                                    ? $query->where('department_id', $get('department_id'))
                                    : $query
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get, ?RecruitmentPlan $record) {
                                if (! $state) {
                                    return;
                                }

                                $position = JobPosition::find($state);
                                if (! $position) {
                                    return;
                                }

                                if (! $get('department_id') || $get('department_id') != $position->department_id) {
                                    $set('department_id', (string) $position->department_id);
                                }

                                $set('salary_from', $position->salary_min);
                                $set('salary_to', $position->salary_max);

                                if ($position->description) {
                                    $set('job_description', $position->description);
                                }

                                $availableVacancies = \App\Services\Recruitment\VacancyAccountingService::getAvailableVacancies($position, $record);
                                $set('vacancies_needed', max($availableVacancies, 1));

                                if ($position->salary_max && $availableVacancies > 0) {
                                    $set('budget', round($position->salary_max * max($availableVacancies, 1), 2));
                                }
                            })
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
                            ->default(1)
                            ->live(onBlur: true)
                            ->maxValue(function (Get $get, ?RecruitmentPlan $record) {
                                $positionId = $get('job_position_id');
                                if (! $positionId) {
                                    return null;
                                }
                                $position = JobPosition::find($positionId);
                                if (! $position) {
                                    return null;
                                }
                                return \App\Services\Recruitment\VacancyAccountingService::getAvailableVacancies($position, $record);
                            })
                            ->helperText(function (Get $get, ?RecruitmentPlan $record) {
                                $positionId = $get('job_position_id');
                                if (! $positionId) {
                                    return null;
                                }
                                $position = JobPosition::find($positionId);
                                if (! $position) {
                                    return null;
                                }
                                $available = \App\Services\Recruitment\VacancyAccountingService::getAvailableVacancies($position, $record);
                                return "Max available: {$available} (Position: {$position->vacancy_count} total − {$position->employees()->count()} filled − " . \App\Services\Recruitment\VacancyAccountingService::getConsumedVacancies($position, $record) . ' in active plans/campaigns)';
                            })
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get) {

                                $salaryTo = $get('salary_to');
                                if ($state && $salaryTo) {
                                    $set('budget', round((float) $salaryTo * (int) $state, 2));
                                }
                            }),

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
                            ->minValue(function (Get $get) {
                                $salaryTo = $get('salary_to');
                                $vacancies = $get('vacancies_needed');
                                if ($salaryTo && $vacancies) {
                                    return round((float) $salaryTo * (int) $vacancies, 2);
                                }
                                return 0;
                            })
                            ->required()
                            ->helperText('Minimum = Salary To × Vacancies. Can be set higher to cover additional costs.')
                            ->live(onBlur: true)
                            ->columnSpan(1),

                        TextInput::make('salary_from')
                            ->label('Starting Salary (From)')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->maxValue(function (Get $get) {
                                $positionId = $get('job_position_id');
                                if (! $positionId) {
                                    return null;
                                }
                                $position = JobPosition::find($positionId);
                                return $position?->salary_min;
                            })
                            ->helperText(function (Get $get) {
                                $positionId = $get('job_position_id');
                                if (! $positionId) {
                                    return null;
                                }
                                $position = JobPosition::find($positionId);
                                return $position?->salary_min
                                    ? "Position min: {$position->salary_min}"
                                    : null;
                            })
                            ->live(onBlur: true)
                            ->columnSpan(1),

                        TextInput::make('salary_to')
                            ->label('Starting Salary (To)')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->gte('salary_from')
                            ->maxValue(function (Get $get) {
                                $positionId = $get('job_position_id');
                                if (! $positionId) {
                                    return null;
                                }
                                $position = JobPosition::find($positionId);
                                return $position?->salary_max;
                            })
                            ->helperText(function (Get $get) {
                                $positionId = $get('job_position_id');
                                if (! $positionId) {
                                    return null;
                                }
                                $position = JobPosition::find($positionId);
                                return $position?->salary_max
                                    ? "Position max: {$position->salary_max}"
                                    : null;
                            })
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get) {

                                $vacancies = $get('vacancies_needed');
                                if ($state && $vacancies) {
                                    $set('budget', round((float) $state * (int) $vacancies, 2));
                                }
                            })
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
                                ->with('stages')
                                ->where('document_type', 'recruitment_plan')
                                ->where('is_active', true)
                                ->get()
                                ->mapWithKeys(fn ($w) => [$w->id => "{$w->name} ({$w->stages->count()} stages)"])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required()
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
