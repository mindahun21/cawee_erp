<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class RecruitmentChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'status', 'responsible_person_id',
        'language', 'submit_button_text', 'success_message',
        'notify_on_submission', 'notification_target',
        'notification_person_id', 'form_schema', 'is_active',
    ];

    protected $casts = [
        'form_schema'          => 'array',
        'notify_on_submission' => 'boolean',
        'is_active'            => 'boolean',
    ];

    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'responsible_person_id');
    }

    public function notificationPerson(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'notification_person_id');
    }

    /**
     * The complete list of fields HR can add to the form builder.
     * This is the ONLY source of truth for available fields.
     */
    public static function availableFields(): array
    {
        return [
            // Layout elements
            'header'    => ['label' => 'Header',    'type' => 'layout_header',    'db_column' => null,  'name' => null,             'repeatable' => true],
            'paragraph' => ['label' => 'Paragraph', 'type' => 'layout_paragraph', 'db_column' => null,  'name' => null,             'repeatable' => true],

            // File upload
            'resume_path' => ['label' => 'File Upload', 'type' => 'file', 'db_column' => 'resume_path', 'name' => 'file-input', 'repeatable' => false],

            // Text / identity
            'first_name'         => ['label' => 'First name',    'type' => 'text',   'db_column' => 'first_name',    'name' => 'first_name',    'repeatable' => false],
            'last_name'          => ['label' => 'Last name',     'type' => 'text',   'db_column' => 'last_name',     'name' => 'last_name',     'repeatable' => false],
            'candidate_code'     => ['label' => 'Candidate code','type' => 'text',   'db_column' => 'candidate_code','name' => 'candidate_code','repeatable' => false],
            'birthday'           => ['label' => 'Birthday',      'type' => 'date',   'db_column' => 'birthday',      'name' => 'birthday',      'repeatable' => false],
            'desired_salary'     => ['label' => 'Desired salary','type' => 'number', 'db_column' => 'desired_salary','name' => 'desired_salary','repeatable' => false],
            'birthplace'         => ['label' => 'Birthplace',    'type' => 'text',   'db_column' => 'birthplace',    'name' => 'birthplace',    'repeatable' => false],
            'home_town'          => ['label' => 'Home town',     'type' => 'text',   'db_column' => 'home_town',     'name' => 'home_town',     'repeatable' => false],
            'identification'     => ['label' => 'Identification','type' => 'text',   'db_column' => 'identification','name' => 'identification','repeatable' => false],
            'place_of_issue'     => ['label' => 'Place of issue','type' => 'text',   'db_column' => 'place_of_issue','name' => 'place_of_issue','repeatable' => false],
            'nation'             => ['label' => 'Nation',        'type' => 'text',   'db_column' => 'nation',        'name' => 'nation',        'repeatable' => false],
            'religion'           => ['label' => 'Religion',      'type' => 'text',   'db_column' => 'religion',      'name' => 'religion',      'repeatable' => false],
            'height_m'           => ['label' => 'Height(m)',     'type' => 'number', 'db_column' => 'height_m',      'name' => 'height_m',      'repeatable' => false],
            'weight_kg'          => ['label' => 'Weight(kg)',    'type' => 'number', 'db_column' => 'weight_kg',     'name' => 'weight_kg',     'repeatable' => false],
            'days_for_identity'  => ['label' => 'Days for identity','type' => 'date','db_column' => 'days_for_identity','name' => 'days_for_identity','repeatable' => false],

            // Contact
            'email'                 => ['label' => 'Email Address',        'type' => 'text',   'db_column' => 'email',                 'name' => 'email',                 'repeatable' => false],
            'phone'                 => ['label' => 'Phone',                'type' => 'text',   'db_column' => 'phone',                 'name' => 'phone',                 'repeatable' => false],
            'resident'              => ['label' => 'Resident',             'type' => 'text',   'db_column' => 'resident',              'name' => 'resident',              'repeatable' => false],
            'zip_code'              => ['label' => 'Zip Code',             'type' => 'text',   'db_column' => 'zip_code',              'name' => 'zip_code',              'repeatable' => false],
            'current_accommodation' => ['label' => 'Current accommodation','type' => 'text',   'db_column' => 'current_accommodation', 'name' => 'current_accommodation', 'repeatable' => false],
            'skype'                 => ['label' => 'Skype',                'type' => 'text',   'db_column' => 'skype',                 'name' => 'skype',                 'repeatable' => false],
            'facebook'              => ['label' => 'Facebook',             'type' => 'text',   'db_column' => 'facebook',              'name' => 'facebook',              'repeatable' => false],
            'linkedin_url'          => ['label' => 'Linkedin',             'type' => 'text',   'db_column' => 'linkedin_url',          'name' => 'linkedin_url',          'repeatable' => false],
            'introduce_yourself'    => ['label' => 'Introduce yourself',   'type' => 'text',   'db_column' => 'introduce_yourself',    'name' => 'introduce_yourself',    'repeatable' => false],
            'interests'             => ['label' => 'Interests',            'type' => 'text',   'db_column' => 'interests',             'name' => 'interests',             'repeatable' => false],

            // Work history
            'company'                => ['label' => 'Company',               'type' => 'text',   'db_column' => 'seniority_company',           'name' => 'company',               'repeatable' => false],
            'role_in_old_company'    => ['label' => 'Role in the old company','type' => 'text',  'db_column' => 'seniority_position',          'name' => 'role_in_old_company',   'repeatable' => false],
            'contact_person'         => ['label' => 'Contact person',        'type' => 'text',   'db_column' => 'seniority_contact_person',    'name' => 'contact_person',        'repeatable' => false],
            'salary'                 => ['label' => 'Salary',                'type' => 'number', 'db_column' => 'seniority_salary',            'name' => 'salary',                'repeatable' => false],
            'reason_for_leaving_job' => ['label' => 'Reason for leaving job','type' => 'text',   'db_column' => 'seniority_reason_for_leaving','name' => 'reason_for_leaving_job','repeatable' => false],
            'job_description'        => ['label' => 'Job description',       'type' => 'text',   'db_column' => 'seniority_job_description',   'name' => 'job_description',       'repeatable' => false],

            // Education
            'diploma'         => ['label' => 'Diploma',         'type' => 'text',   'db_column' => 'literacy_diploma',         'name' => 'diploma',         'repeatable' => false],
            'training_places' => ['label' => 'Training places', 'type' => 'text',   'db_column' => 'literacy_training_places', 'name' => 'training_places', 'repeatable' => false],
            'specialized'     => ['label' => 'Specialized',     'type' => 'text',   'db_column' => 'literacy_specialized',     'name' => 'specialized',     'repeatable' => false],
            'percentage'      => ['label' => 'Percentage',      'type' => 'number', 'db_column' => 'literacy_percentage',      'name' => 'percentage',      'repeatable' => false],

            // Select fields
            'gender' => [
                'label' => 'Gender', 'type' => 'select',
                'db_column' => 'gender', 'name' => 'gender', 'repeatable' => false,
                'default_options' => [
                    ['label' => '',       'value' => ''],
                    ['label' => 'Male',   'value' => 'male'],
                    ['label' => 'Female', 'value' => 'female'],
                ],
            ],

            'marital_status' => [
                'label' => 'Marital status', 'type' => 'select',
                'db_column' => 'marital_status', 'name' => 'marital_status', 'repeatable' => false,
                'default_options' => [
                    ['label' => '',        'value' => ''],
                    ['label' => 'Single',  'value' => 'single'],
                    ['label' => 'Married', 'value' => 'married'],
                ],
            ],

            'nationality' => [
                'label' => 'Nationality', 'type' => 'select', // The UI screenshot uses 'Nationality' in the edit panel, although list uses 'Cote divoire...'
                'db_column' => 'nationality', 'name' => 'nationality', 'repeatable' => false,
                'default_options' => self::countriesOptions(),
            ],

            'seniority' => [
                'label' => 'Seniority', 'type' => 'select',
                'db_column' => 'seniority',
                'name' => 'year_experience',
                'repeatable' => false,
                'default_options' => [
                    ['label' => 'No seniority yet',   'value' => 'no_experience_yet'],
                    ['label' => 'Less than one year', 'value' => 'less_than_1_year'],
                    ['label' => 'One year',           'value' => '1_year'],
                    ['label' => 'Two years',          'value' => '2_years'],
                    ['label' => 'Three years',        'value' => '3_years'],
                    ['label' => 'Four years',         'value' => '4_years'],
                    ['label' => 'Five years',         'value' => '5_years'],
                    ['label' => 'Over five years',    'value' => 'over_5_years'],
                ],
            ],

            // Skills
            'skills' => [
                'label' => 'Skill', 'type' => 'skill_select',
                'db_column' => 'skills',
                'name' => 'skill',
                'repeatable' => false,
                'allow_multiple' => true,
                'default_options' => [],
            ],
        ];
    }

    public static function countriesOptions(): array
    {
        return [
            ['label' => '',             'value' => ''],
            ['label' => 'Afghanistan',  'value' => '1'],
            ['label' => 'Aland Islands','value' => '2'],
            ['label' => 'Albania',      'value' => '3'],
            ['label' => 'Algeria',      'value' => '4'],
            ['label' => 'American Samoa','value' => '5'],
            ['label' => 'Andorra',      'value' => '6'],
            ['label' => 'Angola',       'value' => '7'],
            ['label' => 'Anguilla',     'value' => '8'],
            ['label' => 'Antarctica',   'value' => '9'],
            ['label' => 'Antigua and Barbuda','value' => '10'],
            ['label' => 'Argentina',    'value' => '11'],
            ['label' => 'Cote divoire (Ivory Coast)', 'value' => '12'],
        ];
    }
}
