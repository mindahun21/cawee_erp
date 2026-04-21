<?php
namespace App\Filament\Resources\Finance\Receivables\Pages;
use App\Filament\Resources\Finance\Receivables\IncomeRegisterResource;
use Filament\Resources\Pages\EditRecord;
class EditIncomeRegisters extends EditRecord {
    protected static string $resource = IncomeRegisterResource::class;
    protected function getRedirectUrl(): string {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
