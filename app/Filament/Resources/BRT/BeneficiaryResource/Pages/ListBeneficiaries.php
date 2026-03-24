<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\BeneficiaryResource\Pages;

use App\Filament\Imports\BrtBeneficiaryImporter;
use App\Filament\Resources\BRT\BeneficiaryResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListBeneficiaries extends ListRecords
{
    protected static string $resource = BeneficiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label('Import Beneficiaries')
                ->icon('heroicon-o-arrow-up-tray')
                ->importer(BrtBeneficiaryImporter::class),
            CreateAction::make(),
        ];
    }
}
