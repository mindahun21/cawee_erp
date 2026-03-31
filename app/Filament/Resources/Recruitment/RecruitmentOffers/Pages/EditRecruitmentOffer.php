<?php

namespace App\Filament\Resources\Recruitment\RecruitmentOffers\Pages;

use App\Filament\Resources\Recruitment\RecruitmentOffers\RecruitmentOfferResource;
use Filament\Resources\Pages\EditRecord;

class EditRecruitmentOffer extends EditRecord
{
    protected static string $resource = RecruitmentOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make(),
        ];
    }
}
