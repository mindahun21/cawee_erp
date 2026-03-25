<style>
/* ============================================
   Channel Form Builder — Custom Block Styles
   ============================================ */

/* The right panel canvas — matches Zemen's dashed border area */
.channel-form-builder .fi-fo-builder-blocks {
    min-height: 500px;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 12px;
    background: #fafafa;
}

/* Hide native Add Action button */
.channel-form-builder .fi-fo-builder-add-action {
    display: none !important;
}

/* Empty state placeholder text */
.channel-form-builder .fi-fo-builder-blocks:empty::before {
    content: "Drag a field from the right to this area";
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100px;
    color: #9ca3af;
    font-size: 0.875rem;
    font-style: italic;
}

/* Each block wrapper — the collapsed card */
.channel-form-builder .fi-fo-builder-block {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    margin-bottom: 6px;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.channel-form-builder .fi-fo-builder-block.fi-collapsed {
    border-style: solid !important;
}

/* EXPANDED STATE (Point 3) */
/* The main wrapper border */
.channel-form-builder .fi-fo-builder-block:not(.fi-collapsed) {
    border: 2px dashed var(--primary-500) !important;
    background-color: #f3f4f6 !important; /* Light gray background */
}

/* Force inner body wrappers to be transparent or light gray */
.channel-form-builder .fi-fo-builder-block:not(.fi-collapsed) > div,
.channel-form-builder .fi-fo-builder-block:not(.fi-collapsed) form,
.channel-form-builder .fi-fo-builder-block:not(.fi-collapsed) .fi-fo-builder-block-content {
    background-color: #f3f4f6 !important;
}

/* The dark gray header area */
.channel-form-builder .fi-fo-builder-block:not(.fi-collapsed) .fi-fo-builder-block-header,
.channel-form-builder .fi-fo-builder-block:not(.fi-collapsed) > header,
.channel-form-builder .fi-fo-builder-item-header {
    background-color: #374151 !important; /* Dark gray */
    border-bottom: 1px solid #1f2937 !important;
    border-radius: 0 !important;
}

/* Force text and icons in dark header to be white */
.channel-form-builder .fi-fo-builder-block:not(.fi-collapsed) .fi-fo-builder-block-label,
.channel-form-builder .fi-fo-builder-block:not(.fi-collapsed) .fi-fo-builder-block-header *,
.channel-form-builder .fi-fo-builder-block:not(.fi-collapsed) > header *,
.channel-form-builder .fi-fo-builder-item-header * {
    color: #ffffff !important;
}

.channel-form-builder .fi-fo-builder-block:hover {
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}

/* The block header row (collapsed state — shows label + drag handle) */
.channel-form-builder .fi-fo-builder-block-header {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    cursor: default;
    gap: 8px;
}

/* Block label text */
.channel-form-builder .fi-fo-builder-block-header .fi-fo-builder-block-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    flex: 1;
}

/* Hide the action buttons by default, show on hover */
.channel-form-builder .fi-fo-builder-block .fi-fo-builder-block-actions {
    opacity: 0;
    transition: opacity 0.15s ease;
    display: flex;
    align-items: center;
    gap: 4px;
}

.channel-form-builder .fi-fo-builder-block:hover .fi-fo-builder-block-actions {
    opacity: 1;
}

/* The pencil (collapse/expand) button — style it to look like ✏ */
.channel-form-builder .fi-fo-builder-block .fi-fo-builder-block-collapse-action {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    color: #6b7280;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: color 0.15s, background 0.15s;
}

.channel-form-builder .fi-fo-builder-block .fi-fo-builder-block-collapse-action:hover {
    color: #3b82f6;
    background: #eff6ff;
}

/* The delete (×) button — style it to look like × not a red trash button */
.channel-form-builder .fi-fo-builder-block .fi-fo-builder-block-delete-action {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    color: #9ca3af;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: color 0.15s, background 0.15s;
    font-size: 16px;
    font-weight: 400;
}

.channel-form-builder .fi-fo-builder-block .fi-fo-builder-block-delete-action:hover {
    color: #ef4444;
    background: #fef2f2;
}

/* Hide the default Filament trash icon inside the delete button,
   replace with × character */
.channel-form-builder .fi-fo-builder-block .fi-fo-builder-block-delete-action svg {
    display: none;
}

.channel-form-builder .fi-fo-builder-block .fi-fo-builder-block-delete-action::after {
    content: "×";
    font-size: 18px;
    line-height: 1;
}

