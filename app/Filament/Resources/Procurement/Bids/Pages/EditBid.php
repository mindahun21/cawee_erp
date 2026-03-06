<?php namespace App\Filament\Resources\Procurement\Bids\Pages;
use App\Filament\Resources\Procurement\Bids\BidResource;
use Filament\Actions\DeleteAction; use Filament\Resources\Pages\EditRecord;
class EditBid extends EditRecord {
    protected static string $resource = BidResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
