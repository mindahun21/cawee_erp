<?php

namespace App\Imports;

use App\Models\ContractType;
use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\FieldOfStudy;
use App\Models\JobPosition;
use App\Models\Location;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * EmployeeImport
 *
 * Imports employees from an Excel/CSV file. The first row must be headers.
 * Required columns: first_name, last_name, email
 * All other columns are optional and will be matched by name.
 *
 * Download the template from the import modal to see the correct format.
 */
class EmployeeImport implements ToCollection, WithHeadingRow, SkipsEmptyRows, WithChunkReading
{
    public int $importedCount = 0;
    public int $skippedCount  = 0;
    public array $errors      = [];

    // Chunk size to avoid memory issues with large files
    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $rows): void
    {
        // Pre-load lookup maps to avoid N+1 queries
        $departments     = Department::pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [strtolower($n) => $id])->toArray();
        $positions       = JobPosition::pluck('id', 'title')->mapWithKeys(fn ($id, $t) => [strtolower($t) => $id])->toArray();
        $contractTypes   = ContractType::pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [strtolower($n) => $id])->toArray();
        $educationLevels = EducationLevel::pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [strtolower($n) => $id])->toArray();
        $fieldsOfStudy   = FieldOfStudy::pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [strtolower($n) => $id])->toArray();
        $locations       = Location::pluck('id', 'location_name')->mapWithKeys(fn ($id, $n) => [strtolower($n) => $id])->toArray();
        $projects        = Project::pluck('id', 'project_name')->mapWithKeys(fn ($id, $n) => [strtolower($n) => $id])->toArray();

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 because row 1 is heading

            // Required fields
            if (empty($row['first_name']) || empty($row['email'])) {
                $this->errors[]  = "Row {$rowNum}: first_name and email are required.";
                $this->skippedCount++;
                continue;
            }

            // Skip duplicates by email
            if (Employee::where('email', trim($row['email']))->exists()) {
                $this->skippedCount++;
                continue;
            }

            try {
                Employee::create([
                    'first_name'        => trim($row['first_name']),
                    'last_name'         => trim($row['last_name'] ?? ''),
                    'email'             => strtolower(trim($row['email'])),
                    'phone_number'      => $row['phone_number'] ?? null,
                    'gender'            => isset($row['gender']) ? strtoupper(substr($row['gender'], 0, 1)) : null,
                    'date_of_birth'     => $this->parseDate($row['date_of_birth'] ?? null),
                    'national_id'       => $row['national_id'] ?? null,
                    'tin'               => $row['tin'] ?? null,
                    'pension_id'        => $row['pension_id'] ?? null,
                    'date_of_employment' => $this->parseDate($row['date_of_employment'] ?? null),
                    'employment_type'   => $row['employment_type'] ?? null,
                    'basic_salary'      => is_numeric($row['basic_salary'] ?? null) ? (float) $row['basic_salary'] : 0,
                    'transport_allowance' => is_numeric($row['transport_allowance'] ?? null) ? (float) $row['transport_allowance'] : 0,
                    'house_allowance'   => is_numeric($row['house_allowance'] ?? null) ? (float) $row['house_allowance'] : 0,
                    'bank_account_awash' => $row['bank_account_awash'] ?? null,
                    'remarks'           => $row['remarks'] ?? null,

                    // Lookup by name
                    'department_id'      => $departments[strtolower($row['department'] ?? '')] ?? null,
                    'job_position_id'    => $positions[strtolower($row['job_position'] ?? '')] ?? null,
                    'contract_type_id'   => $contractTypes[strtolower($row['contract_type'] ?? '')] ?? null,
                    'education_level_id' => $educationLevels[strtolower($row['education_level'] ?? '')] ?? null,
                    'field_of_study_id'  => $fieldsOfStudy[strtolower($row['field_of_study'] ?? '')] ?? null,
                    'location_id'        => $locations[strtolower($row['location'] ?? '')] ?? null,
                    'project_id'         => $projects[strtolower($row['project'] ?? '')] ?? null,
                ]);

                $this->importedCount++;
            } catch (\Throwable $e) {
                $this->errors[]  = "Row {$rowNum}: " . $e->getMessage();
                $this->skippedCount++;
            }
        }
    }

    private function parseDate(mixed $value): ?string
    {
        if (empty($value)) return null;
        try {
            if (is_numeric($value)) {
                // Excel serial date
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
