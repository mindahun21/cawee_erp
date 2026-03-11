<?php

namespace App\Filament\Resources\RecruitmentInterviews\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class RecruitmentInterviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([

                    TextInput::make('schedule_name')
                        ->label('Interview Schedule Name')
                        ->required(),

                    Select::make('recruitment_campaign_id')
                        ->relationship('recruitmentCampaign', 'campaign_name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('recruitment_position_id')
                        ->label('Position')
                        ->relationship('recruitmentPosition', 'job_position')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('interviewer_id')
                        ->label('Interviewer')
                        ->options(User::pluck('name', 'id'))
                        ->searchable()
                        ->required(),

                    DatePicker::make('interview_date')
                        ->required(),

                    TimePicker::make('from_hour')
                        ->label('From Time')
                        ->required(),

                    TimePicker::make('to_hour')
                        ->label('To Time')
                        ->required(),

                    TextInput::make('location')
                        ->label('Interview Location'),
                ]),

                Repeater::make('candidates')
                    ->relationship()
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('candidate_id')
                                ->label('Candidate')
                                ->options(\App\Models\RecruitmentCandidate::pluck('first_name', 'id'))
                                ->searchable()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, $set) {
                                    $candidate = \App\Models\RecruitmentCandidate::find($state);
                                    if ($candidate) {
                                        $set('email', $candidate->email);
                                        $set('phone_number', $candidate->phone_number);
                                    }
                                }),
                            TimePicker::make('from_hour')->label('From')->required(),
                            TimePicker::make('to_hour')->label('To')->required(),

                        ]),

                    ])
                    ->columns(1)
                    ->createItemButtonLabel('Add Candidate')
                    ->collapsible(),
            ]);
    }
}
