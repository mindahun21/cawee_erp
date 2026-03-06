<?php

namespace App\Filament\Resources\RecruitmentPlans\Schemas;

use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Schemas\Schema;

class RecruitmentPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            // General Info
            TextInput::make('plan_name')->label('Plan Name')->required(),
            Select::make('position')
                ->label('Position')
                ->options([
                    'Software Engineer' => 'Software Engineer',
                    'HR Manager' => 'HR Manager',
                    'Accountant' => 'Accountant',
                    'Designer' => 'Designer',
                ])
                ->searchable()
                ->required(),
            Select::make('department')
                ->label('Department')
                ->options([
                    'IT' => 'IT',
                    'HR' => 'HR',
                    'Finance' => 'Finance',
                    'Design' => 'Design',
                ])
                ->searchable()
                ->required(),
            TextInput::make('quantity')->label('Quantity to be Recruited')->numeric(),
            TextInput::make('working_form')->label('Working Form'),
            TextInput::make('workplace')->label('Workplace'),
            TextInput::make('starting_salary_from')->label('Starting Salary (USD)')->numeric(),
            TextInput::make('starting_salary_to')->label('Starting Salary To (USD)')->numeric(),
            DatePicker::make('from_date')->label('From Date'),
            DatePicker::make('to_date')->label('To Date'),
            Textarea::make('reason')->label('Reason for Recruitment'),
            RichEditor::make('job_description')->label('Job Description')->required(),
            Select::make('approver_id')->label('Approver')->relationship('approver', 'name')->required(),

            // Candidate Requirements
            TextInput::make('age_from')->label('Age From')->numeric(),
            TextInput::make('age_to')->label('Age To')->numeric(),
            Select::make('gender')
                ->label('Gender')
                ->options([
                    'Male' => 'Male',
                    'Female' => 'Female',
                    'Any' => 'Any',
                ]),
            TextInput::make('height')->label('Height (m) ≥')->numeric()->step(0.01),
            TextInput::make('weight')->label('Weight (kg) ≥')->numeric()->step(0.01),
            TextInput::make('literacy')->label('Literacy'),
            TextInput::make('seniority')->label('Seniority'),
            FileUpload::make('attachment')
                ->label('Attachment')
                ->disk('public')
                ->directory('attachments')
                // ->disabled(fn () => !auth()->user()->isHR()),
        ]);
    }
}