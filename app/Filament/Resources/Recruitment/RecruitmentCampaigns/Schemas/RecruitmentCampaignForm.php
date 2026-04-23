<?php

namespace App\Filament\Resources\Recruitment\RecruitmentCampaigns\Schemas;

use App\Models\JobPosition;
use App\Models\Recruitment\RecruitmentPlan;
use App\Models\Recruitment\RecruitmentCampaign;
use App\Services\Recruitment\VacancyAccountingService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class RecruitmentCampaignForm
{
    /**
     * Map RecruitmentPlan.working_from values to Campaign.employment_type values.
     */
    private static function mapEmploymentType(?string $workingFrom): string
    {
        return match ($workingFrom) {
            'Full-Time'   => 'full_time',
            'Part-Time'   => 'part_time',
            'Contract'    => 'contract',
            'Internship'  => 'internship',
            'Temporary'   => 'contract',
            default       => 'full_time',
        };
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Campaign Details')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('General Info')
                            ->schema([
                                // ── Linked Plan (first) ──
                                Section::make('Linked Recruitment Plan')
                                    ->description('Optionally link a plan to auto-populate fields below. If linked, core settings become restricted to the plan\'s bounds.')
                                    ->collapsible()
                                    ->schema([
                                        Select::make('recruitment_plan_id')
                                            ->label('Recruitment Plan')
                                            ->options(function (?RecruitmentCampaign $record) {
                                                return RecruitmentPlan::query()
                                                    ->where('status', RecruitmentPlan::STATUS_APPROVED)
                                                    ->where(function ($query) {
                                                        $query->whereNull('end_date')
                                                              ->orWhere('end_date', '>=', now()->startOfDay());
                                                    })
                                                    ->with(['department', 'jobPosition'])
                                                    ->orderByDesc('id')
                                                    ->get()
                                                    ->filter(function (RecruitmentPlan $plan) use ($record) {

                                                        if ($record?->exists && $record->recruitment_plan_id === $plan->id) {
                                                            return true;
                                                        }

                                                        $usedVacancies = RecruitmentCampaign::where('recruitment_plan_id', $plan->id)
                                                            ->whereIn('status', [
                                                                RecruitmentCampaign::STATUS_SUBMITTED,
                                                                RecruitmentCampaign::STATUS_ACTIVE,
                                                                RecruitmentCampaign::STATUS_FULL,
                                                            ])
                                                            ->sum('vacancies_needed');
                                                            
                                                        return ($plan->vacancies_needed - $usedVacancies) > 0;
                                                    })
                                                    ->mapWithKeys(function (RecruitmentPlan $plan) {
                                                        $label = $plan->title ?? (($plan->department?->name ?? '—') . ' — ' . ($plan->jobPosition?->title ?? '—') . ' (' . $plan->status . ')');
                                                        return [$plan->id => $label];
                                                    });
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function (?string $state, Set $set) {
                                                if (! $state) {
                                                    return;
                                                }

                                                $plan = RecruitmentPlan::find($state);
                                                if (! $plan) {
                                                    return;
                                                }

                                                $set('job_position_id', (string) $plan->job_position_id);
                                                $set('title', $plan->title);
                                                $set('manager_id', (string) $plan->manager_id);

                                                $usedVacancies = RecruitmentCampaign::where('recruitment_plan_id', $plan->id)->where('status', '!=', 'rejected')->sum('vacancies_needed');
                                                $set('vacancies_needed', max(1, $plan->vacancies_needed - $usedVacancies));
                                                
                                                $set('salary_min', $plan->salary_from);
                                                $set('salary_max', $plan->salary_to);
                                                $set('currency', $plan->salary_currency);
                                                
                                                $set('start_date', $plan->start_date?->format('Y-m-d'));
                                                $set('end_date', $plan->end_date?->format('Y-m-d'));
                                                $set('reason_for_recruitment', $plan->reason);
                                                $set('description', $plan->job_description);
                                                $set('employment_type', self::mapEmploymentType($plan->working_from));
                                                $set('location', $plan->workplace);
                                                
                                                if ($plan->jobPosition && $plan->jobPosition->requirements) {
                                                    $set('requirements', $plan->jobPosition->requirements);
                                                }
                                            }),
                                    ]),

                                // ── Job Details ──
                                Section::make('Job Details')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('title')
                                                ->required(),
                                                
                                            Select::make('job_position_id')
                                                ->relationship('jobPosition', 'title')
                                                ->required()
                                                ->searchable()
                                                ->preload()
                                                ->live()
                                                ->disabled(fn (Get $get) => filled($get('recruitment_plan_id')))
                                                ->dehydrated()
                                                ->afterStateUpdated(function (?string $state, Set $set, Get $get, ?RecruitmentCampaign $record) {

                                                    if ($get('recruitment_plan_id') || ! $state) {
                                                        return;
                                                    }
                                                    
                                                    $position = JobPosition::find($state);
                                                    if (! $position) return;

                                                    $set('salary_min', $position->salary_min);
                                                    $set('salary_max', $position->salary_max);
                                                    
                                                    if ($position->description) {
                                                        $set('description', $position->description);
                                                    }
                                                    if ($position->requirements) {
                                                        $set('requirements', $position->requirements);
                                                    }

                                                    $available = VacancyAccountingService::getAvailableVacancies($position, null, $record);
                                                    $set('vacancies_needed', max(1, $available));
                                                }),
                                                
                                            Select::make('channel_id')
                                                ->relationship('channel', 'name')
                                                ->required()
                                                ->searchable()
                                                ->preload(),
                                                
                                            Select::make('manager_id')
                                                ->relationship('manager', 'name')
                                                ->label('Manager')
                                                ->searchable()
                                                ->preload(),
                                                
                                            Select::make('employment_type')
                                                ->required()
                                                ->options([
                                                    'full_time'  => 'Full Time',
                                                    'part_time'  => 'Part Time',
                                                    'contract'   => 'Contract',
                                                    'internship' => 'Internship',
                                                ])
                                                ->default('full_time')
                                                ->disabled(fn (Get $get) => filled($get('recruitment_plan_id')))
                                                ->dehydrated(),
                                                
                                            TextInput::make('vacancies_needed')
                                                ->required()
                                                ->numeric()
                                                ->minValue(1)
                                                ->default(1)
                                                ->maxValue(function (Get $get, ?RecruitmentCampaign $record) {
                                                    if ($planId = $get('recruitment_plan_id')) {
                                                        $plan = RecruitmentPlan::find($planId);
                                                        if (!$plan) return null;

                                                        $usedQuery = RecruitmentCampaign::where('recruitment_plan_id', $planId)
                                                            ->whereIn('status', ['submitted', 'active']);
                                                        if ($record?->exists) {
                                                            $usedQuery->where('id', '!=', $record->id);
                                                        }
                                                        $used = $usedQuery->sum('vacancies_needed');
                                                        return max(0, $plan->vacancies_needed - $used);
                                                    }
                                                    
                                                    if ($posId = $get('job_position_id')) {
                                                        $position = JobPosition::find($posId);
                                                        if (!$position) return null;
                                                        return VacancyAccountingService::getAvailableVacancies($position, null, $record);
                                                    }
                                                    return null;
                                                })
                                                ->helperText(function (Get $get, ?RecruitmentCampaign $record) {
                                                    if ($planId = $get('recruitment_plan_id')) {
                                                        $plan = RecruitmentPlan::find($planId);
                                                        return $plan ? "Constrained by Plan: {$plan->vacancies_needed} total." : null;
                                                    }
                                                    if ($posId = $get('job_position_id')) {
                                                        $position = JobPosition::find($posId);
                                                        if ($position) {
                                                            $available = VacancyAccountingService::getAvailableVacancies($position, null, $record);
                                                            return "Max available from Job Position: {$available}.";
                                                        }
                                                    }
                                                    return null;
                                                }),
                                                
                                            TextInput::make('max_applications')
                                                ->numeric()
                                                ->gte('vacancies_needed')
                                                ->helperText('Optional. Once reached, the campaign will stop receiving new applications.'),
                                                
                                            TextInput::make('location')
                                                ->disabled(fn (Get $get) => filled($get('recruitment_plan_id')))
                                                ->dehydrated(),
                                        ]),
                                    ]),

                                // ── Compensation ──
                                Section::make('Compensation')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Select::make('currency')
                                                ->required()
                                                ->options(RecruitmentPlan::currencyOptions())
                                                ->default('ETB')
                                                ->disabled(fn (Get $get) => filled($get('recruitment_plan_id')))
                                                ->dehydrated(),
                                                
                                            TextInput::make('salary_min')
                                                ->numeric()
                                                ->minValue(function(Get $get) {
                                                    if ($planId = $get('recruitment_plan_id')) {
                                                        return RecruitmentPlan::find($planId)?->salary_from ?? 0;
                                                    }
                                                    if ($posId = $get('job_position_id')) {
                                                        return JobPosition::find($posId)?->salary_min ?? 0;
                                                    }
                                                    return 0;
                                                })
                                                ->helperText(function(Get $get) {
                                                    if ($planId = $get('recruitment_plan_id')) {
                                                        $min = RecruitmentPlan::find($planId)?->salary_from;
                                                        return $min ? "Cannot be lower than Plan minimum ({$min})" : null;
                                                    }
                                                    if ($posId = $get('job_position_id')) {
                                                        $min = JobPosition::find($posId)?->salary_min;
                                                        return $min ? "Cannot be lower than Job minimum ({$min})" : null;
                                                    }
                                                    return null;
                                                }),
                                                
                                            TextInput::make('salary_max')
                                                ->numeric()
                                                ->gte('salary_min')
                                                ->maxValue(function(Get $get) {
                                                    if ($planId = $get('recruitment_plan_id')) {
                                                        return RecruitmentPlan::find($planId)?->salary_to;
                                                    }
                                                    if ($posId = $get('job_position_id')) {
                                                        return JobPosition::find($posId)?->salary_max;
                                                    }
                                                    return null;
                                                })
                                                ->helperText(function(Get $get) {
                                                    if ($planId = $get('recruitment_plan_id')) {
                                                        $max = RecruitmentPlan::find($planId)?->salary_to;
                                                        return $max ? "Cannot exceed Plan maximum ({$max})" : null;
                                                    }
                                                    if ($posId = $get('job_position_id')) {
                                                        $max = JobPosition::find($posId)?->salary_max;
                                                        return $max ? "Cannot exceed Job maximum ({$max})" : null;
                                                    }
                                                    return null;
                                                }),
                                        ]),
                                    ]),

                                // ── Schedule & Visibility ──
                                Section::make('Schedule & Visibility')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            DatePicker::make('start_date')
                                                ->minDate(fn (Get $get) => $get('recruitment_plan_id') 
                                                    ? RecruitmentPlan::find($get('recruitment_plan_id'))?->start_date?->toDateString() 
                                                    : null)
                                                ->maxDate(fn (Get $get) => $get('recruitment_plan_id') 
                                                    ? RecruitmentPlan::find($get('recruitment_plan_id'))?->end_date?->toDateString() 
                                                    : null)
                                                ->helperText(fn (Get $get) => $get('recruitment_plan_id') ? "Must be within Plan's approved timeframe" : null),
                                                
                                            DatePicker::make('end_date')
                                                ->afterOrEqual('start_date')
                                                ->maxDate(fn (Get $get) => $get('recruitment_plan_id') 
                                                    ? RecruitmentPlan::find($get('recruitment_plan_id'))?->end_date?->toDateString() 
                                                    : null)
                                                ->helperText(fn (Get $get) => $get('recruitment_plan_id') ? "Cannot exceed Plan's end date" : null),
                                                
                                            Toggle::make('display_salary')
                                                ->required(),
                                                
                                            Toggle::make('is_public')
                                                ->required()
                                                ->default(true),
                                        ]),
                                    ]),

                                // ── Description ──
                                Section::make('Description')
                                    ->schema([
                                        RichEditor::make('description')
                                            ->columnSpanFull(),
                                        RichEditor::make('requirements')
                                            ->columnSpanFull(),
                                        RichEditor::make('reason_for_recruitment')
                                            ->columnSpanFull(),
                                    ]),

                                // ── SEO & Notes ──
                                Section::make('SEO & Notes')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('meta_title'),
                                            Textarea::make('meta_description')
                                                ->rows(2),
                                        ]),
                                        Textarea::make('notes')
                                            ->columnSpanFull(),
                                    ]),

                                Hidden::make('created_by')
                                    ->default(fn () => auth()->id()),
                                Select::make('approval_workflow_id')
                                    ->label('Approval Workflow')
                                    ->relationship('approvalWorkflow', 'name', fn ($query) => $query->where('document_type', 'recruitment_campaign')->where('is_active', true))
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} (" . $record->stages()->count() . " stages)")
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'submitted' => 'Submitted',
                                        'active' => 'Active',
                                        'paused' => 'Paused',
                                        'full' => 'Applications Full',
                                        'closed' => 'Closed',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->default('draft')
                                    ->required()
                                    ->hidden(function (?RecruitmentCampaign $record) {
                                        if (!$record) return true; // hidden on create
                                        if (auth()->user()->hasRole('super_admin')) {
                                            if (empty($record->approval_workflow_id) && !in_array($record->status, ['active', 'rejected'])) {
                                                return false; // NOT hidden
                                            }
                                        }
                                        return true; // hidden
                                    }),
                            ]),

                        Tab::make('Requirements')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('candidate_age_from')
                                        ->numeric()
                                        ->minValue(0),
                                    TextInput::make('candidate_age_to')
                                        ->numeric()
                                        ->minValue(0)
                                        ->gte('candidate_age_from'),
                                    Select::make('candidate_gender')
                                        ->options([
                                            'male'   => 'Male',
                                            'female' => 'Female',
                                        ]),
                                    Select::make('candidate_literacy')
                                        ->options([
                                            'not_required' => 'Not Required',
                                            'university'   => 'University',
                                            'masters'      => 'Masters',
                                            'phd'          => 'PhD',
                                        ]),
                                    Select::make('candidate_seniority')
                                        ->options([
                                            'none'         => 'None',
                                            '1_year'       => '1 Year',
                                            '2_years'      => '2 Years',
                                            'over_5_years' => 'Over 5 Years',
                                        ]),
                                    TextInput::make('candidate_height_min')
                                        ->numeric()
                                        ->minValue(0),
                                    TextInput::make('candidate_weight_min')
                                        ->numeric()
                                        ->minValue(0),
                                ]),
                            ]),
                    ]),
            ]);
    }
}
