<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Receipt</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #3b82f6;
            margin: 0;
            font-size: 28px;
        }
        .receipt-number {
            background-color: #f0f9ff;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 30px;
            border-left: 4px solid #3b82f6;
        }
        .receipt-number strong {
            color: #3b82f6;
            font-size: 18px;
        }
        .details {
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #6b7280;
        }
        .detail-value {
            color: #111827;
            font-weight: 500;
        }
        .amount-highlight {
            background-color: #dcfce7;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 30px 0;
            border: 2px solid #22c55e;
        }
        .amount-highlight .label {
            color: #15803d;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .amount-highlight .amount {
            color: #15803d;
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }
        .tax-notice {
            background-color: #fef3c7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #f59e0b;
        }
        .tax-notice p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .thank-you {
            text-align: center;
            margin: 30px 0;
            font-size: 18px;
            color: #3b82f6;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎁 Donation Receipt</h1>
            <p style="margin: 10px 0 0 0; color: #6b7280;">Thank you for your generous contribution!</p>
        </div>

        <div class="receipt-number">
            <strong>Receipt Number: {{ $receiptNumber }}</strong>
        </div>

        <div class="amount-highlight">
            <div class="label">Donation Amount</div>
            <div class="amount">{{ $currency }}{{ number_format($amount, 2) }}</div>
        </div>

        <div class="details">
            <div class="detail-row">
                <span class="detail-label">Donor Name:</span>
                <span class="detail-value">{{ $donorName }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Donation Type:</span>
                <span class="detail-value">{{ $donationType }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Campaign:</span>
                <span class="detail-value">{{ $campaign }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Donation Date:</span>
                <span class="detail-value">{{ date('F d, Y', strtotime($donationDate)) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Receipt Issued:</span>
                <span class="detail-value">{{ date('F d, Y H:i', strtotime($dateIssued)) }}</span>
            </div>
        </div>

        @if($isTaxDeductible)
        <div class="tax-notice">
            <p><strong>Tax Deductible:</strong> This donation is tax-deductible to the extent allowed by law. Please consult with your tax advisor for specific guidance. Keep this receipt for your tax records.</p>
        </div>
        @endif

        <div class="thank-you">
            Thank you for making a difference! 💙
        </div>

        <div class="footer">
            <p>This is an official donation receipt. Please retain this for your records.</p>
            <p style="margin-top: 10px;">If you have any questions, please contact us.</p>
        </div>
    </div>
</body>
</html>
