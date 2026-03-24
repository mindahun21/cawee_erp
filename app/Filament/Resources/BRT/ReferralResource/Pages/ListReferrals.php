<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\ReferralResource\Pages;

use App\Filament\Resources\BRT\ReferralResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReferrals extends ListRecords
{
    protected static string $resource = ReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
