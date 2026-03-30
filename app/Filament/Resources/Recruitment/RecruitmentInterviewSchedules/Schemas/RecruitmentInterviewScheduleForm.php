<?php

namespace App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\Schemas;

use App\Models\Recruitment\RecruitmentCampaign;
use App\Models\Recruitment\RecruitmentCandidate;
use App\Models\Recruitment\RecruitmentEvaluationFormTemplate;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Hidden;

class RecruitmentInterviewScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Schedule Overview')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('campaign_id')
                                ->relationship('campaign', 'title')
                                ->required()
                                ->reactive()
                                ->searchable()
                                ->preload()
                                ->default(request()->query('campaign_id')),
                            TextInput::make('name')
                                ->label('Schedule Name')
                                ->placeholder('e.g. Round 1 — HR Screen')
                                ->required(),
                            TextInput::make('round')
                                ->numeric()
                                ->minValue(1),
                        ]),
                        Grid::make(3)->schema([
                            DatePicker::make('interview_date')
                                ->required(),
                            TimePicker::make('from_time')
                                ->label('Session Start')
                                ->seconds(false)
                                ->required()
                                ->reactive(),
                            TimePicker::make('to_time')
                                ->label('Session End')
                                ->seconds(false)
                                ->required()
                                ->reactive()
                                ->after('from_time')
                                ->rules([
                                    fn ($get, $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                         if (!$get('interview_date') || !$get('campaign_id') || !$get('from_time')) {
                                             return;
                                         }
                                         
                                         $overlaps = \App\Models\Recruitment\RecruitmentInterviewSchedule::where('campaign_id', $get('campaign_id'))
                                             ->where('interview_date', $get('interview_date'))
                                             ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                             ->where(function ($q) use ($get, $value) {
                                                 $q->where('from_time', '<', $value)
                                                   ->where('to_time', '>', $get('from_time'));
                                             })->exists();
                                         
                                         if ($overlaps) {
                                             $fail('Another schedule for this campaign already overlaps with this time range.');
                                         }
                                    },
                                ]),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('interview_type')
                                ->options([
                                    'in_person' => 'In Person',
                                    'online'    => 'Online (Meeting Link)',
                                    'hybrid'    => 'Hybrid',
                                    'telephone' => 'Telephone',
                                ])
                                ->default('in_person')
                                ->required()
                                ->reactive(),
                            TextInput::make('location')
                                ->label(fn (callable $get) => $get('interview_type') === 'online' ? 'Meeting Link' : 'Location / Room')
                                ->placeholder(fn (callable $get) => $get('interview_type') === 'online' ? 'https://zoom.us/j/...' : 'Conference Room A')
                                ->required()
                                ->url(fn (callable $get) => $get('interview_type') === 'online'),
                        ]),
                        Grid::make(1)->schema([
                            Select::make('evaluation_template_id')
                                ->label('Evaluation Template')
                                ->options(function (callable $get) {
                                    $campaignId = $get('campaign_id');
                                    if (!$campaignId) {
                                        return RecruitmentEvaluationFormTemplate::where('is_active', true)
                                            ->whereNull('job_position_id')
                                            ->pluck('name', 'id');
                                    }
                                    
                                    $campaign = RecruitmentCampaign::find($campaignId);
                                    if (!$campaign) return [];
                                    
                                    $templates = RecruitmentEvaluationFormTemplate::where('is_active', true)
                                        ->where(function ($query) use ($campaign) {
                                            $query->where('job_position_id', $campaign->job_position_id)
                                                  ->orWhereNull('job_position_id');
                                        })
                                        ->get();
                                        
                                    return $templates->pluck('name', 'id')->toArray();
                                })
                                ->required()
                                ->searchable()
                                ->preload(),
                        ]),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),

                Section::make('Interview Panelists')
                    ->description('Select the members who will conduct the interviews.')
                    ->schema([
                            Repeater::make('scheduleInterviewers')
                                ->relationship('scheduleInterviewers')
                                ->label('Interview Panel')
                                ->schema([
                                    Select::make('user_id')
                                        ->label('Interviewer')
                                        ->relationship('user', 'name')
                                        ->required()
                                        ->searchable()
                                        ->preload(),
                                    Select::make('role')
                                        ->options([
                                            'chair' => 'Chair',
                                            'interviewer' => 'Interviewer',
                                            'observer' => 'Observer',
                                        ])
                                        ->default('interviewer')
                                        ->required(),
                                    TextInput::make('notes')
                                        ->label('Notes')
                                        ->placeholder('Focus area or instructions...')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->defaultItems(1)
                                ->itemLabel(fn (array $state): ?string => ($state['user_id'] ?? null) ? \App\Models\User::find($state['user_id'])?->name : null),
                    ]),

                Section::make('Candidates & Time Slots')
                    ->description('Assign candidates to specific time slots within the session.')
                    ->schema([
                        Repeater::make('scheduleCandidates')
                            ->relationship('scheduleCandidates')
                            ->minItems(1)
                            ->schema([
                                Select::make('candidate_id')
                                    ->label('Candidate')
                                    ->required()
                                    ->searchable()
                                    ->options(function (callable $get) {
                                        $campaignId = $get('../../campaign_id');
                                        if (!$campaignId) return [];
                                        
                                        return RecruitmentCandidate::whereHas('applications', function ($query) use ($campaignId) {
                                            $query->where('campaign_id', $campaignId)
                                                  ->whereIn('status', ['shortlisted', 'interview_scheduled']);
                                        })->get()->pluck('full_name', 'id');
                                    })
                                    ->disableOptionWhen(function ($value, $state, $get) {
                                        $selected = collect($get('../../scheduleCandidates'))
                                            ->pluck('candidate_id')
                                            ->filter(fn ($id) => $id !== null && $id != $state)
                                            ->values();
                                        return $selected->contains($value);
                                    }),
                                TimePicker::make('candidate_from_time')
                                    ->label('Slot Start')
                                    ->seconds(false)
                                    ->required()
                                    ->reactive(),
                                TimePicker::make('candidate_to_time')
                                    ->label('Slot End')
                                    ->seconds(false)
                                    ->required()
                                    ->reactive()
                                    ->after('candidate_from_time'),
                            ])
                            ->columns(3)
                            ->itemLabel(fn (array $state): ?string => ($state['candidate_id'] ?? null) ? RecruitmentCandidate::find($state['candidate_id'])?->full_name : null)
                            ->rule(function (callable $get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $overallFrom = $get('from_time');
                                    $overallTo = $get('to_time');
                                    
                                    if (!$overallFrom || !$overallTo) return;

                                    // Reset to numeric keys to avoid string + int TypeError
                                    $slots = array_values(is_array($value) ? $value : []);
                                    foreach ($slots as $index => $slot) {
                                        $start = $slot['candidate_from_time'] ?? null;
                                        $end = $slot['candidate_to_time'] ?? null;

                                        if (!$start || !$end) continue;

                                        // Bounds validation
                                        if ($start < $overallFrom) {
                                            $fail('Slot #' . ($index + 1) . " starts before session beginning ($overallFrom).");
                                        }
                                        if ($end > $overallTo) {
                                            $fail('Slot #' . ($index + 1) . " ends after session conclusion ($overallTo).");
                                        }

                                        // Overlap validation against other slots
                                        foreach ($slots as $ci => $compareSlot) {
                                            if ($index === $ci) continue;
                                            $compareStart = $compareSlot['candidate_from_time'] ?? null;
                                            $compareEnd = $compareSlot['candidate_to_time'] ?? null;

                                            if (!$compareStart || !$compareEnd) continue;

                                            if ($start < $compareEnd && $end > $compareStart) {
                                                $fail('Time slots overlap (Slot #' . ($index + 1) . ' and Slot #' . ($ci + 1) . ').');
                                                return;
                                            }
                                        }
                                    }
                                };
                            }),
                    ]),
                
                Hidden::make('created_by')
                    ->default(auth()->id()),
                
                Section::make('Approval Workflow')
                    ->schema([
                        Select::make('approval_workflow_id')
                            ->relationship('approvalWorkflow', 'name', fn($query) => $query->where('document_type', 'recruitment_interview_schedule')->where('is_active', true))
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }
}
