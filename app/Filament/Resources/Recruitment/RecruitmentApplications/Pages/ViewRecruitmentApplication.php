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

            Actions\Action::make('mark_under_review')
                ->label('Mark Under Review')
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->visible(fn () => $this->record->status === \App\Models\Recruitment\RecruitmentApplication::STATUS_APPLIED)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => \App\Models\Recruitment\RecruitmentApplication::STATUS_UNDER_REVIEW,
                        'reviewed_by' => auth()->id(),
                    ]);
                    \Filament\Notifications\Notification::make()->title('Application marked as under review')->success()->send();
                }),

            Actions\Action::make('shortlist')
                ->label('Shortlist')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === \App\Models\Recruitment\RecruitmentApplication::STATUS_UNDER_REVIEW)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => \App\Models\Recruitment\RecruitmentApplication::STATUS_SHORTLISTED,
                        'shortlisted_by' => auth()->id(),
                    ]);
                    \Filament\Notifications\Notification::make()->title('Application shortlisted')->success()->send();
                }),
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
