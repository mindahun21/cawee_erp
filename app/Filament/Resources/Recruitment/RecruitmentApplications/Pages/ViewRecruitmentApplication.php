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

            Actions\Action::make('reject')
                ->label('Reject Application')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, [
                    \App\Models\Recruitment\RecruitmentApplication::STATUS_APPLIED,
                    \App\Models\Recruitment\RecruitmentApplication::STATUS_UNDER_REVIEW,
                    \App\Models\Recruitment\RecruitmentApplication::STATUS_SHORTLISTED,
                    \App\Models\Recruitment\RecruitmentApplication::STATUS_INTERVIEW_SCHEDULED,
                ]))
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->placeholder('Provide a brief reason for the candidate...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => \App\Models\Recruitment\RecruitmentApplication::STATUS_REJECTED,
                        'rejection_reason' => $data['rejection_reason'],
                    ]);
                    \Filament\Notifications\Notification::make()->title('Application rejected')->danger()->send();
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
