<?php

namespace App\Filament\Pages\Finance;

use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class FinanceDashboard extends Page
{
    protected string $view = 'filament.pages.finance.finance-dashboard';

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-chart-bar-square';
    protected static string|UnitEnum|null   $navigationGroup = 'Finance';
    protected static ?string                $navigationLabel = 'Finance Dashboard';
    protected static ?int                   $navigationSort  = 0;
    protected static ?string                $slug            = 'finance';
    protected static ?string                $title           = 'Finance Dashboard';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }
}
