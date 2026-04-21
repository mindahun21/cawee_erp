<?php
namespace App\Filament\Resources\Finance\FinancialStatements\Pages;
use App\Filament\Resources\Finance\FinancialStatements\FinancialStatementResource;
use Filament\Resources\Pages\ListRecords;
class ListFinancialStatements extends ListRecords {
    protected static string $resource = FinancialStatementResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
