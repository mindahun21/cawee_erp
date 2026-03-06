<?php

namespace App\Filament\Resources\Procurement\Suppliers\Pages;

use App\Filament\Resources\Procurement\Suppliers\SupplierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()->label('Register Supplier')]; }
}
