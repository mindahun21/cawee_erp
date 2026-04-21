<?php
namespace App\Filament\Resources\Finance\Perdiem\Pages;
use App\Filament\Resources\Finance\Perdiem\PerdiemRequestResource;
use Filament\Resources\Pages\ListRecords;
class ListPerdiemRequests extends ListRecords {
    protected static string $resource = PerdiemRequestResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
