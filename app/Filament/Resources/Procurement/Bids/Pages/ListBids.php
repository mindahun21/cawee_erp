<?php namespace App\Filament\Resources\Procurement\Bids\Pages;
use App\Filament\Resources\Procurement\Bids\BidResource;
use Filament\Actions\CreateAction; use Filament\Resources\Pages\ListRecords;
class ListBids extends ListRecords {
    protected static string $resource = BidResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()->label('Register Bid')]; }
}
