<?php

namespace App\Filament\Resources\Recruitment\RecruitmentOffers\Pages;

use App\Filament\Resources\Recruitment\RecruitmentOffers\RecruitmentOfferResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecruitmentOffer extends CreateRecord
{
    protected static string $resource = RecruitmentOfferResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['issued_by'] = auth()->id();
        $data['status']    = \App\Models\Recruitment\RecruitmentOffer::STATUS_DRAFT;
        return $data;
    }
}
