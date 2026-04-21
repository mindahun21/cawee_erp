<?php
namespace App\Filament\Resources\Finance\ProjectProgressPayments\Pages;
use App\Filament\Resources\Finance\ProjectProgressPayments\ProjectProgressPaymentResource;
use Filament\Resources\Pages\ListRecords;
class ListProjectProgressPayments extends ListRecords {
    protected static string $resource = ProjectProgressPaymentResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
