<?php namespace App\Filament\Resources\Procurement\GoodsReceipts\Pages;
use App\Filament\Resources\Procurement\GoodsReceipts\GoodsReceiptResource;
use Filament\Actions\DeleteAction; use Filament\Resources\Pages\EditRecord;
class EditGoodsReceipt extends EditRecord {
    protected static string $resource = GoodsReceiptResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
