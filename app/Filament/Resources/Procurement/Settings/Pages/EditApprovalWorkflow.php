<?php

namespace App\Filament\Resources\Procurement\Settings\Pages;

use App\Filament\Resources\Procurement\Settings\ApprovalWorkflowResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;

class EditApprovalWorkflow extends EditRecord
{
    protected static string $resource = ApprovalWorkflowResource::class;

    protected function getRedirectUrl(): string
    {
        return ApprovalWorkflowResource::getUrl();
    }

    protected function can(string $action, ?\Illuminate\Database\Eloquent\Model $record = null): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isProcurementOfficer() || $user->isSuperAdmin());
    }
}
