<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\RecruitmentCampaign;

class JobDetails extends Page
{
    protected string $view = 'filament.pages.job-details';
    protected static bool $shouldRegisterNavigation = false;

    public $job;

    // Load job when page is opened
    public function mount($id)
    {
        $this->job = RecruitmentCampaign::findOrFail($id);
    }
}
