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
    
    public function getTitle(): string
    {
        return 'Candidate Comparison Dashboard';
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
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
                                TextEntry::make('candidate.resume_path')
                                    ->label('Resume')
                                    ->formatStateUsing(fn ($state) => $state ? 'Download Resume' : 'Not attached')
                                    ->url(fn ($record) => $record->candidate?->resume_path ? \Illuminate\Support\Facades\Storage::url($record->candidate->resume_path) : null, true)
                                    ->color('primary'),
                            ]),
                    ]),

                Section::make('Interview Evaluations')
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('evaluations')
                            ->view('filament.resources.recruitment.applications.evaluations-summary')
                    ]),
            ]);
    }
}
