<?php
namespace App\Filament\Resources\Finance\Receivables\Pages;
use App\Filament\Resources\Finance\Receivables\IncomeRegisterResource;
use Filament\Resources\Pages\ListRecords;
class ListIncomeRegisters extends ListRecords {
    protected static string $resource = IncomeRegisterResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
