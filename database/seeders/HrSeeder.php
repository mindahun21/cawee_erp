<?php

namespace Database\Seeders;

use App\Models\AppraisalCriterion;
use App\Models\AppraisalSection;
use App\Models\AppraisalTemplate;
use App\Models\OnboardingChecklistItem;
use Illuminate\Database\Seeder;

class HrSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedOnboardingChecklist();
        $this->seedAppraisalTemplates();
    }

    private function seedOnboardingChecklist(): void
    {
        $onboarding = [
            // Declarations and Policies to Sign
            ['title' => 'Code of Conduct for SVO Employees',           'category' => 'Document to Sign', 'phase' => 'Onboarding', 'sort_order' => 1],
            ['title' => 'Declaration of Compliance – HR Policy',        'category' => 'Document to Sign', 'phase' => 'Onboarding', 'sort_order' => 2],
            ['title' => 'E-mail Usage Policy',                          'category' => 'Document to Sign', 'phase' => 'Onboarding', 'sort_order' => 3],
            ['title' => 'Internet Usage Policy',                        'category' => 'Document to Sign', 'phase' => 'Onboarding', 'sort_order' => 4],
            ['title' => 'Statement of Commitment to Child Protection',  'category' => 'Document to Sign', 'phase' => 'Onboarding', 'sort_order' => 5],
            ['title' => 'Telephone Usage Policy',                       'category' => 'Document to Sign', 'phase' => 'Onboarding', 'sort_order' => 6],
            // Forms to Fill
            ['title' => 'Employee Life History Form',                   'category' => 'Form to Fill',     'phase' => 'Onboarding', 'sort_order' => 7],
            ['title' => 'Voluntary Orphan Support Fund Commitment Form', 'category' => 'Form to Fill',    'phase' => 'Onboarding', 'sort_order' => 8],
        ];

        $offboarding = [
            ['title' => 'Clearance Form',     'category' => 'Form to Fill', 'phase' => 'Offboarding', 'sort_order' => 1],
            ['title' => 'Exit Interview',     'category' => 'Form to Fill', 'phase' => 'Offboarding', 'sort_order' => 2],
        ];

        foreach (array_merge($onboarding, $offboarding) as $item) {
            OnboardingChecklistItem::firstOrCreate(
                ['title' => $item['title'], 'phase' => $item['phase']],
                $item + ['is_active' => true, 'requires_signature' => true]
            );
        }
    }

    private function seedAppraisalTemplates(): void
    {
        // ---------- Employee Template ----------
        $empTemplate = AppraisalTemplate::firstOrCreate(
            ['name' => 'Standard Employee Appraisal', 'type' => 'Employee'],
            ['is_active' => true, 'description' => 'Standard performance evaluation form for non-supervisory employees.']
        );

        $empSections = [
            ['title' => 'I. Individual', 'sort_order' => 1, 'criteria' => [
                ['factor_name' => 'Effort and Initiative',            'description' => 'Volunteers readily, seeks increased responsibilities, looks for and takes advantage of opportunities.'],
                ['factor_name' => 'Professional and Technical Competence', 'description' => 'Has the capacity to apply personal expertise and experiences to discharge his/her responsibility.'],
                ['factor_name' => 'Team Work',                        'description' => 'The capacity to work wherever assigned and also handle responsibilities with colleagues in one accord.'],
                ['factor_name' => 'Dependability',                    'description' => 'Follows instructions, responds to management direction, takes responsibility for own actions.'],
            ]],
            ['title' => 'II. Task Effectiveness', 'sort_order' => 2, 'criteria' => [
                ['factor_name' => 'Planning and Organizing',          'description' => 'Plans work activities and works in an organized manner.'],
                ['factor_name' => 'Quality and Quantity of Work',     'description' => 'Accuracy & efficiency of accomplishment - Performing duties with best results and quality in a given time frame/deadline.'],
                ['factor_name' => 'Priority Setting',                 'description' => 'Prioritizes work activities.'],
                ['factor_name' => 'Compliance',                       'description' => 'Complies with rules and regulations of the organization.'],
            ]],
            ['title' => 'III. Interpersonal', 'sort_order' => 3, 'criteria' => [
                ['factor_name' => 'Written Communication',            'description' => 'Expresses ideas and thoughts in written form.'],
                ['factor_name' => 'Coordination / Collaboration',     'description' => 'Offers assistance and support to coworkers.'],
            ]],
        ];

        $this->createSections($empTemplate, $empSections);

        // ---------- Supervisor Template ----------
        $supTemplate = AppraisalTemplate::firstOrCreate(
            ['name' => 'Standard Supervisor Appraisal', 'type' => 'Supervisor'],
            ['is_active' => true, 'description' => 'Performance evaluation form for supervisory staff.']
        );

        $supSections = [
            ['title' => 'I. Individual', 'sort_order' => 1, 'criteria' => [
                ['factor_name' => 'Effort and Initiative',            'description' => 'Volunteers readily, seeks increased responsibilities, looks for and takes advantage of opportunities.'],
                ['factor_name' => 'Professional and Technical Competence', 'description' => 'Has the capacity to apply personal expertise and experiences to discharge his/her responsibility.'],
                ['factor_name' => 'Team Work',                        'description' => 'The capacity to work wherever assigned and also handle responsibilities with colleagues in one accord.'],
                ['factor_name' => 'Dependability',                    'description' => 'Follows instructions, responds to management direction, takes responsibility for own actions.'],
            ]],
            ['title' => 'II. Task Effectiveness', 'sort_order' => 2, 'criteria' => [
                ['factor_name' => 'Planning and Organizing',          'description' => 'Plans work activities and works in an organized manner.'],
                ['factor_name' => 'Quality and Quantity of Work',     'description' => 'Accuracy & efficiency of accomplishment.'],
                ['factor_name' => 'Priority Setting',                 'description' => 'Prioritizes work activities.'],
                ['factor_name' => 'Compliance',                       'description' => 'Complies with rules and regulations of the organization.'],
                ['factor_name' => 'Problem Solving and Decision Making', 'description' => 'Identifies problems in a timely manner, develops alternative solutions, resolves problems in early stages.'],
                ['factor_name' => 'Attendance',                       'description' => 'On time availability, loyalty and commitments in utilizing working hours.'],
            ]],
            ['title' => 'III. Interpersonal', 'sort_order' => 3, 'criteria' => [
                ['factor_name' => 'Interpersonal Communication',      'description' => 'Does healthy communication with subordinates, has non-abusiveness character.'],
                ['factor_name' => 'Written Communication',            'description' => 'Expresses ideas and thoughts in written form.'],
                ['factor_name' => 'Coordination / Collaboration',     'description' => 'Offers assistance and support to coworkers.'],
                ['factor_name' => 'Supervisory Control',              'description' => 'Controls and supervises subordinates effectively.'],
            ]],
            ['title' => 'IV. Leadership', 'sort_order' => 4, 'criteria' => [
                ['factor_name' => 'Coaching',        'description' => 'Effectively trains subordinates.'],
                ['factor_name' => 'Empowering',      'description' => 'Carries out effective delegation.'],
                ['factor_name' => 'Modeling',        'description' => 'Leads by example.'],
                ['factor_name' => 'Team Building',   'description' => 'Contributes to building a positive team spirit among employees.'],
                ['factor_name' => 'Self-Development', 'description' => 'Ready to learn, undertakes self-development activities.'],
            ]],
        ];

        $this->createSections($supTemplate, $supSections);
    }

    private function createSections(AppraisalTemplate $template, array $sections): void
    {
        foreach ($sections as $sectionData) {
            $criteria = $sectionData['criteria'];
            unset($sectionData['criteria']);

            $section = AppraisalSection::firstOrCreate(
                ['template_id' => $template->id, 'title' => $sectionData['title']],
                $sectionData
            );

            foreach ($criteria as $i => $criterion) {
                AppraisalCriterion::firstOrCreate(
                    ['section_id' => $section->id, 'factor_name' => $criterion['factor_name']],
                    $criterion + ['sort_order' => $i + 1, 'max_score' => 5, 'weight' => 1.0, 'is_active' => true]
                );
            }
        }
    }
}
