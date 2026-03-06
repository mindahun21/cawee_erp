<?php namespace App\Filament\Resources\Procurement\Tenders\Pages;
use App\Filament\Resources\Procurement\Tenders\TenderResource;
use Filament\Resources\Pages\CreateRecord;
class CreateTender extends CreateRecord {
    protected static string $resource = TenderResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
