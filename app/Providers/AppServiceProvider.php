<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Donation::observe(\App\Observers\DonationObserver::class);
        \App\Models\Asset::observe(\App\Observers\AssetObserver::class);
        \App\Models\InventoryMovement::observe(\App\Observers\InventoryMovementObserver::class);
        \App\Models\AssetAssignment::observe(\App\Observers\AssetAssignmentObserver::class);

        // Recruitment Observers
        \App\Models\Recruitment\RecruitmentPlan::observe(\App\Observers\Recruitment\RecruitmentPlanObserver::class);

        // Set default pagination to 25 rows across all Filament tables
        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table): void {
            $table
                ->defaultPaginationPageOption(25)
                ->paginationPageOptions([25, 50, 100, 'all']);
        });
    }
}
