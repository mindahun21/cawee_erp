<?php

namespace App\Services\Recruitment;

use App\Models\Recruitment\RecruitmentChannel;
use App\Models\Recruitment\RecruitmentSkill;
use Filament\Forms\Components;

class ChannelFormBuilderService
{
    public static function buildFormSchemaBuilder(): Components\Builder
    {
        return Components\Builder::make('form_schema')
            ->label('Form schema')
            ->addActionLabel('Add field')
            ->reorderable()
            ->collapsible()
            ->collapsed()
            ->cloneable(false)
            ->extraAttributes(['class' => 'channel-form-builder'])
            ->blocks([
                // ── HEADER ──────────────────────────────────────────────
                Components\Builder\Block::make('header')
                    ->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Header')
                    ->icon('heroicon-o-pencil')
                    ->schema([
                        Components\TextInput::make('label')
                            ->label('Label')
                            ->default('Header')
                            ->required(),
                        Components\TextInput::make('class')
                            ->label('Class')
                            ->placeholder('space separated classes'),
                        Components\Hidden::make('type')->default('layout_header'),
                        Components\Hidden::make('name')->default(null),
                        Components\Hidden::make('db_column')->default(null),
                        self::closeHint(),
                    ]),

                // ── PARAGRAPH ───────────────────────────────────────────
                Components\Builder\Block::make('paragraph')
                    ->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Paragraph')
                    ->icon('heroicon-o-pencil')
                    ->schema([
                        Components\Textarea::make('content')
                            ->label('Content')
                            ->default('Paragraph')
                            ->rows(3),
                        Components\TextInput::make('class')
                            ->label('Class')
                            ->placeholder('space separated classes'),
                        Components\Hidden::make('type')->default('layout_paragraph'),
                        Components\Hidden::make('name')->default(null),
                        Components\Hidden::make('db_column')->default(null),
                        self::closeHint(),
                    ]),

                // ── FILE UPLOAD ──────────────────────────────────────────
                Components\Builder\Block::make('resume_path')
                    ->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'File Upload')
                    ->icon('heroicon-o-pencil')
                    ->schema([
                        Components\Toggle::make('required')->label('Required')->default(false),
                        Components\TextInput::make('label')->label('Label')->default('File Upload'),
                        Components\TextInput::make('help_text')->label('Help Text'),
                        Components\TextInput::make('placeholder')->label('Placeholder'),
                        Components\TextInput::make('class')->label('Class')->default('form-control'),
                        Components\TextInput::make('name')
                            ->label('Name')
                            ->default('file-input')
                            ->disabled()
                            ->dehydrated(),
                        Components\Toggle::make('multiple_files')
                            ->label('Allow users to upload multiple files')
                            ->default(false),
                        Components\Hidden::make('type')->default('file'),
                        Components\Hidden::make('db_column')->default('resume_path'),
                        self::closeHint(),
                    ]),

                // ── TEXT FIELDS ────────────────────
                Components\Builder\Block::make('first_name')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'First name')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('first_name', 'First name', 'text')),
                Components\Builder\Block::make('last_name')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Last name')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('last_name', 'Last name', 'text')),
                Components\Builder\Block::make('birthday')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Birthday')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('birthday', 'Birthday', 'date')),
                Components\Builder\Block::make('desired_salary')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Desired salary')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('desired_salary', 'Desired salary', 'number')),
                Components\Builder\Block::make('birthplace')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Birthplace')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('birthplace', 'Birthplace', 'text')),
                Components\Builder\Block::make('home_town')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Home town')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('home_town', 'Home town', 'text')),
                Components\Builder\Block::make('identification')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Identification')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('identification', 'Identification', 'text')),
                Components\Builder\Block::make('place_of_issue')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Place of issue')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('place_of_issue', 'Place of issue', 'text')),
                Components\Builder\Block::make('nation')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Nation')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('nation', 'Nation', 'text')),
                Components\Builder\Block::make('religion')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Religion')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('religion', 'Religion', 'text')),
                Components\Builder\Block::make('height_m')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Height(m)')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('height_m', 'Height(m)', 'number')),
                Components\Builder\Block::make('weight_kg')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Weight(kg)')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('weight_kg', 'Weight(kg)', 'number')),
                Components\Builder\Block::make('days_for_identity')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Days for identity')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('days_for_identity', 'Days for identity', 'date')),

