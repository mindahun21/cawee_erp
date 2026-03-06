<?php

namespace Database\Seeders;

use App\Models\ContractType;
use App\Models\Department;
use App\Models\Dependent;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\FieldOfStudy;
use App\Models\JobPosition;
use App\Models\Training;
use App\Models\TrainingType;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Carbon\Carbon;

class HrDummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $departments = Department::all();
        $positions = JobPosition::all();
        $contractTypes = ContractType::all();
        $edLevels = EducationLevel::all();
        $fields = FieldOfStudy::all();
        $trainingTypes = TrainingType::all();

        if ($departments->isEmpty() || $positions->isEmpty()) {
            $this->command->warn('Skipping HR Dummy Data: lookups missing. Run HrSettingsSeeder first.');
            return;
        }

        $this->command->info('Creating 50 Dummy Employees with Contracts, Dependents, and Trainings...');

        for ($i = 0; $i < 50; $i++) {
            $gender = $faker->randomElement(['M', 'F']);
            $firstName = $gender === 'M' ? $faker->firstNameMale : $faker->firstNameFemale;
            
            // Randomly set resign date for some employees to test "Layoff/Resigned" reports
            $isResigned = $faker->boolean(15); 
            $startDate = Carbon::instance($faker->dateTimeBetween('-10 years', '-1 month'));
            $resignDate = $isResigned ? (clone $startDate)->addMonths($faker->numberBetween(6, 48)) : null;

            $employee = Employee::create([
                'first_name' => $firstName,
                'last_name' => $faker->lastName,
                'gender' => $gender,
                'date_of_birth' => $faker->dateTimeBetween('-50 years', '-22 years')->format('Y-m-d'),
                'national_id' => $faker->numerify('NID-########'),
                'tin' => $faker->numerify('TIN-#######'),
                'pension_id' => $faker->numerify('PEN-######'),
                'phone_number' => '+2519' . $faker->numerify('########'),
                'email' => strtolower($firstName) . '.' . $faker->unique()->word . '@example.com',
                
                'education_level_id' => $edLevels->random()->id,
                'field_of_study_id' => $fields->random()->id,
                
                'department_id' => $departments->random()->id,
                'job_position_id' => $positions->random()->id,
                'contract_type_id' => $contractTypes->random()->id,
                'employment_type' => $faker->randomElement(['Full-Time', 'Part-Time']),
                'date_of_employment' => $startDate,
                'date_resigned' => $resignDate,
                
                'basic_salary' => $faker->randomFloat(2, 5000, 45000),
                'transport_allowance' => $faker->randomElement([500, 1000, 1500, 2000]),
                'house_allowance' => $faker->randomElement([1000, 2000, 3000]),
                'bank_account_awash' => $faker->numerify('1000########'),
                
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ── Contracts ──────────────────────────────────────────
            $numContracts = $faker->numberBetween(1, 3);
            $contractStart = clone $startDate;
            for ($c = 0; $c < $numContracts; $c++) {
                $contractEnd = (clone $contractStart)->addYear();
                $isLast = ($c === $numContracts - 1);
                
                EmployeeContract::create([
                    'employee_id' => $employee->id,
                    'contract_type_id' => $contractTypes->random()->id,
                    'start_date' => $contractStart,
                    'end_date' => $isLast && !$isResigned && $faker->boolean(50) ? null : $contractEnd,
                    'salary' => $employee->basic_salary - ($numContracts - $c) * 1000, // progressive salary
                    'status' => ($isLast && !$isResigned) ? 'Active' : ($isResigned && $isLast ? 'Terminated' : 'Expired'),
                    'file_path' => null,
                ]);

                $contractStart = clone $contractEnd;
            }

            // ── Dependents ─────────────────────────────────────────
            $numDependents = $faker->numberBetween(0, 3);
            for ($d = 0; $d < $numDependents; $d++) {
                Dependent::create([
                    'employee_id' => $employee->id,
                    'full_name' => $faker->firstName . ' ' . $employee->last_name,
                    'relationship' => $faker->randomElement(['Spouse', 'Child', 'Parent', 'Sibling']),
                    'date_of_birth' => $faker->dateTimeBetween('-40 years', 'now')->format('Y-m-d'),
                    'is_beneficiary' => $faker->boolean(40),
                ]);
            }

            // ── Trainings ──────────────────────────────────────────
            $numTrainings = $faker->numberBetween(0, 4);
            for ($t = 0; $t < $numTrainings; $t++) {
                $tstart = Carbon::instance($faker->dateTimeBetween($startDate, 'now'));
                Training::create([
                    'employee_id' => $employee->id,
                    'training_type_id' => $trainingTypes->random()->id,
                    'title' => $faker->catchPhrase . ' Training',
                    'institution' => $faker->company,
                    'start_date' => $tstart,
                    'end_date' => (clone $tstart)->addDays($faker->numberBetween(1, 14)),
                    'certificate_path' => null,
                ]);
            }
        }

        $this->command->info('  Successfully seeded 50 Dummy Employees with their related data!');
    }
}
