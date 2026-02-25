<?php

namespace App\Filament\Resources\HR\LeaveRequests\Pages;

use App\Filament\Resources\HR\LeaveRequests\LeaveRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;
}
