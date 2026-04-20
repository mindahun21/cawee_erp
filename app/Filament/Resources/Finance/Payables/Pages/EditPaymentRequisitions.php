<?php
namespace App\Filament\Resources\Finance\Payables\Pages;
use App\Filament\Resources\Finance\Payables\PaymentRequisitionResource;
use Filament\Resources\Pages\EditRecord;
class EditPaymentRequisitions extends EditRecord {
    protected static string $resource = PaymentRequisitionResource::class;
    protected function getRedirectUrl(): string {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
