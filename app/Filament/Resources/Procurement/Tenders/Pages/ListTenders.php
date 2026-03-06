<?php namespace App\Filament\Resources\Procurement\Tenders\Pages;
use App\Filament\Resources\Procurement\Tenders\TenderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListTenders extends ListRecords {
    protected static string $resource = TenderResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()->label('New Tender / RFQ')]; }
}
