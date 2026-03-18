<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\HrHoliday;
use App\Models\HrLeavePolicy;
use App\Models\HrLeaveRequest;
use App\Models\HrLeaveType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * LeaveBalanceService
 *
 * The authoritative leave balance computation engine for the ELiSoft ERP.
 *
 * Key concepts (mirroring the existing Java/Vert.x leave system):
 *
 * 1. **No stored balance** — balance is always derived from leave requests.
 *    approved_request.no_of_days are summed per period; nothing is cached.
 *
 * 2. **Two policy eras** (Ethiopian Labour Proclamation):
 *    - Pre-boundary (default < July 8 2019): 14 days base + 1 day/year accrual
 *    - Post-boundary (default ≥ July 8 2019): 16 days base + 1 day per N years
 *
 * 3. **Fiscal year boundary** defaults to July 8 (Hamle 1 EC).
 *    Each employee's annual cycle restarts at this date.
 *
 * 4. **FIFO redistribution** — after computing raw per-period taken days, the
 *    last N periods are redistributed oldest-first so the oldest available
 *    balance is always depleted before newer entitlements.
 *
 * 5. **Working-day end-date** — for "is_working_days" leave types, the end
 *    date is computed by advancing from start_date while skipping Sundays
 *    and any public holidays in hr_holidays.
 *
 * 6. **Balance report** — returns a Collection of period arrays that the
 *    Filament resource renders. Exact same shape as the Java JSON report.
 */
class LeaveBalanceService
{
    protected HrLeavePolicy $policy;

    /** Cached holiday dates for a given reference year. */
    private array $holidayDatesCache = [];

