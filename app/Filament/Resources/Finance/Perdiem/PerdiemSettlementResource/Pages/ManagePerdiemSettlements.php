<?php
namespace App\Filament\Resources\Finance\Perdiem\PerdiemSettlementResource\Pages;
use App\Filament\Resources\Finance\Perdiem\PerdiemSettlementResource;
use Filament\Resources\Pages\ManageRecords;
class ManagePerdiemSettlements extends ManageRecords {
    protected static string $resource = PerdiemSettlementResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
