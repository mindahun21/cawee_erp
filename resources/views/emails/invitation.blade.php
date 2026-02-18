<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Event Invitation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4a6fa5; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; background-color: #f9f9f9; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .button { 
            display: inline-block; 
            background-color: #4a6fa5; 
            color: white; 
            padding: 12px 30px; 
            text-decoration: none; 
            border-radius: 5px; 
            margin: 20px 0; 
        }
        .event-details { background-color: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>You're Invited!</h1>
        </div>
        
        <div class="content">
            <h2>Hello {{ $attendee->name ?? $attendee->donor?->full_name ?? 'Friend' }},</h2>
            
            <p>You're invited to attend:</p>
            
            <div class="event-details">
                <h3>{{ $event->event_name }}</h3>
                <p><strong>Date & Time:</strong> {{ $event->event_date->format('F j, Y g:i A') }}</p>
                @if($event->venue)
                    <p><strong>Venue:</strong> {{ $event->venue }}</p>
                @endif
                @if($event->description)
                    <p>{{ $event->description }}</p>
                @endif
            </div>
            
            @if($event->registration_link)
                <div style="text-align: center;">
                    <a href="{{ $event->registration_link }}" class="button">RSVP Now</a>
                </div>
            @endif
            
            <p>We hope to see you there!</p>
            
            <p>Best regards,<br>
            The {{ $event->organizer_name ?? 'Fundraising' }} Team</p>
        </div>
        
        <div class="footer">
            <p>This email was sent from {{ config('app.url') }}</p>
            <p>If you wish to unsubscribe from these emails, please contact us.</p>
        </div>
    </div>
</body>
</html>
