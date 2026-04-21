<?php
namespace App\Filament\Resources\Finance\DeclaredTaxes\Pages;
use App\Filament\Resources\Finance\DeclaredTaxes\DeclaredTaxResource;
use Filament\Resources\Pages\CreateRecord;
class CreateDeclaredTaxes extends CreateRecord {
    protected static string $resource = DeclaredTaxResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('view', ['record' => $this->record]); }
}
