<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationFormTemplates\Pages;

use App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationFormTemplates\RecruitmentEvaluationFormTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateRecruitmentEvaluationFormTemplate extends CreateRecord
{
    protected static string $resource = RecruitmentEvaluationFormTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateProportions($data['groups'] ?? []);
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $groups = $data['groups'] ?? [];
        unset($data['groups']);

        $template = static::getModel()::create($data);

        DB::transaction(function () use ($template, $groups) {
            $order = 0;
            foreach ($groups as $group) {
                foreach ($group['lines'] ?? [] as $line) {
                    $template->lines()->create([
                        'group_criteria_id' => $group['group_criteria_id'],
                        'criteria_id'       => $line['criteria_id'],
                        'proportion'        => $line['proportion'],
                        'sort_order'        => $order++,
                    ]);
                }
            }
        });

        return $template;
    }

    protected function validateProportions(array $groups): void
    {
        $total = collect($groups)
            ->flatMap(fn($group) => $group['lines'] ?? [])
            ->sum('proportion');

        if (abs($total - 100) > 0.01) {
            throw ValidationException::withMessages([
                'data.groups' => "Proportions must sum to exactly 100%. Current total: {$total}%",
            ]);
        }
    }
}
