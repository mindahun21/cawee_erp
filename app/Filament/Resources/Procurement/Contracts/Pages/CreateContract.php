<?php namespace App\Filament\Resources\Procurement\Contracts\Pages;
use App\Filament\Resources\Procurement\Contracts\ContractResource;
use Filament\Resources\Pages\CreateRecord;
class CreateContract extends CreateRecord {
    protected static string $resource = ContractResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
