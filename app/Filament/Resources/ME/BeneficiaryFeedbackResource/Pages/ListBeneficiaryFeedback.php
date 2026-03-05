<?php

namespace App\Filament\Resources\ME\BeneficiaryFeedbackResource\Pages;

use App\Filament\Resources\ME\BeneficiaryFeedbackResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBeneficiaryFeedback extends ListRecords
{
    protected \Filament\Support\Enums\Width | string | null $maxContentWidth = \Filament\Support\Enums\Width::Full;
    protected static string $resource = BeneficiaryFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
