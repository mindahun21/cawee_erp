<?php namespace App\Filament\Resources\Procurement\Invoices\Pages;
use App\Filament\Resources\Procurement\Invoices\InvoiceResource;
use Filament\Actions\CreateAction; use Filament\Resources\Pages\ListRecords;
class ListInvoices extends ListRecords {
    protected static string $resource = InvoiceResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()->label('Register Invoice')]; }
}
