<?php
namespace App\Filament\Resources\Finance\Perdiem\Pages;
use App\Filament\Resources\Finance\Perdiem\PerdiemRequestResource;
use Filament\Resources\Pages\EditRecord;
class EditPerdiemRequests extends EditRecord {
    protected static string $resource = PerdiemRequestResource::class;
    protected function getRedirectUrl(): string {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
