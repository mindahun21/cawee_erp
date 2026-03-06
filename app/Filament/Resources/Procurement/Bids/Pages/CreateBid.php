<?php namespace App\Filament\Resources\Procurement\Bids\Pages;
use App\Filament\Resources\Procurement\Bids\BidResource;
use Filament\Resources\Pages\CreateRecord;
class CreateBid extends CreateRecord {
    protected static string $resource = BidResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
