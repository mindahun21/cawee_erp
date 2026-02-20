<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Donation Receipt #{{ $donation->id }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.5; }
        .container { padding: 40px; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .logo { font-size: 24px; font-bold: bold; color: #1a56db; }
        .receipt-title { font-size: 20px; margin-top: 10px; text-transform: uppercase; letter-spacing: 2px; }
        .details { margin-bottom: 30px; width: 100%; border-collapse: collapse; }
        .details td { padding: 8px 0; border-bottom: 1px solid #f9f9f9; }
        .label { font-weight: bold; width: 200px; color: #666; }
        .amount-section { margin-top: 40px; padding: 20px; background: #f8fafc; text-align: center; }
        .amount { font-size: 32px; font-weight: bold; color: #111827; }
        .footer { margin-top: 60px; font-size: 12px; color: #999; text-align: center; }
        .signature { margin-top: 40px; text-align: right; }
        .signature-line { border-top: 1px solid #333; width: 200px; display: inline-block; margin-top: 40px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">ELISOFT SOLUTION</div>
            <div class="receipt-title">Official Donation Receipt</div>
        </div>

        <table class="details">
            <tr>
                <td class="label">Receipt Number:</td>
                <td>#REC-{{ str_pad($donation->id, 6, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <td class="label">Date Issued:</td>
                <td>{{ now()->format('F j, Y') }}</td>
            </tr>
            <tr>
                <td class="label">Donation Date:</td>
                <td>{{ $donation->donation_date->format('F j, Y') }}</td>
            </tr>
            <tr>
                <td class="label">Donor:</td>
                <td>{{ $donation->donor->full_name }}</td>
            </tr>
            <tr>
                <td class="label">Campaign:</td>
                <td>{{ $donation->campaign->name ?? 'General Fund' }}</td>
            </tr>
            <tr>
                <td class="label">Method:</td>
                <td>{{ ucfirst($donation->payment_method) }}</td>
            </tr>
        </table>

        <div class="amount-section">
            <div>Total Donation Amount</div>
            <div class="amount">{{ $donation->currency->symbol ?? '$' }}{{ number_format($donation->amount, 2) }}</div>
            <div style="font-size: 14px; color: #666; margin-top: 5px;">{{ $donation->currency->name ?? 'US Dollar' }}</div>
        </div>

        <p style="margin-top: 40px;">
            Thank you for your generous contribution. Your support is instrumental in helping us achieve our goals and making a lasting impact in our community.
        </p>

        <div class="signature">
            <div class="signature-line"></div>
            <div>Authorized Signature</div>
        </div>

        <div class="footer">
            <p>This is an official receipt for your donation.</p>
            <p>ELISOFT SOLUTION &bull; 123 Business Way, Suite 100 &bull; contact@elisoft.com</p>
        </div>
    </div>
</body>
</html>
