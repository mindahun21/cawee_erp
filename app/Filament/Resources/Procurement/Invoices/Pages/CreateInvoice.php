<?php namespace App\Filament\Resources\Procurement\Invoices\Pages;
use App\Filament\Resources\Procurement\Invoices\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;
class CreateInvoice extends CreateRecord {
    protected static string $resource = InvoiceResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
