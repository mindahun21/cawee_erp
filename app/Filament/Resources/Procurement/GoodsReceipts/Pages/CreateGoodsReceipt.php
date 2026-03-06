<?php namespace App\Filament\Resources\Procurement\GoodsReceipts\Pages;
use App\Filament\Resources\Procurement\GoodsReceipts\GoodsReceiptResource;
use Filament\Resources\Pages\CreateRecord;
class CreateGoodsReceipt extends CreateRecord {
    protected static string $resource = GoodsReceiptResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
