<?php

namespace App\Support\FileSharing;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Collection;

class EmployeeRecipientOptions
{
    protected static function employees(): Collection
    {
        return Employee::query()
            ->with('user')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public static function employeeOptions(): array
    {
        return static::employees()
            ->mapWithKeys(function (Employee $employee): array {
                $label = $employee->full_name;

                if (filled($employee->email)) {
                    $label .= ' ('.$employee->email.')';
                } elseif (filled($employee->user?->email)) {
                    $label .= ' ('.$employee->user->email.')';
                }

                if (blank($employee->user_id)) {
                    $label .= ' - No linked login';
                }

                return [$employee->id => $label];
            })
            ->all();
    }

    public static function userIdForEmployeeId($employeeId): ?int
    {
        if (blank($employeeId)) {
            return null;
        }

        return Employee::query()
            ->whereKey($employeeId)
            ->value('user_id');
    }

    public static function employeeIdForUserId($userId): ?int
    {
        if (blank($userId)) {
            return null;
        }

        return Employee::query()
            ->where('user_id', $userId)
            ->value('id');
    }

    public static function emailForEmployeeId($employeeId): ?string
    {
        if (blank($employeeId)) {
            return null;
        }

        return Employee::query()
            ->whereKey($employeeId)
            ->value('email');
    }

    public static function labelForUser(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $employee = $user->employee;

        if (! $employee) {
            return $user->name;
        }

        $label = $employee->full_name;

        if (filled($employee->email)) {
            $label .= ' ('.$employee->email.')';
        } elseif (filled($user->email)) {
            $label .= ' ('.$user->email.')';
        }

        return $label;
    }

    public static function labelForEmployee(?Employee $employee): ?string
    {
        if (! $employee) {
            return null;
        }

        $label = $employee->full_name;

        if (filled($employee->email)) {
            $label .= ' ('.$employee->email.')';
        } elseif (filled($employee->user?->email)) {
            $label .= ' ('.$employee->user->email.')';
        }

        if (blank($employee->user_id)) {
            $label .= ' - No linked login';
        }

        return $label;
    }
}
