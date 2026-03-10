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

                    TextInput::make('interview_schedule_name')
                        ->label('Interview Schedule Name')
                        ->required(),

                    Select::make('recruitment_campaign_id')
                        ->relationship('recruitmentCampaign', 'campaign_name')
                        ->required()
                        ->searchable()
                        ->preload(),

                    TextInput::make('position'),

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

                    TextInput::make('interview_location'),

                    Toggle::make('send_email_notification')
                        ->label('Send email notifications to the contact')
                        ->inline(false),

                ]),

                Repeater::make('candidates')
                    ->relationship()
                    ->schema([

                        Grid::make(3)->schema([

                            Select::make('candidate_id')
                                ->relationship('candidates', 'name')
                                ->searchable()
                                ->required(),

                            TextInput::make('email')
                                ->email(),

                            TextInput::make('phone_number'),

                            TimePicker::make('from_hour')
                                ->label('From'),

                            TimePicker::make('to_hour')
                                ->label('To'),

                        ]),

                    ])
                    ->columns(1)
                    ->createItemButtonLabel('Add Candidate')
                    ->collapsible(),
            ]);
    }
}
