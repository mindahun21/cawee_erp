<?php

namespace App\Filament\Resources\Procurement\Settings\Pages;

use App\Filament\Concerns\HasProcurementSettingsNavigation;
use App\Filament\Resources\Procurement\Settings\ApprovalWorkflowResource;
use App\Models\Procurement\ProcurementApprovalWorkflow;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageApprovalWorkflows extends ManageRecords
{
    use HasProcurementSettingsNavigation;

    protected static string $resource = ApprovalWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Add Workflow')];
    }

    protected function can(string $action, ?Model $record = null): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isProcurementOfficer() || $user->isSuperAdmin());
    }
}
