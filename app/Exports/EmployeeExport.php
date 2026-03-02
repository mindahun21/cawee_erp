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

class EmployeeExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection(): Collection
    {
        return Employee::with(['department', 'jobPosition', 'contractType', 'educationLevel', 'fieldOfStudy', 'location', 'project', 'salaryGrade'])
            ->orderBy('first_name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Gender', 'Date of Birth',
            'National ID', 'TIN', 'Pension ID',
            'Department', 'Job Position', 'Contract Type', 'Employment Type',
            'Date of Employment', 'Date Resigned',
            'Education Level', 'Field of Study',
            'Basic Salary', 'Transport Allow', 'House Allow', 'Other Allow',
            'Bank (Awash)', 'Bank (Orocoop)', 'Location', 'Project',
            'Status',
        ];
    }

    public function map($e): array
    {
        return [
            $e->id,
            $e->first_name,
            $e->last_name,
            $e->email,
            $e->phone_number,
            $e->gender === 'M' ? 'Male' : ($e->gender === 'F' ? 'Female' : ''),
            $e->date_of_birth?->format('Y-m-d'),
            $e->national_id,
            $e->tin,
            $e->pension_id,
            $e->department?->name,
            $e->jobPosition?->title,
            $e->contractType?->name,
            $e->employment_type,
            $e->date_of_employment?->format('Y-m-d'),
            $e->date_resigned?->format('Y-m-d'),
            $e->educationLevel?->name,
            $e->fieldOfStudy?->name,
            $e->basic_salary,
            $e->transport_allowance,
            $e->house_allowance,
            $e->other_allowances,
            $e->bank_account_awash,
            $e->bank_account_orocoop,
            $e->location?->location_name,
            $e->project?->project_name,
            $e->date_resigned ? 'Resigned' : 'Active',
        ];
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
}
