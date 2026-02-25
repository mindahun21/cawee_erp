<?php

namespace App\Filament\Resources\HR\SalaryGrades\Pages;

use App\Filament\Resources\HR\SalaryGrades\SalaryGradeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSalaryGrades extends ManageRecords
{
    protected static string $resource = SalaryGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
