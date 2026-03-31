<?php

namespace App\Filament\Resources\Donations\PledgeResource\Pages;

use App\Filament\Resources\Donations\PledgeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPledge extends EditRecord
{
    protected static string $resource = PledgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
