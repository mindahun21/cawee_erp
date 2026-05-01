<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Increase memory limit for complex Livewire / Filament pages (e.g. large JE repeaters)
        ini_set('memory_limit', '256M');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            return method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin() ? true : null;
        });
        \App\Models\Donation::observe(\App\Observers\DonationObserver::class);
        \App\Models\Asset::observe(\App\Observers\AssetObserver::class);
        \App\Models\InventoryMovement::observe(\App\Observers\InventoryMovementObserver::class);
        \App\Models\AssetAssignment::observe(\App\Observers\AssetAssignmentObserver::class);

        // Recruitment Observers
        \App\Models\Recruitment\RecruitmentPlan::observe(\App\Observers\Recruitment\RecruitmentPlanObserver::class);
        \App\Models\Recruitment\RecruitmentInterviewSchedule::observe(\App\Observers\Recruitment\RecruitmentInterviewScheduleObserver::class);

        // Set default pagination to 25 rows across all Filament tables
        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table): void {
            $table
                ->defaultPaginationPageOption(25)
                ->paginationPageOptions([25, 50, 100, 'all']);
        });

        // ── Finance Event Listeners ──────────────────────────────────────────
        // Payroll summary ready → post to GL (queued)
        Event::listen(
            \App\Events\Finance\PayrollSummaryReadyForPosting::class,
            \App\Listeners\Finance\PostPayrollSummaryToGLListener::class,
        );

        // Procurement PO approved → create Finance Commitment
        // Wire to your Procurement PO Approved event class here:
        // Event::listen(
        //     \App\Events\Procurement\ProcurementPOApproved::class,
        //     \App\Listeners\Finance\CreateCommitmentFromPOListener::class,
        // );

        // Procurement GRN confirmed → create Encumbrance
        // Event::listen(
        //     \App\Events\Procurement\ProcurementGoodsReceived::class,
        //     \App\Listeners\Finance\CreateEncumbranceFromGRNListener::class,
        // );

        // Petty cash replenishment approved → update fund balance
        // Event::listen(
        //     \App\Events\Finance\PettyCashReplenishmentApproved::class,
        //     \App\Listeners\Finance\UpdatePettyCashFundBalanceListener::class,
        // );
    }
}

