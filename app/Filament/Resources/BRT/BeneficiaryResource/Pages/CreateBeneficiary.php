<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\BeneficiaryResource\Pages;

use App\Filament\Resources\BRT\BeneficiaryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBeneficiary extends CreateRecord
{
    protected static string $resource = BeneficiaryResource::class;
}
