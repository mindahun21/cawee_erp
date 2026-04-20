<?php
namespace App\Filament\Resources\Finance\Payables\Pages;
use App\Filament\Resources\Finance\Payables\PaymentRequisitionResource;
use Filament\Resources\Pages\ListRecords;
class ListPaymentRequisitions extends ListRecords {
    protected static string $resource = PaymentRequisitionResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
