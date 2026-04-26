<?php

namespace App\Filament\Pages\Finance;

use App\Traits\BelongsToModulePage;

use App\Filament\Widgets\Finance\FinanceBudgetUtilizationWidget;
use App\Filament\Widgets\Finance\FinanceBurnRateChartWidget;
use App\Filament\Widgets\Finance\FinanceCashPositionWidget;
use App\Filament\Widgets\Finance\FinanceFundTransferStatusWidget;
use App\Filament\Widgets\Finance\FinancePendingApprovalsWidget;
use App\Filament\Widgets\Finance\FinancePettyCashStatusWidget;
use App\Filament\Widgets\Finance\FinanceTaxObligationsWidget;
use App\Filament\Widgets\Finance\FinanceTopExpensesWidget;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class FinanceDashboard extends Page
{
    use BelongsToModulePage;

    // No custom $view — Filament renders header + footer widgets automatically
    // via x-filament-panels::page. A custom blade that ALSO calls getHeaderWidgets()
    // causes every widget to appear twice. Removing it fixes the duplication.

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-chart-bar-square';
    protected static string|UnitEnum|null   $navigationGroup = 'Finance';
    protected static ?string                $navigationLabel = 'Finance Dashboard';
    protected static ?int                   $navigationSort  = 0;
    protected static ?string                $slug            = 'finance';      // restored — was causing RouteNotFoundException
    protected static ?string                $title           = 'Finance Dashboard';

    public static function canAccess(): bool
    {
        if (! \App\Support\ModuleManager::isPageEnabled(static::class)) {
            return false;
        }

        $user = auth()->user();
        return $user instanceof User && ($user->isFinanceOfficer() || $user->isFinanceManager() || $user->isSuperAdmin());
    }

    // ── Widgets registered on this page ───────────────────────────────────
    // Using getHeaderWidgets + getFooterWidgets (two visual rows) OR
    // just getWidgets with getColumns for a single unified grid.
    // We do a single unified list here to keep the layout clean.

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceCashPositionWidget::class,
            FinancePendingApprovalsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            FinanceBurnRateChartWidget::class,
            FinanceBudgetUtilizationWidget::class,
            FinancePettyCashStatusWidget::class,
            FinanceTopExpensesWidget::class,
            FinanceFundTransferStatusWidget::class,
            FinanceTaxObligationsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'sm'      => 2,
            'lg'      => 4,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'sm'      => 2,
            'lg'      => 3,
        ];
    }
}
