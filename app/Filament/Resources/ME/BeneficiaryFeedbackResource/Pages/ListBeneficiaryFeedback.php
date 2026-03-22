<?php

declare(strict_types=1);

namespace App\Filament\Resources\ME\BeneficiaryFeedbackResource\Pages;

use App\Filament\Resources\ME\BeneficiaryFeedbackResource;
use App\Models\ME\MeBeneficiaryFeedback;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class ListBeneficiaryFeedback extends ListRecords
{
    protected \Filament\Support\Enums\Width | string | null $maxContentWidth = Width::Full;

    protected static string $resource = BeneficiaryFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Record Feedback')
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BeneficiaryFeedbackResource\Widgets\BeneficiaryFeedbackStatsOverview::class,
        ];
    }
}
