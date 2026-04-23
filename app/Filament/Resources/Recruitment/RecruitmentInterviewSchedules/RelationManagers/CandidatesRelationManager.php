<?php

namespace App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

class CandidatesRelationManager extends RelationManager
{
    protected static string $relationship = 'candidates';

    protected static ?string $recordTitleAttribute = 'first_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Not editable here, use the main form repeater for slots
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->label('Candidate Name')
                    ->formatStateUsing(fn ($record) => $record->full_name)
                    ->searchable(),
                TextColumn::make('last_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pivot.candidate_from_time')
                    ->label('Slot Start')
                    ->time('H:i'),
                TextColumn::make('pivot.candidate_to_time')
                    ->label('Slot End')
                    ->time('H:i'),
            ])
            ->actions([
                \Filament\Actions\Action::make('evaluate')
                    ->label('Evaluate')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('primary')
                    ->visible(function ($record, $livewire) {
                        $schedule = $livewire->getOwnerRecord();

                        $invalidStatuses = [
                            \App\Models\Recruitment\RecruitmentInterviewSchedule::STATUS_DRAFT,
                            \App\Models\Recruitment\RecruitmentInterviewSchedule::STATUS_REJECTED,
                            \App\Models\Recruitment\RecruitmentInterviewSchedule::STATUS_CANCELLED,
                        ];
                        
                        if (in_array($schedule->status, $invalidStatuses)) {
                            return false;
                        }

                        if ($schedule->interview_date && $record->pivot && $record->pivot->candidate_from_time) {
                            $startTimeStr = $schedule->interview_date->format('Y-m-d') . ' ' . $record->pivot->candidate_from_time;
                            $startTime = \Carbon\Carbon::parse($startTimeStr, 'Africa/Addis_Ababa');
                            
                            if (now('Africa/Addis_Ababa')->lessThan($startTime)) {
                                return false;
                            }
                        }

                        $isPanelist = $schedule->interviewers()
                            ->where('user_id', auth()->id())
                            ->wherePivotIn('role', ['chair', 'interviewer', 'Chair', 'Interviewer'])
                            ->exists();
                            
                        if (!$isPanelist) {
                            return false;
                        }
                        
                        // Hide if already evaluated by this user
                        return !\App\Models\Recruitment\RecruitmentCandidateEvaluation::where('schedule_id', $schedule->id)
                            ->where('candidate_id', $record->id)
                            ->where('interviewer_id', auth()->id())
                            ->exists();
                    })
                    ->form(function ($livewire) {
                        $schedule = $livewire->getOwnerRecord();
                        $templateId = $schedule->evaluation_template_id;
                        
                        if (!$templateId) {
                            return [\Filament\Forms\Components\Placeholder::make('error')->content('No evaluation template attached to this schedule.')];
                        }

                        $lines = \App\Models\Recruitment\RecruitmentEvaluationFormTemplateLine::with('criteria')
                            ->where('template_id', $templateId)
                            ->get();

                        if ($lines->isEmpty()) {
                            return [\Filament\Forms\Components\Placeholder::make('error')->content('No criteria defined in the attached template.')];
                        }

                        $components = [];
                        
                        foreach ($lines as $line) {
                            if (!$line->criteria) continue;
                            
                            $components[] = \Filament\Forms\Components\Radio::make('criterion_' . $line->criteria_id)
                                ->label($line->criteria->name)
                                ->helperText($line->criteria->description)
                                ->options([
                                    1 => '1' . ($line->criteria->score_1_desc ? ' - ' . $line->criteria->score_1_desc : ''),
                                    2 => '2' . ($line->criteria->score_2_desc ? ' - ' . $line->criteria->score_2_desc : ''),
                                    3 => '3' . ($line->criteria->score_3_desc ? ' - ' . $line->criteria->score_3_desc : ''),
                                    4 => '4' . ($line->criteria->score_4_desc ? ' - ' . $line->criteria->score_4_desc : ''),
                                    5 => '5' . ($line->criteria->score_5_desc ? ' - ' . $line->criteria->score_5_desc : ''),
                                ])
                                ->required();
                        }
                        
                        $components[] = \Filament\Forms\Components\Textarea::make('overall_comments')
                            ->label('Overall Comments & Feedback')
                            ->required();
                            
                        return $components;
                    })
                    ->action(function (array $data, $record, $livewire) {
                        $schedule = $livewire->getOwnerRecord();
                        $templateId = $schedule->evaluation_template_id;
                        
                        $lines = \App\Models\Recruitment\RecruitmentEvaluationFormTemplateLine::where('template_id', $templateId)->get();
                        
                        $totalScore = 0;
                        $count = 0;
                        
                        foreach ($lines as $line) {
                            $key = 'criterion_' . $line->criteria_id;
                            if (isset($data[$key])) {
                                $totalScore += (int) $data[$key];
                                $count++;
                            }
                        }
                        
                        $average = $count > 0 ? ($totalScore / $count) : 0;
                        
                        $evaluation = \App\Models\Recruitment\RecruitmentCandidateEvaluation::create([
                            'schedule_id' => $schedule->id,
                            'candidate_id' => $record->id,
                            'interviewer_id' => auth()->id(),
                            'template_id' => $templateId,
                            'overall_score' => $average,
                            'comments' => $data['overall_comments'],
                        ]);
                        
                        foreach ($lines as $line) {
                            $key = 'criterion_' . $line->criteria_id;
                            if (isset($data[$key])) {
                                \App\Models\Recruitment\RecruitmentEvaluationScore::create([
                                    'evaluation_id' => $evaluation->id,
                                    'criteria_id' => $line->criteria_id,
                                    'score' => (int) $data[$key],
                                ]);
                            }
                        }
                        
                        \Filament\Notifications\Notification::make()->title('Evaluation submitted completely!')->success()->send();
                    })
            ]);
    }
}
