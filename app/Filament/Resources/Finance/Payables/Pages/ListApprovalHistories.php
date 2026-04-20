<?php

namespace App\Filament\Resources\Finance\Payables\Pages;

use App\Filament\Resources\Finance\Payables\ApprovalHistoryResource;
use Filament\Resources\Pages\ListRecords;

class ListApprovalHistories extends ListRecords
{
    protected static string $resource = ApprovalHistoryResource::class;
}
