<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Report — {{ ucwords(str_replace('_', ' ', $report)) }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; padding: 20px; }
        .header { background: #1a56db; color: white; padding: 16px 20px; border-radius: 8px; margin-bottom: 20px; }
        .header h1 { font-size: 16px; font-weight: bold; }
        .header p { font-size: 10px; opacity: 0.8; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #1a56db; color: white; }
        thead th { padding: 8px 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; }
        .badge { padding: 2px 8px; border-radius: 12px; font-size: 8px; font-weight: bold; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-purple { background: #ede9fe; color: #5b21b6; }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .footer { margin-top: 20px; text-align: right; font-size: 8px; color: #6b7280; }
        .mono { font-family: 'Courier New', monospace; }
    </style>
</head>
<body>
    <div class="header">
        <h1>HR Report — {{ ucwords(str_replace('_', ' ', $report)) }}</h1>
        <p>Generated on {{ now()->format('d F Y, H:i') }} &nbsp;|&nbsp; Total records: {{ $data->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @if($report === 'staff_list')
                    <th>Full Name</th><th>Department</th><th>Position</th><th>Contract</th><th>Start Date</th><th>Salary (ETB)</th><th>Status</th>
                @elseif($report === 'layoffs')
                    <th>Full Name</th><th>Department</th><th>Position</th><th>Resigned</th><th>Years</th>
                @elseif($report === 'salary_changes')
                    <th>Full Name</th><th>Department</th><th>Position</th><th>Salary (ETB)</th><th>Grade</th>
                @elseif($report === 'qualifications')
                    <th>Full Name</th><th>Department</th><th>Education Level</th><th>Field of Study</th>
                @elseif($report === 'seniority')
                    <th>Full Name</th><th>Department</th><th>Position</th><th>Start Date</th><th>Years</th><th>Band</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($data as $e)
            <tr>
                @if($report === 'staff_list')
                    <td><b>{{ $e->full_name }}</b></td>
                    <td><span class="badge badge-blue">{{ $e->department?->name ?? '–' }}</span></td>
                    <td>{{ $e->jobPosition?->title ?? ($e->position ?? '–') }}</td>
                    <td>{{ $e->contractType?->name ?? ($e->employment_type ?? '–') }}</td>
                    <td>{{ $e->date_of_employment?->format('d/m/Y') ?? '–' }}</td>
                    <td class="mono" style="text-align:right">{{ number_format((float)$e->basic_salary, 2) }}</td>
                    <td><span class="badge {{ $e->date_resigned ? 'badge-red' : 'badge-green' }}">{{ $e->date_resigned ? 'Resigned' : 'Active' }}</span></td>
                @elseif($report === 'layoffs')
                    <td><b>{{ $e->full_name }}</b></td>
                    <td>{{ $e->department?->name ?? '–' }}</td>
                    <td>{{ $e->jobPosition?->title ?? ($e->position ?? '–') }}</td>
                    <td>{{ $e->date_resigned?->format('d/m/Y') ?? '–' }}</td>
                    <td style="text-align:center"><b>{{ $e->date_of_employment ? (int) $e->date_of_employment->diffInYears($e->date_resigned ?? now()) : '–' }}</b></td>
                @elseif($report === 'salary_changes')
                    <td><b>{{ $e->full_name }}</b></td>
                    <td>{{ $e->department?->name ?? '–' }}</td>
                    <td>{{ $e->jobPosition?->title ?? ($e->position ?? '–') }}</td>
                    <td class="mono" style="text-align:right; color:#065f46; font-weight:bold">{{ number_format((float)$e->basic_salary, 2) }}</td>
                    <td>{{ $e->salaryGrade ? "G{$e->salaryGrade->grade} S{$e->salaryGrade->step}" : '–' }}</td>
                @elseif($report === 'qualifications')
                    <td><b>{{ $e->full_name }}</b></td>
                    <td>{{ $e->department?->name ?? '–' }}</td>
                    <td><span class="badge badge-purple">{{ $e->educationLevel?->name ?? ($e->education_level ?? '–') }}</span></td>
                    <td>{{ $e->fieldOfStudy?->name ?? ($e->field_of_study ?? '–') }}</td>
                @elseif($report === 'seniority')
                    <td><b>{{ $e->full_name }}</b></td>
                    <td>{{ $e->department?->name ?? '–' }}</td>
                    <td>{{ $e->jobPosition?->title ?? ($e->position ?? '–') }}</td>
                    <td>{{ $e->date_of_employment?->format('d/m/Y') ?? '–' }}</td>
                    <td style="text-align:center; font-weight:bold; color:#1a56db">{{ $e->years ?? 0 }}</td>
                    <td><span class="badge badge-amber">{{ $e->band ?? '–' }}</span></td>
                @endif
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center; padding:20px; color:#6b7280">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Stand for Vulnerable Organization — HR Records Management &nbsp;|&nbsp; Confidential</div>
</body>
</html>
