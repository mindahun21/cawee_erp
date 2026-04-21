<?php
namespace App\Filament\Resources\Finance\Perdiem\PerdiemRequestExtensionResource\Pages;
use App\Filament\Resources\Finance\Perdiem\PerdiemRequestExtensionResource;
use Filament\Resources\Pages\ManageRecords;
class ManagePerdiemRequestExtensions extends ManageRecords {
    protected static string $resource = PerdiemRequestExtensionResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
