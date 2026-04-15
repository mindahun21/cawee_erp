<?php

namespace App\Filament\Resources\Recruitment\RecruitmentApplications\Pages;

use App\Filament\Resources\Recruitment\RecruitmentApplications\RecruitmentApplicationResource;
use App\Models\Recruitment\RecruitmentApplication;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;

class KanbanRecruitmentApplications extends Page
{
    protected static string $resource = RecruitmentApplicationResource::class;
    protected string $view = 'filament.resources.recruitment.applications-kanban';
    protected static ?string $title = 'Applications Kanban';

    public function getStatuses(): array
    {
        return [
            RecruitmentApplication::STATUS_APPLIED => ['label' => 'Applied', 'color' => 'bg-gray-100 dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-200'],
            RecruitmentApplication::STATUS_UNDER_REVIEW => ['label' => 'Under Review', 'color' => 'bg-warning-100 dark:bg-warning-900 border-warning-300 dark:border-warning-700 text-warning-800 dark:text-warning-200'],
            RecruitmentApplication::STATUS_SHORTLISTED => ['label' => 'Shortlisted', 'color' => 'bg-info-100 dark:bg-info-900 border-info-300 dark:border-info-700 text-info-800 dark:text-info-200'],
            RecruitmentApplication::STATUS_INTERVIEW_SCHEDULED => ['label' => 'Interview Scheduled', 'color' => 'bg-success-100 dark:bg-success-900 border-success-300 dark:border-success-700 text-success-800 dark:text-success-200'],
            RecruitmentApplication::STATUS_INTERVIEWED => ['label' => 'Interviewed', 'color' => 'bg-primary-50 dark:bg-primary-950 border-primary-200 dark:border-primary-800 text-primary-700 dark:text-primary-300'],
            RecruitmentApplication::STATUS_SELECTED => ['label' => 'Selected', 'color' => 'bg-success-50 dark:bg-success-950 border-success-200 dark:border-success-800 text-success-700 dark:text-success-300'],
            RecruitmentApplication::STATUS_WAITLISTED => ['label' => 'Waitlisted', 'color' => 'bg-amber-50 dark:bg-amber-950 border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300'],
            RecruitmentApplication::STATUS_OFFER_PENDING => ['label' => 'Offer Pending', 'color' => 'bg-primary-100 dark:bg-primary-900 border-primary-300 dark:border-primary-700 text-primary-800 dark:text-primary-200'],
            RecruitmentApplication::STATUS_OFFER_ACCEPTED => ['label' => 'Offer Accepted', 'color' => 'bg-success-200 dark:bg-success-800 border-success-400 dark:border-success-600 text-success-900 dark:text-success-100'],
            RecruitmentApplication::STATUS_OFFER_DECLINED => ['label' => 'Offer Declined', 'color' => 'bg-danger-100 dark:bg-danger-900 border-danger-300 dark:border-danger-700 text-danger-800 dark:text-danger-200'],
            RecruitmentApplication::STATUS_HIRED => ['label' => 'Hired', 'color' => 'bg-success-300 dark:bg-success-700 border-success-500 text-success-900 dark:text-white'],
            RecruitmentApplication::STATUS_REJECTED => ['label' => 'Rejected', 'color' => 'bg-danger-50 dark:bg-danger-950 border-danger-200 dark:border-danger-800 text-danger-700 dark:text-danger-400'],
            RecruitmentApplication::STATUS_WITHDRAWN => ['label' => 'Withdrawn', 'color' => 'bg-gray-200 dark:bg-gray-700 border-gray-400 text-gray-900 dark:text-gray-100'],
        ];
    }

    public function getRecords()
    {
        $applications = RecruitmentApplication::with(['candidate', 'campaign'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        $grouped = [];
        foreach ($this->getStatuses() as $status => $details) {
            $grouped[$status] = $applications->where('status', $status)->values();
        }
        return $grouped;
    }

    public function updateApplicationStatus($id, $newStatus)
    {
        $app = RecruitmentApplication::find($id);
        if ($app && array_key_exists($newStatus, $this->getStatuses())) {
            $app->update(['status' => $newStatus]);
            \Filament\Notifications\Notification::make()
                ->title('Status updated')
                ->success()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list_view')
                ->label('Switch to List')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->url(RecruitmentApplicationResource::getUrl('index')),
            CreateAction::make(),
        ];
    }
}
