<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Event Reminder</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #ff9800; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; background-color: #fff8e1; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .event-details { background-color: white; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ff9800; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Event Reminder</h1>
        </div>
        
        <div class="content">
            <h2>Hello {{ $attendee->name ?? $attendee->donor?->full_name ?? 'Friend' }},</h2>
            
            <p>This is a friendly reminder about the upcoming event:</p>
            
            <div class="event-details">
                <h3>{{ $event->event_name }}</h3>
                <p><strong>Date & Time:</strong> {{ $event->event_date->format('F j, Y g:i A') }}</p>
                @if($event->venue)
                    <p><strong>Location:</strong> {{ $event->venue }}</p>
                @endif
                <p><strong>Only {{ now()->diffInDays($event->event_date) }} day(s) to go!</strong></p>
            </div>
            
            @if($event->registration_link)
                <p><a href="{{ $event->registration_link }}">View Event Details</a></p>
            @endif
            
            <p>If you can no longer attend, please let us know.</p>
            
            <p>Looking forward to seeing you there!</p>
        </div>
        
        <div class="footer">
            <p>{{ config('app.name') }} Fundraising System</p>
        </div>
    </div>
</body>
</html>
