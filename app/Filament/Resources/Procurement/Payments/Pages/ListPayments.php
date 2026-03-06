<?php namespace App\Filament\Resources\Procurement\Payments\Pages;
use App\Filament\Resources\Procurement\Payments\PaymentResource;
use Filament\Actions\CreateAction; use Filament\Resources\Pages\ListRecords;
class ListPayments extends ListRecords {
    protected static string $resource = PaymentResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()->label('Schedule Payment')]; }
}