/* Replace the chevron icon with a pencil appearance */
.channel-form-builder .fi-fo-builder-block-collapse-action svg {
    display: none;
}
.channel-form-builder .fi-fo-builder-block-collapse-action::before {
    content: "✏";
    font-size: 13px;
}

/* The expanded block content area — slightly different background */
.channel-form-builder .fi-fo-builder-block-content {
    padding: 16px;
    border-top: 1px solid #f3f4f6;
    background: #f8fafc;  /* very subtle blue-gray tint — matches Zemen's expanded state */
    border-radius: 0 0 6px 6px;
}

/* When collapsed, hide the content area */
.channel-form-builder .fi-fo-builder-block.fi-collapsed .fi-fo-builder-block-content {
    display: none;
}

/* The "Name" field inside each block — make it look read-only */
.channel-form-builder .fi-fo-builder-block [data-field="name"] input,
.channel-form-builder .fi-fo-builder-block input[name*="[name]"] {
    background-color: #f3f4f6 !important;
    color: #6b7280 !important;
    cursor: not-allowed !important;
    border-color: #e5e7eb !important;
}

/* "Add field" button at the bottom of the builder */
.channel-form-builder .fi-fo-builder-add-action {
    margin-top: 8px;
}

/* The "Add field" dropdown items */
.channel-form-builder .fi-dropdown-list-item {
    font-size: 0.875rem;
    padding: 8px 12px;
}

/* Drag handle */
.channel-form-builder .fi-fo-builder-block .fi-fo-builder-block-reorder-handle {
    color: #d1d5db;
    cursor: grab;
}

.channel-form-builder .fi-fo-builder-block .fi-fo-builder-block-reorder-handle:hover {
    color: #9ca3af;
}

/* Left palette panel */
.channel-field-palette {
    /* Height is full of content to avoid scroll, width controlled by Grid */
    width: 100%;
}

.channel-field-palette .palette-item {
    border-bottom: 2px solid #ffffff;
    background-color: #f3f4f6; /* Gray background */
    cursor: pointer;
}

.channel-field-palette .palette-item:last-child {
    border-bottom: none;
}

.channel-field-palette .palette-item:hover {
    background-color: #e5e7eb;
}

/* ── Dark mode ── */
.dark .channel-form-builder .fi-fo-builder-blocks {
    border-color: #4b5563;
    background: #1f2937;
}

.dark .channel-form-builder .fi-fo-builder-block {
    background: #111827;
    border-color: #374151;
}

.dark .channel-form-builder .fi-fo-builder-block:not(.fi-collapsed) {
    border-color: var(--primary-400) !important;
    background-color: #1f2937 !important;
}

.dark .channel-form-builder .fi-fo-builder-block:not(.fi-collapsed) > div,
.dark .channel-form-builder .fi-fo-builder-block:not(.fi-collapsed) form,
.dark .channel-form-builder .fi-fo-builder-block:not(.fi-collapsed) .fi-fo-builder-block-content {
    background-color: #1f2937 !important;
}

.dark .channel-form-builder .fi-fo-builder-block-header .fi-fo-builder-block-label {
    color: #e5e7eb;
}

.dark .channel-form-builder .fi-fo-builder-block-content {
    border-top-color: #374151;
    background: #111827;
}

.dark .channel-form-builder .fi-fo-builder-block [data-field="name"] input,
.dark .channel-form-builder .fi-fo-builder-block input[name*="[name]"] {
    background-color: #1f2937 !important;
    color: #9ca3af !important;
    border-color: #374151 !important;
}

.dark .channel-form-builder .fi-fo-builder-block .fi-fo-builder-block-collapse-action:hover {
    color: #60a5fa;
    background: #1e3a5f;
}

.dark .channel-form-builder .fi-fo-builder-block .fi-fo-builder-block-delete-action:hover {
    color: #f87171;
    background: #3b1111;
}

.dark .channel-form-builder .fi-fo-builder-block .fi-fo-builder-block-reorder-handle {
    color: #4b5563;
}
.dark .channel-form-builder .fi-fo-builder-block .fi-fo-builder-block-reorder-handle:hover {
    color: #6b7280;
}

.dark .channel-field-palette {
    background-color: #111827;
}
.dark .channel-field-palette .palette-item {
    background-color: #1f2937;
    border-bottom-color: #111827;
}
.dark .channel-field-palette .palette-item:hover {
    background-color: #374151;
}
.dark .channel-field-palette .palette-item span {
    color: #d1d5db !important;
}
</style>

