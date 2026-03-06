<?php namespace App\Filament\Resources\Procurement\Invoices\Pages;
use App\Filament\Resources\Procurement\Invoices\InvoiceResource;
use Filament\Actions\DeleteAction; use Filament\Resources\Pages\EditRecord;
class EditInvoice extends EditRecord {
    protected static string $resource = InvoiceResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
