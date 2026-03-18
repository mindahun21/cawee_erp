<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\HrHoliday;
use App\Models\HrLeaveRequest;
use App\Models\HrLeaveType;
use App\Models\HrTimesheet;
use App\Models\HrTimesheetEntry;
use App\Models\Project;
use App\Models\Location;
use App\Models\Department;
use App\Models\JobPosition;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class HrTimesheetSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Timesheet & Leave Request demo data...');

        // 1. Ensure some Leave Types exist
        $leaveTypes = [
            'Sick Leave',
            'Vacation',
            'Personal Leave',
            'Maternity Leave',
            'Paternity Leave',
        ];

        foreach ($leaveTypes as $name) {
            HrLeaveType::firstOrCreate(['name' => $name], ['is_active' => true]);
        }

        // 2. Ensure some Holidays exist
        $holidays = [
            ['name' => 'New Year Day', 'holiday_date' => Carbon::parse(date('Y') . '-01-01'), 'is_recurring' => true],
            ['name' => 'Victory of Adwa', 'holiday_date' => Carbon::parse(date('Y') . '-03-02'), 'is_recurring' => true],
            ['name' => 'Workers Day', 'holiday_date' => Carbon::parse(date('Y') . '-05-01'), 'is_recurring' => true],
        ];

        foreach ($holidays as $holiday) {
            HrHoliday::firstOrCreate(['name' => $holiday['name']], $holiday);
        }

        // 3. Ensure some Projects exist
        $location = Location::first() ?? Location::create(['location_name' => 'Head Office']);
        
        $projects = [
            ['project_name' => 'ERP Implementation', 'project_code' => 'PRJ-ERP', 'location_id' => $location->id],
            ['project_name' => 'Cloud Migration', 'project_code' => 'PRJ-CLD', 'location_id' => $location->id],
            ['project_name' => 'Security Audit', 'project_code' => 'PRJ-SEC', 'location_id' => $location->id],
        ];

        foreach ($projects as $project) {
            Project::firstOrCreate(['project_code' => $project['project_code']], $project);
        }

        // 4. Create dummy Employees if none exist
        if (Employee::count() === 0) {
            $this->command->info('No employees found. Creating 3 test employees...');
            
            $dept = Department::first() ?? Department::create(['name' => 'IT', 'code' => 'IT']);
            $pos = JobPosition::first() ?? JobPosition::create(['title' => 'Developer', 'department_id' => $dept->id]);
            
            $testEmployees = [
                ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@example.com'],
                ['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane.smith@example.com'],
                ['first_name' => 'Abebe', 'last_name' => 'Bikila', 'email' => 'abebe.bikila@example.com'],
            ];

            foreach ($testEmployees as $data) {
                Employee::create(array_merge($data, [
                    'gender' => 'M',
                    'date_of_birth' => '1990-01-01',
                    'national_id' => 'ID-' . rand(1000, 9999),
                    'tin' => 'TIN-' . rand(1000, 9999),
                    'phone_number' => '+251' . rand(100000000, 999999999),
                    'department_id' => $dept->id,
                    'job_position_id' => $pos->id,
                    'employment_type' => 'Full-Time',
                    'date_of_employment' => now()->subYear(),
                    'basic_salary' => 25000,
                ]));
            }
        }

        $employees = Employee::limit(5)->get();
        $projectIds = Project::pluck('id')->toArray();
        $leaveTypeIds = HrLeaveType::pluck('id')->toArray();

        foreach ($employees as $employee) {
            // Create a timesheet for the current month
            $timesheet = HrTimesheet::create([
                'employee_id' => $employee->id,
                'location_id' => $location->id,
                'month' => date('n'),
                'year' => date('Y'),
                'status' => 'submitted',
            ]);

            // Add some random work entries
            $selectedProjects = array_intersect_key($projectIds, array_flip((array)array_rand($projectIds, min(2, count($projectIds)))));
            
            foreach ($selectedProjects as $projectId) {
                // Seed some hours for first 5 days
                for ($day = 1; $day <= 5; $day++) {
                    HrTimesheetEntry::create([
                        'hr_timesheet_id' => $timesheet->id,
                        'project_id' => $projectId,
                        'day' => $day,
                        'hours' => 8,
                        'location_id' => $location->id,
                        'description' => "Worked on " . Project::find($projectId)->project_name . " - Task " . $day,
                    ]);
                }
            }

            // Create an APPROVED leave request
            $leaveTypeId = $leaveTypeIds[array_rand($leaveTypeIds)];
            HrLeaveRequest::create([
                'employee_id' => $employee->id,
                'hr_leave_type_id' => $leaveTypeId,
                'start_date' => Carbon::now()->startOfMonth()->addDays(10), // Day 11
                'end_date' => Carbon::now()->startOfMonth()->addDays(12),   // Day 13
                'reason' => 'Test leave info',
                'approval_status' => HrLeaveRequest::STATUS_APPROVED,
                'approval_date' => now()->toDateString(),
                'supervisor_status' => HrLeaveRequest::STATUS_APPROVED,
                'supervisor_approved_at' => now(),
                'hr_status' => HrLeaveRequest::STATUS_APPROVED,
                'hr_approved_at' => now(),
                'director_status' => HrLeaveRequest::STATUS_APPROVED,
                'director_approved_at' => now(),
            ]);
        }

        $this->command->info('Successfully seeded Timesheet demo data!');
    }
}
