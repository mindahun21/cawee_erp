<?php

namespace App\Filament\Components;

use App\Models\Recruitment\RecruitmentChannel;
use App\Models\Recruitment\RecruitmentSkill;
use Filament\Forms\Components\Field;

class FormSchemaBuilder extends Field
{
    protected string $view = 'filament.components.form-schema-builder';

    public function getAvailableFields(): array
    {
        return collect(RecruitmentChannel::availableFields())
            ->map(fn (array $field, string $key): array => [
                'field_key' => $key,
                ...$field,
            ])
            ->values()
            ->all();
    }

    public function getSkillOptions(): array
    {
        return RecruitmentSkill::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (RecruitmentSkill $skill): array => [
                'label' => $skill->name,
                'value' => (string) $skill->id,
            ])
            ->values()
            ->all();
    }
}
