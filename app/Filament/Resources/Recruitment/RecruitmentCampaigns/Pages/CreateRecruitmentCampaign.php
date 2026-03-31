<?php

namespace App\Filament\Resources\Recruitment\RecruitmentCampaigns\Pages;

use App\Filament\Resources\Recruitment\RecruitmentCampaigns\RecruitmentCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateRecruitmentCampaign extends CreateRecord
{
    protected static string $resource = RecruitmentCampaignResource::class;

    protected function afterCreate(): void
    {
        $campaign = $this->record;

        // Auto-copy skills from the assigned job position
        if ($campaign->job_position_id) {
            $skills = DB::table('recruitment_job_position_skill')
                ->where('job_position_id', $campaign->job_position_id)
                ->get();

            foreach ($skills as $skill) {
                $campaign->skills()->attach($skill->recruitment_skill_id);
            }
        }
    }
}