                // Contact
                Components\Builder\Block::make('email')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Email Address')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('email', 'Email Address', 'text')),
                Components\Builder\Block::make('phone')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Phone')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('phone', 'Phone', 'text')),
                Components\Builder\Block::make('resident')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Resident')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('resident', 'Resident', 'text')),
                Components\Builder\Block::make('zip_code')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Zip Code')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('zip_code', 'Zip Code', 'text')),
                Components\Builder\Block::make('current_accommodation')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Current accommodation')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('current_accommodation', 'Current accommodation', 'text')),
                Components\Builder\Block::make('skype')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Skype')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('skype', 'Skype', 'text')),
                Components\Builder\Block::make('facebook')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Facebook')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('facebook', 'Facebook', 'text')),
                Components\Builder\Block::make('linkedin_url')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Linkedin')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('linkedin_url', 'Linkedin', 'text')),
                Components\Builder\Block::make('introduce_yourself')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Introduce yourself')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('introduce_yourself', 'Introduce yourself', 'text')),
                Components\Builder\Block::make('interests')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Interests')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('interests', 'Interests', 'text')),

                // Work history
                Components\Builder\Block::make('company')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Company')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('company', 'Company', 'text')),
                Components\Builder\Block::make('role_in_old_company')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Role in the old company')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('role_in_old_company', 'Role in the old company', 'text')),
                Components\Builder\Block::make('contact_person')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Contact person')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('contact_person', 'Contact person', 'text')),
                Components\Builder\Block::make('salary')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Salary')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('salary', 'Salary', 'number')),
                Components\Builder\Block::make('reason_for_leaving_job')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Reason for leaving job')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('reason_for_leaving_job', 'Reason for leaving job', 'text')),
                Components\Builder\Block::make('job_description')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Job description')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('job_description', 'Job description', 'text')),

                // Education
                Components\Builder\Block::make('diploma')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Diploma')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('diploma', 'Diploma', 'text')),
                Components\Builder\Block::make('training_places')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Training places')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('training_places', 'Training places', 'text')),
                Components\Builder\Block::make('specialized')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Specialized')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('specialized', 'Specialized', 'text')),
                Components\Builder\Block::make('percentage')->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Percentage')->icon('heroicon-o-pencil')->schema(self::textFieldSchema('percentage', 'Percentage', 'number')),

                // SELECT FIELDS
                Components\Builder\Block::make('gender')
                    ->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Gender')
                    ->icon('heroicon-o-pencil')
                    ->schema(self::selectFieldSchema('gender', 'Gender', 'gender', [
                        ['label' => '',       'value' => ''],
                        ['label' => 'Male',   'value' => 'male'],
                        ['label' => 'Female', 'value' => 'female'],
                    ])),

                Components\Builder\Block::make('marital_status')
                    ->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Marital status')
                    ->icon('heroicon-o-pencil')
                    ->schema(self::selectFieldSchema('marital_status', 'Marital status', 'marital_status', [
                        ['label' => '',        'value' => ''],
                        ['label' => 'Single',  'value' => 'single'],
                        ['label' => 'Married', 'value' => 'married'],
                    ])),

                Components\Builder\Block::make('nationality')
                    ->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Nationality (Cote divoire)')
                    ->icon('heroicon-o-pencil')
                    ->schema(self::selectFieldSchema('nationality', 'Nationality', 'nationality', RecruitmentChannel::countriesOptions())),

                Components\Builder\Block::make('seniority')
                    ->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Seniority')
                    ->icon('heroicon-o-pencil')
                    ->schema(self::selectFieldSchema('seniority', 'Seniority', 'year_experience', [
                        ['label' => 'No seniority yet',   'value' => 'no_experience_yet'],
                        ['label' => 'Less than one year', 'value' => 'less_than_1_year'],
                        ['label' => 'One year',           'value' => '1_year'],
                        ['label' => 'Two years',          'value' => '2_years'],
                        ['label' => 'Three years',        'value' => '3_years'],
                        ['label' => 'Four years',         'value' => '4_years'],
                        ['label' => 'Five years',         'value' => '5_years'],
                        ['label' => 'Over five years',    'value' => 'over_5_years'],
                    ])),

