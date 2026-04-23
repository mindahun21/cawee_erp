<?php

namespace App\Filament\Resources\Recruitment\RecruitmentOffers\Pages;

use App\Filament\Resources\Recruitment\RecruitmentOffers\RecruitmentOfferResource;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentOffers extends ListRecords
{
    protected static string $resource = RecruitmentOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
