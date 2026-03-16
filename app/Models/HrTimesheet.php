<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;


class HrTimesheet extends Model
{
    protected $table = 'hr_timesheets';

    protected $fillable = [
        'employee_id',
        'location_id',
        'month',
        'year',
        'status',
        'supervisor_id',
        'approved_by',
    ];

    protected $appends = [
        'timesheet_data',
    ];

    // ── Relationships ───────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(HrTimesheetEntry::class, 'hr_timesheet_id');
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(HrTimesheetLeave::class, 'hr_timesheet_id');
    }

    // ── Virtual Attribute for Filament Grid ─────────

    public function getTimesheetDataAttribute(): array
    {
        $data = [
            'projects' => [],
            'leaves' => [],
            'daily_details' => [],
            'holidays' => [],
        ];

        // 1. Load entries (grouped by project)
        foreach ($this->entries as $entry) {
            $projId = $entry->project_id ?? 'null';
            if (!isset($data['projects'][$projId])) {
                $data['projects'][$projId] = [];
            }
            $data['projects'][$projId][$entry->day] = $entry->hours;

            // Daily details
            if (!isset($data['daily_details'][$entry->day]) || $projId === 'null') {
                $data['daily_details'][$entry->day] = [
                    'location_id' => $entry->location_id,
                    'description' => $entry->description,
                ];
            }
        }

        // 2. Load manual leave overrides
        foreach ($this->leaves as $leave) {
            if (!isset($data['leaves'][$leave->hr_leave_type_id])) {
                $data['leaves'][$leave->hr_leave_type_id] = [];
            }
            $data['leaves'][$leave->hr_leave_type_id][$leave->day] = $leave->hours;
        }

        // 3. Load approved leaves from HR Leave module
        $approvedLeaves = HrLeaveRequest::where('employee_id', $this->employee_id)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereMonth('start_date', $this->month)
                      ->whereYear('start_date', $this->year)
                      ->orWhereMonth('end_date', $this->month)
                      ->whereYear('end_date', $this->year);
            })
            ->get();

        foreach ($approvedLeaves as $request) {
            $start = Carbon::parse($request->start_date);
            $end = Carbon::parse($request->end_date);

            $current = clone $start;
            while ($current <= $end) {
                if ($current->month == $this->month && $current->year == $this->year) {
                    $day = $current->day;
                    $typeId = $request->hr_leave_type_id;
                    if (!isset($data['leaves'][$typeId])) $data['leaves'][$typeId] = [];
                    if (!isset($data['leaves'][$typeId][$day])) {
                        $data['leaves'][$typeId][$day] = 8;
                    }
                }
                $current->addDay();
            }
        }

        // 4. Load Holidays for this month
        $monthInt = (int) $this->month;
        $yearInt = (int) $this->year;
        
        // From dedicated Holiday table
        $holidays = HrHoliday::all();
        foreach ($holidays as $h) {
            $date = $h->holiday_date;
            if (!$date) continue;
            
            if ($h->is_recurring && $date->month == $monthInt) {
                $data['holidays'][$date->day] = $h->name;
            } elseif (!$h->is_recurring && $date->month == $monthInt && $date->year == $yearInt) {
                $data['holidays'][$date->day] = $h->name;
            }
        }

        // From Leave Types which act as holidays (Fixed Dates)
        $leaveTypeHolidays = HrLeaveType::whereNotNull('holiday_date')->get();
        foreach ($leaveTypeHolidays as $lty) {
            $date = $lty->holiday_date;
            if ($lty->is_recurring && $date->month == $monthInt) {
                $data['holidays'][$date->day] = $lty->name;
            } elseif (!$lty->is_recurring && $date->month == $monthInt && $date->year == $yearInt) {
                $data['holidays'][$date->day] = $lty->name;
            }
        }

        return $data;
    }

    // ── Save Timesheet Grid Data ─────────────────────

    public function saveTimesheetData(array $value): void
    {
        DB::transaction(function () use ($value) {
            // 1. Handle Project & General Entries
            $this->entries()->delete();
            if (!empty($value['projects'])) {
                foreach ($value['projects'] as $projectId => $days) {
                    $actualProjectId = ($projectId === 'null' || $projectId === '') ? null : $projectId;
                    if (!is_array($days)) continue;

                    foreach ($days as $day => $hours) {
                        if (!$hours) continue;

                        $details = $value['daily_details'][$day] ?? [];
                        $this->entries()->create([
                            'project_id' => $actualProjectId,
                            'day' => (int) $day,
                            'hours' => (float) $hours,
                            'location_id' => $details['location_id'] ?? null,
                            'description' => $details['description'] ?? null,
                        ]);
                    }
                }
            }

            // 2. Handle Leaves
            $this->leaves()->delete();
            if (!empty($value['leaves'])) {
                foreach ($value['leaves'] as $leaveTypeId => $days) {
                    if (!is_array($days)) continue;

                    foreach ($days as $day => $hours) {
                        if (!$hours) continue;
                        $this->leaves()->create([
                            'hr_leave_type_id' => $leaveTypeId,
                            'day' => (int) $day,
                            'hours' => (float) $hours,
                        ]);
                    }
                }
            }
        });
    }

    // ── Mutator for backwards compatibility ─────────

    public function setTimesheetDataAttribute($value): void
    {
        if ($this->exists && $this->id) {
            $this->saveTimesheetData(is_array($value) ? $value : []);
        }
    }

    // ── Generate Preview Data ───────────────────────

    public static function generatePreviewData($employeeId, $month, $year): array
    {
        $data = [
            'projects' => [],
            'leaves' => [],
            'daily_details' => [],
            'holidays' => [],
        ];

        if (!$month || !$year) return $data;

        // 1. Load Holidays (Independent of employee selection)
        $monthInt = (int) $month;
        $yearInt = (int) $year;
        
        // From dedicated Holiday table
        $holidays = HrHoliday::all();
        foreach ($holidays as $h) {
            $date = $h->holiday_date;
            if (!$date) continue;

            if ($h->is_recurring && $date->month == $monthInt) {
                $data['holidays'][$date->day] = $h->name;
            } elseif (!$h->is_recurring && $date->month == $monthInt && $date->year == $yearInt) {
                $data['holidays'][$date->day] = $h->name;
            }
        }

        // From Leave Types which act as holidays (Fixed Dates)
        $leaveTypeHolidays = HrLeaveType::whereNotNull('holiday_date')->get();
        foreach ($leaveTypeHolidays as $lty) {
            $date = $lty->holiday_date;
            if ($lty->is_recurring && $date->month == $monthInt) {
                $data['holidays'][$date->day] = $lty->name;
            } elseif (!$lty->is_recurring && $date->month == $monthInt && $date->year == $yearInt) {
                $data['holidays'][$date->day] = $lty->name;
            }
        }

        if (!$employeeId) return $data;

        // 2. Load Employee-specific data (Leaves & Previous Projects)
        $approvedLeaves = HrLeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where(function ($query) use ($month, $year) {
                $query->whereMonth('start_date', $month)->whereYear('start_date', $year)
                      ->orWhereMonth('end_date', $month)->whereYear('end_date', $year);
            })
            ->get();

        foreach ($approvedLeaves as $request) {
            $current = Carbon::parse($request->start_date);
            $end = Carbon::parse($request->end_date);
            while ($current <= $end) {
                if ($current->month == $month && $current->year == $year) {
                    $day = $current->day;
                    $typeId = $request->hr_leave_type_id;
                    if (!isset($data['leaves'][$typeId])) $data['leaves'][$typeId] = [];
                    $data['leaves'][$typeId][$day] = 8;
                }
                $current->addDay();
            }
        }

        // Pre-populate projects from previous month
        $prevMonth = ($month == 1) ? 12 : $month - 1;
        $prevYear = ($month == 1) ? $year - 1 : $year;

        $lastTimesheet = self::where('employee_id', $employeeId)
            ->where('month', $prevMonth)
            ->where('year', $prevYear)
            ->first();

        if ($lastTimesheet) {
            foreach ($lastTimesheet->entries->pluck('project_id')->unique() as $projId) {
                $id = $projId ?: 'null';
                if (!isset($data['projects'][$id])) {
                    $data['projects'][$id] = [];
                }
            }
        }

        return $data;
    }
}