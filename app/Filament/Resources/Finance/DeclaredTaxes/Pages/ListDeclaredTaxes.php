<?php
namespace App\Filament\Resources\Finance\DeclaredTaxes\Pages;
use App\Filament\Resources\Finance\DeclaredTaxes\DeclaredTaxResource;
use Filament\Resources\Pages\ListRecords;
class ListDeclaredTaxes extends ListRecords {
    protected static string $resource = DeclaredTaxResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
