<?php
namespace App\Filament\Resources\Finance\FinancialStatements\Pages;
use App\Filament\Resources\Finance\FinancialStatements\FinancialStatementResource;
use Filament\Resources\Pages\EditRecord;
class EditFinancialStatements extends EditRecord {
    protected static string $resource = FinancialStatementResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('view', ['record' => $this->record]); }
}
