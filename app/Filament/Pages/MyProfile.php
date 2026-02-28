<?php

namespace App\Filament\Pages;

use App\Models\Delegation;
use App\Models\Employee;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MyProfile extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;
    protected static string|\UnitEnum|null $navigationGroup = 'My Area';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.my-profile';

    public ?Employee $employee = null;

    public function mount()
    {
        $this->employee = auth()->user()->employee;
    }

    protected function getViewData(): array
    {
        if (! $this->employee) {
            return [];
        }

        $emp = $this->employee;

        // Last movement for the header badge
        $lastMovement = $emp->movements()
            ->where('status', 'Approved')
            ->latest('effective_date')
            ->first();

        return [
            'contracts'   => $emp->contracts()->with('contractType')->latest()->get(),
            'dependents'  => $emp->dependents()->get(),
            'trainings'   => $emp->trainings()->with('trainingType')->latest('start_date')->get(),

            // Career history — approved movements ordered newest first
            'movements'   => $emp->movements()
                ->with(['fromDepartment', 'toDepartment', 'fromPosition', 'toPosition', 'approver'])
                ->where('status', 'Approved')
                ->latest('effective_date')
                ->get(),

            'lastMovement' => $lastMovement,

            // Active delegations (given or received)
            'activeDelegationsGiven'    => $emp->delegationsGiven()
                ->where('status', 'Active')
                ->with('delegate')
                ->get(),
            'activeDelegationsReceived' => $emp->delegationsReceived()
                ->where('status', 'Active')
                ->with('delegator')
                ->get(),

            'projects'    => [],
            'payslips'    => [],
            'assets'      => [],
            'attachments' => [],
        ];
    }
}
