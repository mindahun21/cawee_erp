<?php namespace App\Filament\Resources\Procurement\Bids\Pages;
use App\Filament\Resources\Procurement\Bids\BidResource;
use Filament\Actions\DeleteAction; use Filament\Resources\Pages\EditRecord;
class EditBid extends EditRecord {
    protected static string $resource = BidResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        $tender = $record->tender;
        
        // If the form submitted criterion scores, we should calculate the composite right now
        // before saving to the DB.
        if ($tender && $tender->evaluationCriteria()->exists() && isset($data['criterionScores'])) {
            $criteriaWeights = $tender->evaluationCriteria()->pluck('weight', 'id');
            $weightedSum = 0;
            $hasScores = false;

            // Iterate through the repeater data from the form payload
            foreach ($data['criterionScores'] as $scoreData) {
                // Handle different array structures depending on how Filament sends repeater state
                $critId = $scoreData['criterion_id'] ?? null;
                $scoreVal = $scoreData['score'] ?? null;

                if ($critId && $scoreVal !== null && isset($criteriaWeights[$critId])) {
                    $weightedSum += ((float)$scoreVal * ((float)$criteriaWeights[$critId] / 100));
                    $hasScores = true;
                }
            }

            if ($hasScores) {
                $data['composite_score'] = round($weightedSum, 2);
            }
        }

        return $data;
    }
}
