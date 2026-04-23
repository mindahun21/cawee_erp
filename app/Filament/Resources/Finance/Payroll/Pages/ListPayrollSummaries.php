<?php
namespace App\Filament\Resources\Finance\Payroll\Pages;
use App\Filament\Resources\Finance\Payroll\PayrollSummaryResource;
use Filament\Resources\Pages\ListRecords;
class ListPayrollSummaries extends ListRecords {
    protected static string $resource = PayrollSummaryResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
