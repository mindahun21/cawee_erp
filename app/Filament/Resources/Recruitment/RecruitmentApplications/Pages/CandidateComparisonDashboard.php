<?php

namespace App\Filament\Resources\Recruitment\RecruitmentApplications\Pages;

use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Recruitment\RecruitmentApplications\RecruitmentApplicationResource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;

class CandidateComparisonDashboard extends ViewRecord
{
    protected static string $resource = RecruitmentApplicationResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('hire_candidate')
                ->label('Convert to Employee')
                ->icon('heroicon-o-user-group')
                ->color('success')
                ->visible(fn () => $this->record->status === \App\Models\Recruitment\RecruitmentApplication::STATUS_OFFER_ACCEPTED)
                ->form(function () {
                    return [
                        \Filament\Forms\Components\TextInput::make('employee_name')
                            ->label('Candidate Name')
                            ->default($this->record->candidate?->full_name)
                            ->disabled()
                            ->dehydrated(false),
                        \Filament\Forms\Components\Select::make('department_id')
                            ->label('Department')
                            ->options(\App\Models\Department::pluck('name', 'id'))
                            ->default($this->record->campaign?->jobPosition?->department_id)
                            ->disabled()
                            ->dehydrated(),
                        \Filament\Forms\Components\Select::make('job_position_id')
                            ->label('Job Position')
                            ->options(\App\Models\JobPosition::pluck('title', 'id'))
                            ->default($this->record->campaign?->job_position_id)
                            ->disabled()
                            ->dehydrated(),
                        \Filament\Forms\Components\Select::make('gender')
                            ->label('Gender')
                            ->options(['M' => 'Male', 'F' => 'Female'])
                            ->default($this->record->candidate?->gender ? strtoupper(substr($this->record->candidate->gender, 0, 1)) : null)
                            ->required()
                            ->dehydrated(),
                        \Filament\Forms\Components\Select::make('employment_type')
                            ->label('Employment Type')
                            ->options([
                                'Contract'    => 'Contract',
                                'Temporary'   => 'Temporary',
                                'Consultancy' => 'Consultancy',
                                'Other'       => 'Other',
                            ])
                            ->default(match ($this->record->campaign?->employment_type) {
                                'contract' => 'Contract',
                                'internship' => 'Temporary',
                                'full_time' => 'Contract',
                                'part_time' => 'Contract',
                                default => 'Other',
                            })
                            ->disabled()
                            ->dehydrated(),
                        \Filament\Forms\Components\DatePicker::make('date_of_employment')
                            ->label('Start Date')
                            ->default($this->record->offer?->offer_date ?? now())
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('basic_salary')
                            ->label('Basic Salary')
                            ->numeric()
                            ->prefix('ETB')
                            ->default($this->record->offer?->offered_salary)
                            ->disabled()
                            ->dehydrated(),
                    ];
                })
                ->modalHeading('Hire Candidate & Create Employee Record')
                ->modalDescription('This will transfer the candidate\'s locked profile and legal offer data over to the native HR Module, successfully concluding the Recruitment pipeline.')
                ->action(function (array $data) {
                    $candidate = $this->record->candidate;
                    
                    $employee = \App\Models\Employee::create([
                        'user_id' => $candidate->user_id,
                        'first_name' => $candidate->first_name,
                        'last_name' => $candidate->last_name,
                        'gender' => $data['gender'],
                        'date_of_birth' => $candidate->birthday,
                        'national_id' => $candidate->identification,
                        'phone_number' => $candidate->phone,
                        'email' => $candidate->email,
                        'department_id' => $data['department_id'],
                        'job_position_id' => $data['job_position_id'],
                        'grade_id' => $this->record->campaign?->jobPosition?->grade_id,
                        'basic_salary' => $data['basic_salary'] ?? 0,
                        'employment_type' => $data['employment_type'],
                        'date_of_employment' => $data['date_of_employment'],
                    ]);

                    $this->record->update(['status' => \App\Models\Recruitment\RecruitmentApplication::STATUS_HIRED]);

                    \Filament\Notifications\Notification::make()
                        ->title('Candidate successfully hired! Redirecting to HR Profile...')
                        ->success()
                        ->send();

                    $this->redirect(\App\Filament\Resources\HR\Employees\EmployeeResource::getUrl('edit', ['record' => $employee->id]));
                }),
        ];
    }
    
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Job & Campaign Context')
                            ->columnSpan(['md' => 1])
                            ->schema([
                                TextEntry::make('campaign.title')->label('Campaign')->weight('bold'),
                                TextEntry::make('campaign.jobPosition.title')->label('Position'),
                                TextEntry::make('campaign.salary_min')
                                    ->label('Salary Range')
                                    ->formatStateUsing(fn ($record) => number_format($record->campaign->salary_min) . ' - ' . number_format($record->campaign->salary_max) . ' ' . $record->campaign->currency),
                                TextEntry::make('campaign.candidate_seniority')->label('Req. Seniority'),
                            ]),

                        Section::make('Candidate Profile')
                            ->columnSpan(['md' => 1])
                            ->schema([
                                TextEntry::make('candidate.full_name')->label('Name')->weight('bold'),
                                TextEntry::make('candidate.email')->label('Email')->icon('heroicon-o-envelope'),
                                TextEntry::make('candidate.phone')->label('Phone')->icon('heroicon-o-phone'),
                                TextEntry::make('candidate.seniority')->label('Act. Seniority'),
                            ]),

                        Section::make('Cover Letter & Assets')
                            ->columnSpan(['md' => 1])
                            ->schema([
                                TextEntry::make('cover_letter')
                                    ->label('Cover Letter')
                                    ->default('—')
                                    ->html(),
                                TextEntry::make('candidate.resume_path')
                                    ->label('Resume Attachment')
                                    ->formatStateUsing(fn ($state) => $state ? 'Download Resume' : 'Not attached')
                                    ->action(
                                        \Filament\Actions\Action::make('download_dashboard_resume')
                                            ->action(function ($record) {
                                                $path = $record->candidate?->resume_path;
                                                if (!$path) return;
                                                $disk = \Illuminate\Support\Facades\Storage::disk('private')->exists($path) ? 'private' : 'public';
                                                return response()->download(\Illuminate\Support\Facades\Storage::disk($disk)->path($path));
                                            })
                                    )
                                    ->color('primary')
                                    ->icon('heroicon-o-document-arrow-down'),
                            ]),
                    ]),
                    
                ViewEntry::make('comparison_matrix')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->view('filament.resources.recruitment.applications.candidate-comparison'),

                Section::make('Interview Evaluations')
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('evaluations')
                            ->view('filament.resources.recruitment.applications.evaluations-summary')
                    ]),
            ]);
    }
}