                // SKILLS
                Components\Builder\Block::make('skills')
                    ->label(fn (?array $state): string => ($state['label'] ?? null) ?: 'Skill')
                    ->icon('heroicon-o-pencil')
                    ->schema(self::skillSelectFieldSchema()),
            ]);
    }

    private static function closeHint(): Components\Placeholder
    {
        return Components\Placeholder::make('close_hint')
            ->hiddenLabel()
            ->content(new \Illuminate\Support\HtmlString(
                '<div class="text-right mt-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm cursor-pointer hover:text-gray-900 dark:hover:text-gray-200"
                          onclick="this.closest(\'[data-builder-block]\').querySelector(\'[data-collapse]\').click()">
                        Close
                    </span>
                </div>'
            ));
    }

    private static function textFieldSchema(string $fieldKey, string $defaultLabel, string $fieldType): array
    {
        $field = RecruitmentChannel::availableFields()[$fieldKey];

        return [
            Components\Toggle::make('required')->label('Required')->default(false),
            Components\TextInput::make('label')->label('Label')->default($defaultLabel)->required(),
            Components\TextInput::make('help_text')->label('Help Text'),
            Components\TextInput::make('placeholder')->label('Placeholder'),
            Components\TextInput::make('class')->label('Class')->default('form-control'),
            Components\TextInput::make('name')
                ->label('Name')
                ->default($field['name'])
                ->disabled()
                ->dehydrated(),
            Components\TextInput::make('value')->label('Value'),
            Components\Hidden::make('type')->default($fieldType),
            Components\Hidden::make('field_key')->default($fieldKey),
            Components\Hidden::make('db_column')->default($field['db_column']),
            self::closeHint(),
        ];
    }

    private static function selectFieldSchema(string $fieldKey, string $defaultLabel, string $nameKey, array $defaultOptions): array
    {
        $field = RecruitmentChannel::availableFields()[$fieldKey];

        return [
            Components\Toggle::make('required')->label('Required')->default(false),
            Components\TextInput::make('label')->label('Label')->default($defaultLabel)->required(),
            Components\TextInput::make('help_text')->label('Help Text'),
            Components\TextInput::make('placeholder')->label('Placeholder'),
            Components\TextInput::make('class')->label('Class')->default('form-control'),
            Components\TextInput::make('name')
                ->label('Name')
                ->default($nameKey)
                ->disabled()
                ->dehydrated(),
            Components\Toggle::make('allow_multiple')->label('Allow Multiple Selections')->default(false),
            Components\Repeater::make('options')
                ->label('Options')
                ->schema([
                    Components\TextInput::make('label')->label('Label'),
                    Components\TextInput::make('value')->label('Value'),
                ])
                ->default($defaultOptions)
                ->columns(2)
                ->reorderable(false)
                ->addActionLabel('Add option'),
            Components\Hidden::make('type')->default('select'),
            Components\Hidden::make('field_key')->default($fieldKey),
            Components\Hidden::make('db_column')->default($field['db_column']),
            self::closeHint(),
        ];
    }

    private static function skillSelectFieldSchema(): array
    {
        $skillOptions = RecruitmentSkill::orderBy('name')
            ->get()
            ->map(fn($s) => ['label' => $s->name, 'value' => (string) $s->id])
            ->toArray();

        if (empty($skillOptions)) {
            $skillOptions = [
                ['label' => 'Option 1', 'value' => 'option-1'],
                ['label' => 'Option 2', 'value' => 'option-2'],
                ['label' => 'Option 3', 'value' => 'option-3'],
            ];
        }

        return [
            Components\Toggle::make('required')->label('Required')->default(false),
            Components\TextInput::make('label')->label('Label')->default('Skill')->required(),
            Components\TextInput::make('help_text')->label('Help Text'),
            Components\TextInput::make('placeholder')->label('Placeholder'),
            Components\TextInput::make('class')->label('Class')->default('form-control'),
            Components\TextInput::make('name')
                ->label('Name')
                ->default('skill')
                ->disabled()
                ->dehydrated(),
            Components\Toggle::make('allow_multiple')->label('Allow Multiple Selections')->default(true),
            Components\Repeater::make('options')
                ->label('Options')
                ->schema([
                    Components\TextInput::make('label')->label('Label'),
                    Components\TextInput::make('value')->label('Value'),
                ])
                ->default($skillOptions)
                ->columns(2)
                ->reorderable(false)
                ->addActionLabel('Add option'),
            Components\Hidden::make('type')->default('skill_select'),
            Components\Hidden::make('field_key')->default('skills'),
            Components\Hidden::make('db_column')->default('skills'),
            self::closeHint(),
        ];
    }
}
