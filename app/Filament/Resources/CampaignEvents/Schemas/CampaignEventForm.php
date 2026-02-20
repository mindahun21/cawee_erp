<?php

namespace App\Filament\Resources\CampaignEvents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CampaignEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Event Basics')
                    ->columns(2)
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Select::make('campaign_id')
                            ->relationship('campaign', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('event_name')
                            ->required()
                            ->maxLength(255),
                        Select::make('event_type')
                            ->options([
                                'fundraiser' => 'Fundraiser',
                                'meeting' => 'Meeting',
                                'volunteer' => 'Volunteer',
                                'awareness' => 'Awareness',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('fundraiser')
                            ->native(false),
                        Select::make('status')
                            ->options([
                                'planned' => 'Planned',
                                'confirmed' => 'Confirmed',
                                'ongoing' => 'Ongoing',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('planned')
                            ->native(false),
                        Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),

                \Filament\Schemas\Components\Section::make('Venue & Timing')
                    ->columns(2)
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextInput::make('venue')
                            ->maxLength(255),
                        Textarea::make('venue_address')
                            ->columnSpanFull()
                            ->rows(2),
                        DateTimePicker::make('event_date')
                            ->required()
                            ->native(false),
                        DateTimePicker::make('end_date')
                            ->native(false)
                            ->after('event_date'),
                        Toggle::make('rsvp_required')
                            ->required(),
                        DateTimePicker::make('rsvp_deadline')
                            ->native(false)
                            ->before('event_date'),
                    ]),

                \Filament\Schemas\Components\Section::make('Logistics & Financials')
                    ->columns(3)
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        TextInput::make('expected_attendees')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        TextInput::make('max_capacity')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('ticket_price')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0),
                        TextInput::make('budget')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0),
                        TextInput::make('actual_cost')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0),
                        TextInput::make('funds_raised')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0),
                        Toggle::make('funds_to_campaign')
                            ->required()
                            ->default(true),
                        TextInput::make('volunteers_needed')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        TextInput::make('volunteers_registered')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->readOnly(),
                    ]),

                \Filament\Schemas\Components\Section::make('Organizer & Contacts')
                    ->columns(3)
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('organizer_name')
                            ->maxLength(100),
                        TextInput::make('organizer_email')
                            ->email(),
                        TextInput::make('organizer_phone')
                            ->tel(),
                        TextInput::make('registration_link')
                            ->url()
                            ->maxLength(500),
                        TextInput::make('social_media_link')
                            ->url()
                            ->maxLength(500),
                    ]),

                \Filament\Schemas\Components\Section::make('Additional Notes')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Textarea::make('notes')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),
            ]);
    }
}
