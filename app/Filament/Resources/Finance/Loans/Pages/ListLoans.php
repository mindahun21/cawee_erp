<?php
namespace App\Filament\Resources\Finance\Loans\Pages;
use App\Filament\Resources\Finance\Loans\LoanResource;
use Filament\Resources\Pages\ListRecords;
class ListLoans extends ListRecords {
    protected static string $resource = LoanResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
