<?php

namespace App\Filament\Resources\RecruitmentChannels\Schemas;

use App\Models\Role;
use App\Models\User;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RecruitmentChannelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('form_name')
                    ->required(),

                Select::make('form_type')
                    ->options([
                        'job_application' => 'Job Application',
                        'contact_form' => 'Contact Form',
                    ])
                    ->required(),
                Select::make('language')
                    ->label('Language')
                    ->options([
                        'en' => 'English',
                        'am' => 'Amharic',
                        'ar' => 'Arabic',
                        'fr' => 'French',
                        'de' => 'German',
                        'es' => 'Spanish',
                        'zh' => 'Chinese',
                        'ja' => 'Japanese',
                        'ru' => 'Russian',
                        'hi' => 'Hindi',
                        'pt' => 'Portuguese',
                        'it' => 'Italian',
                        'ko' => 'Korean',
                        'tr' => 'Turkish',
                        'sv' => 'Swedish',
                        'nl' => 'Dutch',
                        'pl' => 'Polish',
                        'id' => 'Indonesian',
                        'vi' => 'Vietnamese',
                        'th' => 'Thai',
                    ])
                    ->required()
                    ->searchable(),

                TextInput::make('submit_button')
                    ->default('Submit'),

                Textarea::make('message')
                    ->label('Success Message')
                    ->required(),

                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->required(),

                Toggle::make('notify')
                    ->label('Notify Staff'),
                Radio::make('assignment_type')
                    ->label('Assignment Type')
                    ->options([
                        'staff' => 'Specific Staff Member',
                        'role' => 'Staff Members with Roles',
                        'responsible' => 'Responsible Person',
                    ])
                    ->default('staff')
                    ->reactive()
                    ->required(),

                Select::make('staff_member_id')
                    ->label('Specific Staff Member')
                    ->options(User::pluck('name', 'id'))
                    ->searchable()
                    ->visible(fn($get) => $get('assignment_type') === 'staff'),

                // Select::make('role_id')
                //     ->label('Staff Members with Roles')
                //     ->options(Role::pluck('name', 'id'))
                //     ->searchable()
                //     ->visible(fn($get) => $get('assignment_type') === 'role'),

                Select::make('responsible_person_id')
                    ->label('Responsible Person')
                    ->options(User::pluck('name', 'id'))
                    ->searchable()
                    ->visible(fn($get) => $get('assignment_type') === 'responsible'),
            ]);
        Builder::make('form_fields')
            ->label('Form Builder')
            ->blocks([
                Block::make('header')
                    ->schema([
                        TextInput::make('text')->label('Header Text'),
                    ]),

                Block::make('first_name')
                    ->schema([
                        Toggle::make('required')
                    ]),

                Block::make('last_name')
                    ->schema([
                        Toggle::make('required')
                    ]),

                Block::make('birthday')
                    ->schema([
                        Toggle::make('required')
                    ]),

                Block::make('gender')
                    ->schema([
                        Select::make('options')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ])
                    ]),

                Block::make('text')
                    ->schema([
                        TextInput::make('label'),
                        Toggle::make('required'),
                    ]),
            ])

            ->columnSpanFull();
    }
}
