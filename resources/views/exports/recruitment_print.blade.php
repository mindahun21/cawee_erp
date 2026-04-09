<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Print Export' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 20px;
            font-size: 12px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: normal;
        }
        .header .date {
            font-size: 12px;
            color: #555;
        }
        .filters {
            margin-bottom: 20px;
            font-size: 12px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border-bottom: 1px solid #ccc;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            font-weight: bold;
            font-size: 12px;
            color: #000;
        }
        
        @media print {
            body { margin: 0; }
            @page { margin: 1cm; size: landscape; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <div class="date">{{ now()->format('n/j/y, g:i A') }}</div>
        <h2>{{ $title ?? 'Export Data' }}</h2>
        <div></div> <!-- spacer -->
    </div>

    @if(!empty($filterStr) && $filterStr !== 'None')
    <div class="filters">
        <strong>Filters Applied:</strong> {{ $filterStr }}
    </div>
    @endif

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
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
