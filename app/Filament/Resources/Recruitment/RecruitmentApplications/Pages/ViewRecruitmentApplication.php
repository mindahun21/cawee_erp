<?php

namespace App\Filament\Resources\Recruitment\RecruitmentApplications\Pages;

use App\Filament\Resources\Recruitment\RecruitmentApplications\RecruitmentApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewRecruitmentApplication extends ViewRecord
{
    protected static string $resource = RecruitmentApplicationResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getHeading(): string
    {
        $candidate = $this->record->candidate;
        $name = trim(($candidate?->first_name ?? '') . ' ' . ($candidate?->last_name ?? ''));

        return 'Application: ' . ($name ?: $this->record->getKey());
    }

    public function getSubheading(): ?string
    {
        return $this->record->campaign?->title
            ? 'Campaign: ' . $this->record->campaign->title . ' · Status: ' . ucfirst(str_replace('_', ' ', $this->record->status ?? ''))
            : null;
    }
}