    public function __construct()
    {
        $this->policy = HrLeavePolicy::current();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PUBLIC API
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Return the full period-by-period leave balance report for an employee.
     *
     * Each element in the returned Collection is an array:
     *  [
     *    'period_label'            => '2023–2024',
     *    'from_date'               => Carbon,
     *    'to_date'                 => Carbon,
     *    'fiscal_year'             => 2024,          // Ethiopian fiscal year
     *    'annual_leave_balance'    => 24,             // prorated entitlement
     *    'full_annual_leave_balance' => 24,           // un-prorated entitlement
     *    'leaves_taken'            => 8,              // after FIFO redistribution
     *    'actual_leaves_taken'     => 8,              // raw DB value, for reference
     *    'remaining_balance'       => 16,
     *    'is_current_period'       => true,
     *  ]
     *
     * @param  Employee  $employee
     * @param  Carbon|null  $asOf     Treat today as this date (for back-dating).
     * @return Collection
     */
    public function getBalanceReport(Employee $employee, ?Carbon $asOf = null): Collection
    {
        $today      = $asOf ?? Carbon::today();
        $hireDate   = Carbon::parse($employee->date_of_employment);
        $boundary   = Carbon::parse($this->policy->era_boundary_date);
        [$fyMonth, $fyDay] = explode('-', $this->policy->fiscal_year_month_day);

        if (! $hireDate || $hireDate->gt($today)) {
            return collect();
        }

        // Step 1: Build ordered list of fiscal-period boundaries from hire → today
        $periods   = $this->buildFiscalPeriods($hireDate, $today, (int)$fyMonth, (int)$fyDay);
        $annualType = HrLeaveType::annual();

        if (! $annualType) {
            return collect(); // No annual leave type configured
        }

        // Step 2: Build raw report per period
        $report = collect();
        foreach ($periods as $period) {
            $report->push($this->buildPeriod(
                $employee, $period, $boundary, $annualType, $hireDate, $today
            ));
        }

        // Step 3: FIFO redistribution across the last N periods
        $report = $this->applyFifoRedistribution($report);

        return $report;
    }

    /**
     * Get the current remaining annual leave balance for an employee (simple scalar).
     */
    public function getRemainingBalance(Employee $employee, ?Carbon $asOf = null): float
    {
        $report = $this->getBalanceReport($employee, $asOf);
        if ($report->isEmpty()) return 0;

        // FIFO balance = sum of remaining across all periods combined in rolling window
        // Simplest: last period's remaining_balance already reflects rolling window
        return max(0, $report->last()['remaining_balance'] ?? 0);
    }

    /**
     * Compute the end_date for a new leave request, respecting the leave type's
     * is_working_days flag (skip Sundays and public holidays).
     *
     * @param  Carbon  $startDate
     * @param  int     $noOfDays
     * @param  bool    $isWorkingDays  If true, skip Sundays + holidays.
     * @return Carbon  $endDate
     */
    public function computeEndDate(Carbon $startDate, int $noOfDays, bool $isWorkingDays): Carbon
    {
        if ($noOfDays <= 0) return $startDate->copy();

        if (! $isWorkingDays) {
            // Continuous calendar days: end = start + (days - 1)
            return $startDate->copy()->addDays($noOfDays - 1);
        }

        // Working days: skip Sundays and public holidays
        $holidays = $this->getHolidayDateSet($startDate->year);
        $date     = $startDate->copy();
        $counted  = 0;

        while ($counted < $noOfDays) {
            if ($this->isWorkingDay($date, $holidays)) {
                $counted++;
            }
            if ($counted < $noOfDays) {
                $date->addDay();
            }
        }

        return $date;
    }

    /**
     * Count the effective working days between two dates (inclusive), skipping
     * Sundays and public holidays if the leave type requires it.
     *
     * @param  Carbon  $start
     * @param  Carbon  $end
     * @param  bool    $isWorkingDays
     * @return int
     */
    public function countEffectiveDays(Carbon $start, Carbon $end, bool $isWorkingDays): int
    {
        if (! $isWorkingDays) {
            return $start->diffInDays($end) + 1;
        }

        $holidays = $this->getHolidayDateSet($start->year);
        $count    = 0;
        $date     = $start->copy();

        while ($date->lte($end)) {
            if ($this->isWorkingDay($date, $holidays)) {
                $count++;
            }
            $date->addDay();
        }

        return $count;
    }

    /**
     * Check if a proposed leave request overlaps with any existing approved/pending
     * request for the same employee.
     *
     * @param  Employee  $employee
     * @param  Carbon    $start
     * @param  Carbon    $end
     * @param  int|null  $excludeId   Leave request ID to exclude (for edits).
     * @return bool
     */
    public function hasOverlap(Employee $employee, Carbon $start, Carbon $end, ?int $excludeId = null): bool
    {
        return HrLeaveRequest::where('employee_id', $employee->id)
            ->whereIn('approval_status', [HrLeaveRequest::STATUS_PENDING, HrLeaveRequest::STATUS_APPROVED])
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start_date', '<=', $start)
                         ->where('end_date', '>=', $end);
                  });
            })
            ->exists();
    }

    /**
     * Return how many annual leave days the employee has available right now.
     * Used by the form to validate before submitting.
     *
     * @param  Employee  $employee
     * @param  int       $requestedDays
     * @return array  ['has_balance' => bool, 'available' => float]
     */
    public function validateAnnualLeaveBalance(Employee $employee, int $requestedDays): array
    {
        $available = $this->getRemainingBalance($employee);
        return [
            'has_balance' => $available >= $requestedDays,
            'available'   => $available,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // IMPORT (Balance seeding from Excel)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Create synthetic "imported" annual leave requests to seed historical balances.
     *
     * Logic:
     *   For each fiscal period in [fiscalYearMap]:
     *     computed_entitlement = period.annual_leave_balance (or full for last period)
     *     no_of_days = computed_entitlement - excel_remaining_value
     *
     *   If no_of_days > 0 and no import already exists for that period:
     *     Insert an Approved leave request (is_imported = true)
     *
     * @param  Employee  $employee
     * @param  array     $fiscalYearMap   e.g. [2016 => 5.0, 2017 => 12.0, 2018 => 3.0]
     *                                    Key = Ethiopian fiscal year, Value = remaining balance from Excel
     * @param  \Illuminate\Database\Connection  $connection  For transaction control
     * @return array  ['created' => int, 'skipped' => int, 'errors' => array]
     */
    public function importBalanceFromExcel(Employee $employee, array $fiscalYearMap, $connection = null): array
    {
        $report    = $this->getBalanceReport($employee);
        $annualType = HrLeaveType::annual();

        if (! $annualType || $report->isEmpty()) {
            return ['created' => 0, 'skipped' => 0, 'errors' => ['No annual leave type or balance report available.']];
        }

        $created = 0;
        $skipped = 0;
        $errors  = [];

        foreach ($report as $index => $period) {
            $fiscalYear = $this->getEthiopianFiscalYear($period['from_date']);
            if (! isset($fiscalYearMap[$fiscalYear])) {
                $skipped++;
                continue;
            }

            $excelRemaining = (float) $fiscalYearMap[$fiscalYear];
            $isLastPeriod   = ($index === $report->count() - 1);
            $baseline       = $isLastPeriod
                ? $period['full_annual_leave_balance']
                : $period['annual_leave_balance'];

            $noOfDays = $baseline - $excelRemaining;

            if ($noOfDays <= 0) {
                $skipped++;
                continue;
            }

            // Check for duplicate import
            $exists = HrLeaveRequest::where('employee_id', $employee->id)
                ->where('hr_leave_type_id', $annualType->id)
                ->where('is_imported', true)
                ->where('import_fiscal_year', $fiscalYear)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            try {
                $startDate = $period['from_date']->copy();
                $endDate   = $this->computeEndDate($startDate, (int) round($noOfDays), $annualType->is_working_days);

                HrLeaveRequest::create([
                    'employee_id'        => $employee->id,
                    'hr_leave_type_id'   => $annualType->id,
                    'start_date'         => $startDate,
                    'end_date'           => $endDate,
                    'no_of_days'         => (int) round($noOfDays),
                    'reason'             => 'Imported annual leave balance',
                    'approval_status'    => HrLeaveRequest::STATUS_APPROVED,
                    'supervisor_status'  => HrLeaveRequest::STATUS_APPROVED,
                    'hr_status'          => HrLeaveRequest::STATUS_APPROVED,
                    'director_status'    => HrLeaveRequest::STATUS_APPROVED,
                    'is_imported'        => true,
                    'import_fiscal_year' => $fiscalYear,
                ]);

                $created++;
            } catch (\Throwable $e) {
                $errors[] = "FY {$fiscalYear}: " . $e->getMessage();
            }
        }

        return compact('created', 'skipped', 'errors');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PRIVATE — Period Building
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Build an ordered list of fiscal year period ranges from hire date to today.
     * Each period = one fiscal year cycle.
     *
     * Special case: the first period may be shorter than a full year if the
     * employee was hired mid-year.
     */
    private function buildFiscalPeriods(
        Carbon $hireDate, Carbon $today, int $fyMonth, int $fyDay
    ): array {
        $periods = [];

        // Find the first fiscal year boundary AFTER the hire date
        $boundaryYear = $hireDate->year;
        $boundary     = Carbon::create($boundaryYear, $fyMonth, $fyDay);

        if ($boundary->lte($hireDate)) {
            $boundary->addYear();
        }

        // Period 0: hire date → (first boundary - 1 day), or → today if boundary > today
        $periodStart = $hireDate->copy();
        $periodEnd   = $boundary->copy()->subDay();

        while ($periodStart->lte($today)) {
            $end = $periodEnd->copy()->min($today);
            $periods[] = [
                'from' => $periodStart->copy(),
                'to'   => $end->copy(),
            ];

            if ($periodEnd->gte($today)) break;

            $periodStart = $boundary->copy();
            $boundary->addYear();
            $periodEnd   = $boundary->copy()->subDay();
        }

        return $periods;
    }

    /**
     * Build a single period's data row.
     */
    private function buildPeriod(
        Employee $employee,
        array    $period,
        Carbon   $boundary,
        HrLeaveType $annualType,
        Carbon   $hireDate,
        Carbon   $today
    ): array {
        $from = $period['from'];
        $to   = $period['to'];

        // ── Entitlement ──────────────────────────────────────────────
        $full    = $this->computeFullEntitlement($hireDate, $from, $boundary);
        $prorated = $this->prorateEntitlement($full, $from, $to);

        // ── Leaves taken in this period (annual leave only, approved) ─
        $takenInPeriod = HrLeaveRequest::where('employee_id', $employee->id)
            ->where('hr_leave_type_id', $annualType->id)
            ->where('approval_status', HrLeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '>=', $from)
            ->whereDate('start_date', '<=', $to)
            ->sum('no_of_days');

        // Ethiopian fiscal year label
        $fiscalYear  = $this->getEthiopianFiscalYear($from);
        $periodLabel = ($fiscalYear - 1) . '–' . $fiscalYear;

        return [
            'period_label'              => $periodLabel,
            'from_date'                 => $from,
            'to_date'                   => $to,
            'fiscal_year'               => $fiscalYear,
            'annual_leave_balance'      => $prorated,
            'full_annual_leave_balance' => $full,
            'leaves_taken'              => (int) $takenInPeriod,   // overwritten by FIFO
            'actual_leaves_taken'       => (int) $takenInPeriod,   // raw, kept for reference
            'remaining_balance'         => 0,                       // filled after FIFO
            'is_current_period'         => $to->isSameDay($today) || $to->gt($today),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PRIVATE — Entitlement Calculation
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Compute the full (un-prorated) annual leave entitlement for a given
     * fiscal period starting at $periodFrom.
     *
     * Rules (Ethiopian Labour Proclamation):
     *   Pre-era employees (hired before boundary):
     *     - Transition: keep pre-era base (14) for 1st 2 post-boundary years,
     *       then += pre_era_accrual_per_year each additional year.
     *       This is simplified here as: use post_era_base once they cross,
     *       applying accrual relative to hire year.
     *
     *   Post-era employees:
     *     Year 1:    post_era_base_days
     *     Year 2:    post_era_base_days + floor(1 / N)  (usually still base)
     *     Year N+1:  post_era_base_days + 1
     *     Year 2N+1: post_era_base_days + 2  …
     *
     * Note: "year" here = service year index (0-based from hire date).
     */
    private function computeFullEntitlement(Carbon $hireDate, Carbon $periodFrom, Carbon $eraBoundary): int
    {
        $p        = $this->policy;
        $isPreEra = $hireDate->lt($eraBoundary);

        // Complete years of service at the start of this period
        $serviceYears = $hireDate->diffInYears($periodFrom);

        if ($isPreEra) {
            // Pre-era hire: use pre_era_base + pre_era_accrual * complete service years
            // (accounts for the entire career up to now using the old proclamation)
            $entitlement = $p->pre_era_base_days + ($serviceYears * $p->pre_era_accrual_per_year);
        } else {
            // Post-era hire: +1 day per every N complete service years
            $accruals    = (int) floor($serviceYears / $p->post_era_accrual_every_n_years);
            $entitlement = $p->post_era_base_days + $accruals;
        }

        return max(0, $entitlement);
    }

    /**
     * Prorate the entitlement for a partial period (< 365 days).
     * Full periods get the full entitlement; partial-year periods (e.g. first
     * period when hired mid-year) are prorated proportionally.
     */
    private function prorateEntitlement(int $fullEntitlement, Carbon $from, Carbon $to): int
    {
        $totalDays = $from->diffInDays($to) + 1;
        if ($totalDays >= 364) {
            return $fullEntitlement; // Full year (allow ±1 day for leap years)
        }

        return (int) round($fullEntitlement * ($totalDays / 365.25));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PRIVATE — FIFO Redistribution
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Redistribute total leave taken across the last N periods using FIFO
     * (oldest balance depleted first), then compute remaining_balance for
     * each period as a rolling N-period window.
     *
     * See the Java implementation comment for full rationale.
     */
    private function applyFifoRedistribution(Collection $report): Collection
    {
        $size      = $report->count();
        $window    = min($this->policy->fifo_window_years, $size);
        $fifoStart = $size - $window;

        // Original taken values (raw from DB)
        $originalTaken = $report->map(fn($p) => $p['leaves_taken'])->values();

        // Sum of taken within FIFO window
        $totalWindowTaken = $originalTaken->slice($fifoStart)->sum();

        // Distribute oldest-first, capped by each period's entitlement
        $fifoTaken     = $originalTaken->values()->toArray();
        $toDistribute  = $totalWindowTaken;

        for ($i = $fifoStart; $i < $size; $i++) {
            $entitlement          = $report[$i]['annual_leave_balance'];
            $assignedHere         = min($toDistribute, $entitlement);
            $toDistribute        -= $assignedHere;
            $fifoTaken[$i]        = $assignedHere;
        }

        // Any overflow goes to the last period
        if ($toDistribute > 0) {
            $fifoTaken[$size - 1] += $toDistribute;
        }

        // Apply FIFO values and compute remaining_balance using rolling window
        $result = $report->values()->map(function ($period, $i) use (
            $report, $fifoTaken, $originalTaken, $window, $size
        ) {
            // Rolling window: look back up to $window periods (including current)
            $windowStart = max(0, $i - $window + 1);
            $windowEnd   = $i;

            $windowEntitlement = 0;
            $windowTaken       = 0;
            for ($j = $windowStart; $j <= $windowEnd; $j++) {
                $windowEntitlement += $report[$j]['annual_leave_balance'];
                $windowTaken       += $fifoTaken[$j];
            }

            $remaining = $windowEntitlement - $windowTaken;

            return array_merge($period, [
                'leaves_taken'    => $fifoTaken[$i],
                'remaining_balance' => max(0, $remaining),
            ]);
        });

        return $result;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PRIVATE — Date Utilities
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Determine the Ethiopian fiscal year a Gregorian date belongs to.
     *
     * Ethiopian fiscal year = July 8 GC → next July 7 GC.
     * So: if date >= July 8, fiscalYear = gregorianYear + 8  (approx EC year+1)
     * Actually stored as the Gregorian ending year for simplicity.
     *
     *   date = 2022-01-01  → fiscal = 2022  (FY 2021–2022)
     *   date = 2022-09-01  → fiscal = 2023  (FY 2022–2023)
     */
    private function getEthiopianFiscalYear(Carbon $date): int
    {
        [$fyMonth, $fyDay] = explode('-', $this->policy->fiscal_year_month_day);
        $boundary = Carbon::create($date->year, (int)$fyMonth, (int)$fyDay);
        return $date->gte($boundary) ? $date->year + 1 : $date->year;
    }

    /**
     * Returns true if the given date is a working day per the policy
     * (not Sunday if skip_sundays, not a holiday if skip_public_holidays).
     */
    private function isWorkingDay(Carbon $date, array $holidays): bool
    {
        if ($this->policy->skip_sundays && $date->dayOfWeek === Carbon::SUNDAY) {
            return false;
        }
        if ($this->policy->skip_public_holidays && in_array($date->toDateString(), $holidays)) {
            return false;
        }
        return true;
    }

    /**
     * Returns a flat array of date strings ('Y-m-d') for all active holidays
     * that could apply to the given year, including recurring ones.
     */
    private function getHolidayDateSet(int $year): array
    {
        if (isset($this->holidayDatesCache[$year])) {
            return $this->holidayDatesCache[$year];
        }

        // Fetch from DB: exact-year holidays OR recurring ones (shown every year)
        $holidays = HrHoliday::query()
            ->where(function ($q) use ($year) {
                $q->whereYear('holiday_date', $year)            // exact for this year
                  ->orWhere(function ($q2) use ($year) {
                      // Recurring: match month+day across adjacent years
                      $q2->where('is_recurring', true)
                         ->whereYear('holiday_date', '<=', $year + 1);
                  });
            })
            ->get();

        $dates = [];
        foreach ($holidays as $holiday) {
            $hDate = Carbon::parse($holiday->holiday_date);

            if ($holiday->is_recurring) {
                // Adjust to the requested year (keep month + day)
                $adjusted = Carbon::create($year, $hDate->month, $hDate->day);
                $dates[]  = $adjusted->toDateString();
            } else {
                $dates[] = $hDate->toDateString();
            }
        }

        $this->holidayDatesCache[$year] = array_unique($dates);
        return $this->holidayDatesCache[$year];
    }
}
