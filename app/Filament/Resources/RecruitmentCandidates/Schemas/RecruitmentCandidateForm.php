<?php

namespace App\Filament\Resources\RecruitmentCandidates\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section as ComponentsSection;

class RecruitmentCandidateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                ComponentsSection::make('Basic Information')
                    ->schema([
                        TextInput::make('candidate_code')->required(),

                        TextInput::make('first_name')->required(),
                        TextInput::make('last_name'),

                        DatePicker::make('birthday'),

                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ]),

                        TextInput::make('desired_salary'),
                    ])->columns(3),

                ComponentsSection::make('Contact')
                    ->schema([
                        TextInput::make('contact_phone'),
                        TextInput::make('contact_email')->email()->required(),
                        TextInput::make('contact_linkedin'),
                    ])->columns(3),

                ComponentsSection::make('Uploads')
                    ->schema([
                        FileUpload::make('profile_picture')
                            ->image()
                            ->directory('candidates/profile'),

                        FileUpload::make('cv')
                            ->label('CV')
                            ->directory('candidates/cv')
                            ->acceptedFileTypes(['application/pdf'])
                            ->nullable(),
                    ])->columns(2),

                ComponentsSection::make('Experience')
                    ->schema([
                        Repeater::make('seniorities')
                            ->relationship()
                            ->schema([
                                TextInput::make('company'),
                                TextInput::make('position'),
                                DatePicker::make('from_date'),
                                DatePicker::make('to_date'),
                                TextInput::make('salary'),
                                Textarea::make('job_description')->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->addActionLabel('Add Experience'),
                    ]),

                ComponentsSection::make('Education')
                    ->schema([
                        Repeater::make('educations')
                            ->relationship()
                            ->schema([
                                TextInput::make('diploma'),
                                TextInput::make('specialized'),
                                DatePicker::make('from_date'),
                                DatePicker::make('to_date'),
                            ])
                            ->collapsible()
                            ->addActionLabel('Add Education'),
                    ]),

                ComponentsSection::make('References')
                    ->schema([
                        Repeater::make('references')
                            ->relationship()
                            ->schema([
                                TextInput::make('name'),
                                TextInput::make('relationship'),
                                TextInput::make('phone'),
                            ])
                            ->collapsible()
                            ->addActionLabel('Add Reference'),
                    ]),
            ]);
    }
}
