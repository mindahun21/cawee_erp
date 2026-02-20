<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Donation Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #28a745; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; background-color: #f9f9f9; }
        .receipt { background-color: white; padding: 25px; border-radius: 5px; margin: 20px 0; border: 2px solid #ddd; }
        .amount { font-size: 24px; color: #28a745; font-weight: bold; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Thank You for Your Donation!</h1>
        </div>
        
        <div class="content">
            <h2>Dear {{ $donor->full_name }},</h2>
            
            <p>Thank you for your generous donation to our fundraising campaign. Your support is greatly appreciated and will make a significant difference.</p>
            
            <div class="receipt">
                <h3>Donation Receipt</h3>
                <p><strong>Donation ID:</strong> #{{ $donation->id }}</p>
                <p><strong>Date:</strong> {{ $donation->created_at->format('F j, Y') }}</p>
                <p><strong>Amount:</strong> <span class="amount">${{ number_format($donation->amount, 2) }}</span></p>
                <p><strong>Payment Method:</strong> {{ $donation->payment_method ?? 'Online' }}</p>
                @if($donation->transaction_id)
                <p><strong>Transaction ID:</strong> {{ $donation->transaction_id }}</p>
                @endif
            </div>
            
            <p>Your donation is tax-deductible to the extent allowed by law. Please keep this receipt for your records.</p>
            
            <p>If you have any questions about your donation, please don't hesitate to contact us.</p>
            
            <p>With gratitude,<br>
            The Fundraising Team</p>
        </div>
        
        <div class="footer">
            <p>This is an official receipt for your records.</p>
            <p>{{ config('app.url') }}</p>
        </div>
    </div>
</body>
</html>
