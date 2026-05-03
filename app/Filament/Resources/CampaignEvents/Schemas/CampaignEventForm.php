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
                \Filament\Schemas\Components\Tabs::make()->tabs([
                    \Filament\Schemas\Components\Tabs\Tab::make('Event Basics')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
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
                                    ->default('fundraiser'),
                                Select::make('status')
                                    ->options([
                                        'planned' => 'Planned',
                                        'confirmed' => 'Confirmed',
                                        'ongoing' => 'Ongoing',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required()
                                    ->default('planned'),
                                Textarea::make('description')
                                    ->columnSpanFull()
                                    ->rows(3),
                            ]),
                        ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('Venue & Timing')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                                TextInput::make('venue')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('venue_address')
                                    ->required()
                                    ->columnSpanFull()
                                    ->rows(2),
                                DateTimePicker::make('event_date')
                                    ->required(),
                                DateTimePicker::make('end_date')
                                    ->after('event_date'),
                            ]),
                        ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('RSVPs & Capacity')
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                                Toggle::make('rsvp_required')
                                    ->required(),
                                DateTimePicker::make('rsvp_deadline')
                                    ->before('event_date'),
                                TextInput::make('expected_attendees')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0)
                                    ->helperText('How many attendees are expected?'),
                                TextInput::make('max_capacity')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->rules([
                                        fn (\Filament\Schemas\Components\Utilities\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $ticketsSold = (int) ($get('tickets_sold') ?? 0);
                                            if ((int) $value < $ticketsSold) {
                                                $fail("Maximum capacity cannot be less than the number of tickets already sold ({$ticketsSold}).");
                                            }
                                        },
                                    ])
                                    ->helperText('Maximum number of attendees allowed'),
                            ]),
                        ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('Financials & Volunteers')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()->columns(['default' => 2])->schema([
                                TextInput::make('ticket_price')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('ETB')
                                    ->minValue(0),
                                TextInput::make('budget')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('ETB')
                                    ->minValue(0),
                                TextInput::make('actual_cost')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('ETB')
                                    ->minValue(0),
                                TextInput::make('funds_raised')
                                    ->numeric()
                                    ->readOnly()
                                    ->default(0)
                                    ->prefix('ETB')
                                    ->helperText('Automatically calculated from attendee payments'),
                                Toggle::make('funds_to_campaign')
                                    ->required()
                                    ->default(true),
                                TextInput::make('volunteers_needed')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                TextInput::make('volunteers_registered')
                                    ->numeric()
                                    ->readOnly()
                                    ->default(0)
                                    ->helperText('Automatically calculated from volunteer registrations'),
                            ]),
                        ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('Organizer')
                        ->icon('heroicon-o-user')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                                Select::make('organizer_id')
                                    ->label('Select Internal Staff')
                                    ->relationship('organizer', 'first_name')
                                    ->searchable(['first_name', 'last_name', 'email'])
                                    ->preload()
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                                    ->live()
                                    ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                                        if ($state) {
                                            $employee = \App\Models\Employee::find($state);
                                            if ($employee) {
                                                $set('organizer_name', $employee->full_name);
                                                $set('organizer_email', $employee->email);
                                                $set('organizer_phone', $employee->phone);
                                            }
                                        }
                                    })
                                    ->helperText('Select a staff member from the system to automatically link this event.'),
                                TextInput::make('organizer_name')
                                    ->required()
                                    ->maxLength(100)
                                    ->helperText('Manual name if organizer is external.'),
                                TextInput::make('organizer_email')
                                    ->required()
                                    ->email()
                                    ->helperText('Valid email required for event communications.'),
                                TextInput::make('organizer_phone')
                                    ->tel()
                                    ->helperText('Contact number for logistics.'),
                                TextInput::make('registration_link')
                                    ->url()
                                    ->maxLength(500)
                                    ->placeholder('https://example.com/register')
                                    ->helperText('Valid URL required for external registrations.'),
                                TextInput::make('social_media_link')
                                    ->url()
                                    ->maxLength(500)
                                    ->placeholder('https://facebook.com/events/...')
                                    ->helperText('Valid URL required for event promotion.'),
                            ]),
                        ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('Notes')
                        ->icon('heroicon-o-pencil-square')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()->schema([
                                Textarea::make('notes')
                                    ->columnSpanFull()
                                    ->rows(6),
                            ]),
                        ]),
                ])->columnSpanFull(),
            ]);
    }
}
