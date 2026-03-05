<?php

namespace App\Filament\Resources\ME\BeneficiaryFeedbackResource\Pages;

use App\Filament\Resources\ME\BeneficiaryFeedbackResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBeneficiaryFeedback extends CreateRecord
{
    protected \Filament\Support\Enums\Width | string | null $maxContentWidth = \Filament\Support\Enums\Width::Full;
    protected static string $resource = BeneficiaryFeedbackResource::class;
}
