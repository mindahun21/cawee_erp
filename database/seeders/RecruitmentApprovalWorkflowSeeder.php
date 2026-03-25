<?php

namespace Database\Seeders;

use App\Models\Recruitment\RecruitmentApprovalWorkflow;
use Illuminate\Database\Seeder;

class RecruitmentApprovalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $workflow = RecruitmentApprovalWorkflow::updateOrCreate(
            ['document_type' => 'recruitment_plan', 'name' => 'Standard Recruitment Plan Approval'],
            [
                'description' => 'Default 2-stage approval for new recruitment plans.',
                'is_active' => true,
            ]
        );

        $workflow->stages()->delete();

        $workflow->stages()->createMany([
            [
                'stage_name' => 'HR Manager Review',
                'stage_order' => 1,
                'required_role' => 'hr_manager',
                'can_reject' => true,
            ],
            [
                'stage_name' => 'HR Director Approval',
                'stage_order' => 2,
                'required_role' => 'hr_director',
                'can_reject' => true,
            ],
        ]);
    }
}
