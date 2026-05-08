<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; color: #1a56db; }
        .footer { margin-top: 20px; font-size: 8px; text-align: right; color: #777; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $title }}</div>
        <div style="margin-top: 5px;">Inventory & Asset Management Report</div>
        <div style="font-size: 9px; margin-top: 3px;">Generated: {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Cawee ERP System - Confidential Report
    </div>
</body>
</html>
