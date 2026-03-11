<?php

namespace App\Filament\Resources\Procurement\Settings\Pages;

use App\Filament\Concerns\HasProcurementSettingsNavigation;
use App\Filament\Resources\Procurement\Settings\ProcurementCurrencyResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageProcurementCurrencies extends ManageRecords
{
    use HasProcurementSettingsNavigation;

    protected static string $resource = ProcurementCurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Add Currency')];
    }

    /**
     * Override Filament's page-level authorization so that all CRUD actions
     * (create, edit, delete) bypass CurrencyPolicy — which belongs to the
     * Fundraising module — and instead use the procurement role check.
     * The existing CurrencyPolicy is NOT modified.
     */
    protected function can(string $action, ?Model $record = null): bool
    {
        /** @var User|null $user */
        $user = auth()->user();
        return $user && ($user->isProcurementOfficer() || $user->isSuperAdmin());
    }
}
