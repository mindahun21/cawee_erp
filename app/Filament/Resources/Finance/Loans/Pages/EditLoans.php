<?php
namespace App\Filament\Resources\Finance\Loans\Pages;
use App\Filament\Resources\Finance\Loans\LoanResource;
use Filament\Resources\Pages\EditRecord;
class EditLoans extends EditRecord {
    protected static string $resource = LoanResource::class;
    protected function getRedirectUrl(): string {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
