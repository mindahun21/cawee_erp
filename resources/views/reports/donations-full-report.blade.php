<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Donations Report</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .report-title { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        .table th { background-color: #f3f4f6; font-weight: bold; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #999; }
        .total-row { font-weight: bold; background-color: #f9fafb; }
    </style>
</head>
<body>
    <div class="header">
        <div class="report-title">{{ $title }}</div>
        <div>Generated on {{ $date }}</div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Donor</th>
                <th>Campaign</th>
                <th>Method</th>
                <th style="text-align: right;">Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($donations as $donation)
            <tr>
                <td>{{ $donation->donation_date->format('Y-m-d') }}</td>
                <td>{{ $donation->donor->full_name }}</td>
                <td>{{ $donation->campaign->name ?? 'N/A' }}</td>
                <td>{{ ucfirst($donation->payment_method) }}</td>
                <td style="text-align: right;">{{ $donation->currency->symbol ?? '$' }}{{ number_format($donation->amount, 2) }}</td>
                <td>{{ ucfirst($donation->status) }}</td>
            </tr>
            @php $total += $donation->amount; @endphp
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" style="text-align: right;">GRAND TOTAL</td>
                <td style="text-align: right;">{{ number_format($total, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        &copy; {{ date('Y') }} ELISOFT ERP SOLUTION &bull; Page <span class="page-number"></span>
    </div>
</body>
</html>
