<?php

namespace App\Filament\Resources\CampaignEvents\CampaignEventResource\Widgets;

use App\Models\CampaignEvent;
use App\Models\EventAttendee;
use App\Models\EventVolunteer;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EventStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Upcoming Events', CampaignEvent::where('event_date', '>', now())->count())
                ->description('Events scheduled for the future')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
            Stat::make('Total Attendees', EventAttendee::count())
                ->description('Total registered attendees across all events')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make('Total Volunteers', EventVolunteer::count())
                ->description('Total volunteers registered')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
            Stat::make('Total Funds Raised', '$' . number_format(CampaignEvent::sum('funds_raised'), 2))
                ->description('Funds raised through events')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
        ];
    }
}
