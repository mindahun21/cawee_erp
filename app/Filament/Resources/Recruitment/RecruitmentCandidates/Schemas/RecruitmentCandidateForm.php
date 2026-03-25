<?php

namespace App\Filament\Resources\Recruitment\RecruitmentCandidates\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class RecruitmentCandidateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Candidate Details')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Basic Info')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('first_name')->required(),
                                    TextInput::make('last_name')->required(),
                                    TextInput::make('email')->email()->required(),
                                    TextInput::make('phone'),
                                    DatePicker::make('birthday'),
                                    Select::make('gender')
                                        ->options([
                                            'male'   => 'Male',
                                            'female' => 'Female',
                                        ]),
                                    Select::make('marital_status')
                                        ->options([
                                            'single'   => 'Single',
                                            'married'  => 'Married',
                                            'divorced' => 'Divorced',
                                        ]),
                                ]),
                            ]),

                        Tab::make('Contact & Address')
                            ->schema([
                                Textarea::make('resident')->label('Resident Address')->columnSpanFull(),
                                Textarea::make('current_accommodation')->label('Current Accommodation')->columnSpanFull(),
                                Grid::make(2)->schema([
                                    TextInput::make('alternate_phone')->label('Alternate Phone'),
                                    TextInput::make('skype'),
                                    TextInput::make('facebook'),
                                ]),
                            ]),

                        Tab::make('Professional Details')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('seniority')
                                        ->label('Seniority Level')
                                        ->options([
                                            'junior'     => 'Junior',
                                            'mid'        => 'Mid-Level',
                                            'senior'     => 'Senior',
                                            'lead'       => 'Lead',
                                            'manager'    => 'Manager',
                                        ]),
                                    TextInput::make('linkedin_url')->label('LinkedIn URL')->url(),
                                ]),
                                Textarea::make('interests')->columnSpanFull(),
                                FileUpload::make('resume_path')
                                    ->directory('recruitment/resumes')
                                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']),
                            ]),

                        Tab::make('Seniority / Experience')
                            ->schema([
                                Repeater::make('seniorities')
                                    ->relationship()
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('company')->required(),
                                            TextInput::make('position')->required(),
                                            DatePicker::make('from_date'),
                                            DatePicker::make('to_date')
                                                ->label('To Date (Leave blank if current)')
                                                ->afterOrEqual('from_date'),
                                            TextInput::make('salary')
                                                ->numeric()
                                                ->minValue(0),
                                            TextInput::make('contact_person'),
                                            Textarea::make('job_description')->columnSpanFull(),
                                            Textarea::make('reason_for_leaving')->columnSpanFull(),
                                        ]),
                                    ])
                                    ->itemLabel(fn (array $state): ?string => $state['company'] ?? null)
                                    ->collapsible()
                                    ->defaultItems(0),
                            ]),

                        Tab::make('Literacy / Education')
                            ->schema([
                                Repeater::make('literacies')
                                    ->relationship()
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('diploma')->required(),
                                            TextInput::make('training_places')->required(),
                                            TextInput::make('specialized'),
                                            DatePicker::make('from_date'),
                                            DatePicker::make('to_date')
                                                ->afterOrEqual('from_date'),
                                            TextInput::make('percentage')
                                                ->numeric()
                                                ->minValue(0),
                                        ]),
                                    ])
                                    ->itemLabel(fn (array $state): ?string => $state['diploma'] ?? null)
                                    ->collapsible()
                                    ->defaultItems(0),
                            ]),

                        Tab::make('References')
                            ->schema([
                                Repeater::make('references')
                                    ->relationship()
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('name')->required(),
                                            TextInput::make('phone'),
                                            TextInput::make('relationship'),
                                            TextInput::make('job'),
                                            Textarea::make('address')->columnSpanFull(),
                                        ]),
                                    ])
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                    ->collapsible()
                                    ->defaultItems(0),
                            ]),

                        Tab::make('Portal Access')
                            ->schema([
                                Grid::make(2)->schema([
                                    \Filament\Forms\Components\Toggle::make('portal_access')
                                        ->label('Portal Access Granted')
                                        ->helperText('Allow this candidate to log in to the candidate portal.'),

                                    TextInput::make('password')
                                        ->password()
                                        ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state))
                                        ->dehydrated(fn ($state) => filled($state))
                                        ->label('Portal Password')
                                        ->revealable()
                                        ->helperText('Leave blank to keep current. Provide a temporary password if manually registering a candidate.'),
                                ]),
                            ]),
                    ]),
            ]);
    }
}
