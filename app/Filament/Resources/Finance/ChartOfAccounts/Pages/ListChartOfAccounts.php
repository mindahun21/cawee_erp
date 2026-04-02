<?php

namespace App\Filament\Resources\Finance\ChartOfAccounts\Pages;

use App\Filament\Resources\Finance\ChartOfAccounts\ChartOfAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChartOfAccounts extends ListRecords
{
    protected static string $resource = ChartOfAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Account'),
        ];
    }
}
