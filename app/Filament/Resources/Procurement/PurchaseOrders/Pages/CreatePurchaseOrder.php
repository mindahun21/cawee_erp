<?php namespace App\Filament\Resources\Procurement\PurchaseOrders\Pages;
use App\Filament\Resources\Procurement\PurchaseOrders\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;
class CreatePurchaseOrder extends CreateRecord {
    protected static string $resource = PurchaseOrderResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