<div
    class="channel-field-palette rounded-lg bg-white"
>
    @php
        $fields = [
            ['key' => 'header',               'label' => 'Header',                 'icon' => 'H'],
            ['key' => 'paragraph',             'label' => 'Paragraph',              'icon' => '¶'],
            ['key' => 'resume_path',           'label' => 'File Upload',            'icon' => '↑'],
            ['key' => 'first_name',            'label' => 'First name',             'icon' => null],
            ['key' => 'last_name',             'label' => 'Last name',              'icon' => null],
            ['key' => 'candidate_code',        'label' => 'Candidate code',         'icon' => null],
            ['key' => 'birthday',              'label' => 'Birthday',               'icon' => null],
            ['key' => 'gender',                'label' => 'Gender',                 'icon' => null],
            ['key' => 'desired_salary',        'label' => 'Desired salary',         'icon' => null],
            ['key' => 'birthplace',            'label' => 'Birthplace',             'icon' => null],
            ['key' => 'home_town',             'label' => 'Home town',              'icon' => null],
            ['key' => 'identification',        'label' => 'Identification',         'icon' => null],
            ['key' => 'place_of_issue',        'label' => 'Place of issue',         'icon' => null],
            ['key' => 'marital_status',        'label' => 'Marital status',         'icon' => null],
            ['key' => 'nation',                'label' => 'Nation',                 'icon' => null],
            ['key' => 'religion',              'label' => 'Religion',               'icon' => null],
            ['key' => 'height_m',              'label' => 'Height(m)',              'icon' => null],
            ['key' => 'weight_kg',             'label' => 'Weight(kg)',             'icon' => null],
            ['key' => 'email',                 'label' => 'Email Address',          'icon' => null],
            ['key' => 'phone',                 'label' => 'Phone',                  'icon' => null],
            ['key' => 'company',               'label' => 'Company',                'icon' => null],
            ['key' => 'resident',              'label' => 'Resident',               'icon' => null],
            ['key' => 'nationality',           'label' => 'Cote divoire (Ivory Coast)', 'icon' => null],
            ['key' => 'zip_code',              'label' => 'Zip Code',               'icon' => null],
            ['key' => 'introduce_yourself',    'label' => 'Introduce yourself',     'icon' => null],
            ['key' => 'skype',                 'label' => 'Skype',                  'icon' => null],
            ['key' => 'facebook',              'label' => 'Facebook',               'icon' => null],
            ['key' => 'linkedin_url',          'label' => 'Linkedin',               'icon' => null],
            ['key' => 'current_accommodation', 'label' => 'Current accommodation',  'icon' => null],
            ['key' => 'role_in_old_company',   'label' => 'Role in the old company','icon' => null],
            ['key' => 'contact_person',        'label' => 'Contact person',         'icon' => null],
            ['key' => 'salary',                'label' => 'Salary',                 'icon' => null],
            ['key' => 'reason_for_leaving_job','label' => 'Reason for leaving job', 'icon' => null],
            ['key' => 'job_description',       'label' => 'Job description',        'icon' => null],
            ['key' => 'diploma',               'label' => 'Diploma',                'icon' => null],
            ['key' => 'training_places',       'label' => 'Training places',        'icon' => null],
            ['key' => 'specialized',           'label' => 'Specialized',            'icon' => null],
            ['key' => 'percentage',            'label' => 'Percentage',             'icon' => null],
            ['key' => 'days_for_identity',     'label' => 'Days for identity',      'icon' => null],
            ['key' => 'seniority',             'label' => 'Seniority',              'icon' => null],
            ['key' => 'skills',                'label' => 'Skill',                  'icon' => null],
            ['key' => 'interests',             'label' => 'Interests',              'icon' => null],
        ];
    @endphp

    @foreach ($fields as $field)
        <div 
            wire:click="mountAction('add', { block: '{{ $field['key'] }}' }, { schemaComponent: 'data.form_schema' })"
            class="palette-item flex items-center gap-2 px-3 py-2 text-sm select-none transition-colors"
        >
            @if ($field['icon'])
                <span class="font-bold text-gray-500 w-4 flex-shrink-0">{{ $field['icon'] }}</span>
            @else
                <span class="w-4 flex-shrink-0"></span>
            @endif
            <span class="text-gray-700 font-medium">{{ $field['label'] }}</span>
        </div>
    @endforeach
</div>
