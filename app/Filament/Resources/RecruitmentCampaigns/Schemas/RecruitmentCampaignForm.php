<?php

namespace App\Filament\Resources\RecruitmentCampaigns\Schemas;

use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;

class RecruitmentCampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            // Tabs::make('RecruitmentPlanTabs')
            // ->tabs([
            // Tab::make('General Info')->schema([
            TextInput::make('campaign_code')->label('Campaign Code'),
            TextInput::make('campaign_name')->label('Campaign Name')->required(),
            // TextInput::make('plan_name')->label('Recruitment Plan')->required(),
            Select::make('recruitment_plan_id')
                ->relationship('recruitmentPlan', 'plan_name')
                ->searchable()
                ->preload(),
            // Select::make('recruitment_channel_id')
            //     ->label('Recruitment Channel')
            //     ->relationship('recruitmentChannel', 'name')
            //     ->searchable()
            //     ->preload(),

            Select::make('manager_id')
                ->label('Manager')
                ->relationship('manager', 'name')
                ->searchable()
                ->preload(),

            Select::make('follower_id')
                ->label('Follower')
                ->relationship('follower', 'name')
                ->searchable()
                ->preload(),
            Select::make('position')->label('Position')->options([
                'Software Engineer' => 'Software Engineer',
                'HR Manager' => 'HR Manager',
            ])->required(),
            TextInput::make('company')->label('Company'),
            TextInput::make('quantity')->label('Quantity to be recruited')->numeric(),
            TextInput::make('working_form')->label('Working Form'),
            TextInput::make('department')->label('Department'),
            TextInput::make('workplace')->label('Workplace'),
            TextInput::make('starting_salary_from')->label('Starting Salary From (USD)')->numeric(),
            TextInput::make('starting_salary_to')->label('Starting Salary To (USD)')->numeric(),
            TextInput::make('display_salary')->label('Display Salary on Portal')->required(),
            DatePicker::make('from_date')->label('From Date'),
            DatePicker::make('to_date')->label('To Date'),
            Textarea::make('reason')->label('Reason for Recruitment'),
            RichEditor::make('job_description')->label('Job Description')->required(),
            // Select::make('manager_id')->label('Manager')->relationship('manager', 'name'),
            // Select::make('follower_id')->label('Follower')->relationship('follower', 'name'),
            TextInput::make('meta_title')->label('Meta Title'),
            TextInput::make('meta_description')->label('Meta Description'),
            // ]),
            // Tab::make('Candidate Requirements')->schema([
            //     TextInput::make('age_from')->label('Age From')->numeric(),
            //     TextInput::make('age_to')->label('Age To')->numeric(),
            //     Select::make('gender')->label('Gender')->options([
            //         'Male' => 'Male',
            //         'Female' => 'Female',
            //         'Any' => 'Any',
            //     ]),
            TextInput::make('height')->label('Height (m) ≥')->numeric()->step(0.01),
            TextInput::make('weight')->label('Weight (kg) ≥')->numeric()->step(0.01),
            TextInput::make('literacy')->label('Literacy'),
            TextInput::make('seniority')->label('Seniority'),
            // ]),
            // Tab::make('Candidate CV')->schema([
            //     FileUpload::make('attachment')->label('Candidate CV')->disk('public')->directory('attachments'),
            // ]),
            // ]),
        ]);
    }
}
