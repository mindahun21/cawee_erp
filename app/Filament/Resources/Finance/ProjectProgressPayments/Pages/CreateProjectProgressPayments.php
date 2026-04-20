<?php
namespace App\Filament\Resources\Finance\ProjectProgressPayments\Pages;
use App\Filament\Resources\Finance\ProjectProgressPayments\ProjectProgressPaymentResource;
use Filament\Resources\Pages\CreateRecord;
class CreateProjectProgressPayments extends CreateRecord {
    protected static string $resource = ProjectProgressPaymentResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('view', ['record' => $this->record]); }
}
