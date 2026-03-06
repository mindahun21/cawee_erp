<?php namespace App\Filament\Resources\Procurement\Payments\Pages;
use App\Filament\Resources\Procurement\Payments\PaymentResource;
use Filament\Resources\Pages\CreateRecord;
class CreatePayment extends CreateRecord {
    protected static string $resource = PaymentResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
