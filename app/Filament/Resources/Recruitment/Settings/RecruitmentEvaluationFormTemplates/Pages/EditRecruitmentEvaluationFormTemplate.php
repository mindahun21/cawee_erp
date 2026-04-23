<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationFormTemplates\Pages;

use App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationFormTemplates\RecruitmentEvaluationFormTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditRecruitmentEvaluationFormTemplate extends EditRecord
{
    protected static string $resource = RecruitmentEvaluationFormTemplateResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Hydrate the virtual 'groups' Repeater from the DB lines
        $groups = $this->record->lines()
            ->get()
            ->groupBy('group_criteria_id')
            ->map(function ($lines, $groupId) {
                return [
                    'group_criteria_id' => $groupId,
                    'lines' => $lines->map(fn($line) => [
                        'criteria_id' => $line->criteria_id,
                        'proportion' => $line->proportion,
                    ])->toArray(),
                ];
            })->values()->toArray();

        $data['groups'] = $groups;
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->validateProportions($data['groups'] ?? []);
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $groups = $data['groups'] ?? [];
        unset($data['groups']);

        $record->update($data);

        DB::transaction(function () use ($record, $groups) {
            $record->lines()->delete();
            
            $order = 0;
            foreach ($groups as $group) {
                foreach ($group['lines'] ?? [] as $line) {
                    $record->lines()->create([
                        'group_criteria_id' => $group['group_criteria_id'],
                        'criteria_id'       => $line['criteria_id'],
                        'proportion'        => $line['proportion'],
                        'sort_order'        => $order++,
                    ]);
                }
            }
        });

        return $record;
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
