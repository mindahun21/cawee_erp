<?php

namespace App\Filament\Resources\ME\BeneficiaryFeedbackResource\Pages;

use App\Filament\Resources\ME\BeneficiaryFeedbackResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBeneficiaryFeedback extends EditRecord
{
    protected \Filament\Support\Enums\Width | string | null $maxContentWidth = \Filament\Support\Enums\Width::Full;
    protected static string $resource = BeneficiaryFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
