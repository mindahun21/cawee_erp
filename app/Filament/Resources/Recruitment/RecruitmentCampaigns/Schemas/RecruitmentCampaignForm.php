<?php

namespace App\Filament\Resources\Recruitment\RecruitmentCampaigns\Schemas;

use App\Models\Recruitment\RecruitmentPlan;
use App\Models\Recruitment\RecruitmentCampaign;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Components\Utilities\Set as UtilitiesSet;
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
                                    ->description('Optionally link a plan to auto-populate fields below.')
                                    ->collapsible()
                                    ->schema([
                                        Select::make('recruitment_plan_id')
                                            ->label('Recruitment Plan')
                                            ->options(function () {
                                                return RecruitmentPlan::query()
                                                    ->where('status', RecruitmentPlan::STATUS_APPROVED)
                                                    ->with(['department', 'jobPosition'])
                                                    ->orderByDesc('id')
                                                    ->get()
                                                    ->mapWithKeys(function (RecruitmentPlan $plan) {
                                                        $label = $plan->title ?? (($plan->department?->name ?? '—') . ' — ' . ($plan->jobPosition?->title ?? '—') . ' (' . $plan->status . ')');
                                                        return [$plan->id => $label];
                                                    });
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function (?string $state, callable $set) {
                                                if (! $state) {
                                                    return;
                                                }

                                                $plan = RecruitmentPlan::find($state);
                                                if (! $plan) {
                                                    return;
                                                }

                                                // Populate shared fields from plan → campaign
                                                $set('job_position_id', $plan->job_position_id);
                                                $set('manager_id', $plan->manager_id);
                                                $set('vacancies_needed', $plan->vacancies_needed);
                                                $set('salary_min', $plan->salary_from);
                                                $set('salary_max', $plan->salary_to);
                                                $set('currency', $plan->salary_currency);
                                                $set('start_date', $plan->start_date?->format('Y-m-d'));
                                                $set('end_date', $plan->end_date?->format('Y-m-d'));
                                                $set('reason_for_recruitment', $plan->reason);
                                                $set('description', $plan->job_description);
                                                $set('employment_type', self::mapEmploymentType($plan->working_from));
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
                                                ->preload(),
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
                                                ->default('full_time'),
                                            TextInput::make('vacancies_needed')
                                                ->required()
                                                ->numeric()
                                                ->minValue(1)
                                                ->default(1),
                                            TextInput::make('location'),
                                        ]),
                                    ]),

                                // ── Compensation ──
                                Section::make('Compensation')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('currency')
                                                ->required()
                                                ->default('USD'),
                                            TextInput::make('salary_min')
                                                ->numeric()
                                                ->minValue(0),
                                            TextInput::make('salary_max')
                                                ->numeric()
                                                ->minValue(0)
                                                ->gte('salary_min'),
                                        ]),
                                    ]),

                                // ── Schedule & Visibility ──
                                Section::make('Schedule & Visibility')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            DatePicker::make('start_date'),
                                            DatePicker::make('end_date'),
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
                                    ->searchable()
                                    ->preload(),
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'submitted' => 'Submitted',
                                        'active' => 'Active',
                                        'paused' => 'Paused',
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
