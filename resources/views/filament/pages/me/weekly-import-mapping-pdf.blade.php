<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Periodic Import Mapping Guide</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 12px 0; }
        h2 { font-size: 14px; margin: 18px 0 8px 0; }
        p { margin: 0 0 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; vertical-align: top; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Periodic Import Mapping Guide</h1>
    <p>This guide contains accepted columns, mapping behavior, validation rules, and common fixes for periodic import.</p>
    <p><strong>Use fixed columns only.</strong> Do not create custom columns such as "Women Amount Planned" or "Women Amount Actual".</p>
    <p><strong>One row = one target record</strong> for the combination: project + indicator + target + period.</p>

    <h2>Quick Example Row</h2>
    <table>
        <thead>
            <tr>
                <th>project_code</th>
                <th>indicator_name</th>
                <th>target_name</th>
                <th>planned_value</th>
                <th>actual_value</th>
                <th>report_description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>SVOET-003</td>
                <td>Weekly Success</td>
                <td>Women Amount</td>
                <td>80</td>
                <td>70</td>
                <td>Monthly progress note</td>
            </tr>
            <tr>
                <td>SVOET-003</td>
                <td>Weekly Success</td>
                <td>Disbursed Amount</td>
                <td>50000</td>
                <td></td>
                <td>Target imported; report will be added later.</td>
            </tr>
        </tbody>
    </table>

    <h2>What Your File Should Contain</h2>
    <table>
        <thead>
            <tr>
                <th>Area</th>
                <th>File field</th>
                <th>Required</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($columns as $column)
                <tr>
                    <td>{{ $column['area'] ?? '-' }}</td>
                    <td>{{ $column['column'] }}</td>
                    <td>{{ $column['required'] }}</td>
                    <td>{{ $column['description'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Where Each Info Goes</h2>
    <table>
        <thead>
            <tr>
                <th>Accepted column(s)</th>
                <th>Import area</th>
                <th>How it is used</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($mappings as $mapping)
                <tr>
                    <td>{{ $mapping['accepted_columns'] }}</td>
                    <td>{{ $mapping['import_area'] }}</td>
                    <td>{{ $mapping['how_used'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Validation Rules</h2>
    <table>
        <thead>
            <tr>
                <th>Rule</th>
                <th>If rule fails</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($validationRules as $rule)
                <tr>
                    <td>{{ $rule['rule'] }}</td>
                    <td>{{ $rule['impact'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Common Issues And Fixes</h2>
    <table>
        <thead>
            <tr>
                <th>Issue</th>
                <th>What to do</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($commonIssues as $item)
                <tr>
                    <td>{{ $item['issue'] }}</td>
                    <td>{{ $item['fix'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
