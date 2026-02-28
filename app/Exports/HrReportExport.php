<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HrReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected string $reportType;
    protected array  $filters;

    public function __construct(string $reportType, array $filters = [])
    {
        $this->reportType = $reportType;
        $this->filters    = $filters;
    }

    public function collection(): Collection
    {
        return match ($this->reportType) {
            'staff_list'         => $this->staffList(),
            'layoffs'            => $this->layoffs(),
            'salary_changes'     => $this->salaryChanges(),
            'qualifications'     => $this->qualifications(),
            'seniority'          => $this->seniority(),
            default              => collect(),
        };
    }

    public function headings(): array
    {
        return match ($this->reportType) {
            'staff_list' => ['#', 'Full Name', 'Department', 'Job Position', 'Contract Type', 'Employment Type', 'Start Date', 'Basic Salary (ETB)', 'Status'],
            'layoffs'    => ['#', 'Full Name', 'Department', 'Job Position', 'Date Resigned', 'Years of Service'],
            'salary_changes' => ['#', 'Full Name', 'Department', 'Job Position', 'Basic Salary (ETB)', 'Grade'],
            'qualifications' => ['#', 'Full Name', 'Department', 'Education Level', 'Field of Study'],
            'seniority'  => ['#', 'Full Name', 'Department', 'Job Position', 'Start Date', 'Years of Service', 'Seniority Band'],
            default      => [],
        };
    }

    public function map($row): array
    {
        return match ($this->reportType) {
            'staff_list' => [
                $row->id,
                $row->full_name,
                $row->department?->name ?? '–',
                $row->jobPosition?->title ?? ($row->position ?? '–'),
                $row->contractType?->name ?? $row->employment_type ?? '–',
                $row->employment_type ?? '–',
                $row->date_of_employment?->format('Y-m-d') ?? '–',
                number_format((float) $row->basic_salary, 2),
                $row->date_resigned ? 'Resigned' : 'Active',
            ],
            'layoffs' => [
                $row->id,
                $row->full_name,
                $row->department?->name ?? '–',
                $row->jobPosition?->title ?? ($row->position ?? '–'),
                $row->date_resigned?->format('Y-m-d') ?? '–',
                $row->date_of_employment ? (int) $row->date_of_employment->diffInYears($row->date_resigned ?? now()) : '–',
            ],
            'salary_changes' => [
                $row->id,
                $row->full_name,
                $row->department?->name ?? '–',
                $row->jobPosition?->title ?? ($row->position ?? '–'),
                number_format((float) $row->basic_salary, 2),
                $row->salaryGrade ? "Grade {$row->salaryGrade->grade} Step {$row->salaryGrade->step}" : '–',
            ],
            'qualifications' => [
                $row->id,
                $row->full_name,
                $row->department?->name ?? '–',
                $row->educationLevel?->name ?? ($row->education_level ?? '–'),
                $row->fieldOfStudy?->name ?? ($row->field_of_study ?? '–'),
            ],
            'seniority' => (function () use ($row) {
                $years = $row->date_of_employment ? (int) $row->date_of_employment->diffInYears(now()) : 0;
                $band  = match (true) {
                    $years < 1  => 'Probation (< 1yr)',
                    $years < 3  => 'Junior (1-3 yrs)',
                    $years < 7  => 'Mid-level (3-7 yrs)',
                    $years < 15 => 'Senior (7-15 yrs)',
                    default     => 'Veteran (15+ yrs)',
                };
                return [
                    $row->id, $row->full_name,
                    $row->department?->name ?? '–',
                    $row->jobPosition?->title ?? ($row->position ?? '–'),
                    $row->date_of_employment?->format('Y-m-d') ?? '–',
                    $years, $band,
                ];
            })(),
            default => [],
        };
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a56db']],
            ],
        ];
    }

    // ── Queries ───────────────────────────────────────────────────────

    private function staffList(): Collection
    {
        return Employee::with(['department', 'jobPosition', 'contractType', 'salaryGrade'])
            ->when(isset($this->filters['department_id']), fn ($q) => $q->where('department_id', $this->filters['department_id']))
            ->when(isset($this->filters['status']), fn ($q) => $this->filters['status'] === 'active'
                ? $q->whereNull('date_resigned')
                : $q->whereNotNull('date_resigned')
            )
            ->orderBy('first_name')
            ->get();
    }

    private function layoffs(): Collection
    {
        return Employee::with(['department', 'jobPosition'])
            ->whereNotNull('date_resigned')
            ->when(isset($this->filters['year']), fn ($q) => $q->whereYear('date_resigned', $this->filters['year']))
            ->orderByDesc('date_resigned')
            ->get();
    }

    private function salaryChanges(): Collection
    {
        return Employee::with(['department', 'jobPosition', 'salaryGrade'])
            ->whereNull('date_resigned')
            ->when(isset($this->filters['department_id']), fn ($q) => $q->where('department_id', $this->filters['department_id']))
            ->orderByDesc('basic_salary')
            ->get();
    }

    private function qualifications(): Collection
    {
        return Employee::with(['department', 'educationLevel', 'fieldOfStudy'])
            ->whereNull('date_resigned')
            ->when(isset($this->filters['department_id']), fn ($q) => $q->where('department_id', $this->filters['department_id']))
            ->orderBy('first_name')
            ->get();
    }

    private function seniority(): Collection
    {
        return Employee::with(['department', 'jobPosition'])
            ->whereNull('date_resigned')
            ->whereNotNull('date_of_employment')
            ->orderBy('date_of_employment')
            ->get();
    }
}
