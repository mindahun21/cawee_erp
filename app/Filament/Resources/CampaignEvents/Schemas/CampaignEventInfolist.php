<?php

namespace App\Filament\Resources\CampaignEvents\Schemas;

use App\Models\CampaignEvent;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CampaignEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('campaign.title')
                    ->label('Campaign'),
                TextEntry::make('event_name'),
                TextEntry::make('event_type'),
                TextEntry::make('status'),
                TextEntry::make('event_date')
                    ->dateTime(),
                TextEntry::make('end_date')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('venue')
                    ->placeholder('-'),
                TextEntry::make('venue_address')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('expected_attendees')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('max_capacity')
                    ->numeric()
                    ->placeholder('-'),
                IconEntry::make('rsvp_required')
                    ->boolean(),
                TextEntry::make('rsvp_deadline')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('ticket_price')
                    ->money(),
                TextEntry::make('tickets_sold')
                    ->numeric(),
                TextEntry::make('budget')
                    ->numeric(),
                TextEntry::make('actual_cost')
                    ->money(),
                TextEntry::make('funds_raised')
                    ->numeric(),
                IconEntry::make('funds_to_campaign')
                    ->boolean(),
                TextEntry::make('organizer_name')
                    ->placeholder('-'),
                TextEntry::make('organizer_email')
                    ->placeholder('-'),
                TextEntry::make('organizer_phone')
                    ->placeholder('-'),
                TextEntry::make('registration_link')
                    ->placeholder('-'),
                TextEntry::make('social_media_link')
                    ->placeholder('-'),
                TextEntry::make('volunteers_needed')
                    ->numeric(),
                TextEntry::make('volunteers_registered')
                    ->numeric(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (CampaignEvent $record): bool => $record->trashed()),
            ]);
    }
}
