<?php

namespace App\Filament\Resources\Finance\Journals\Pages;

use App\Filament\Resources\Finance\Journals\JournalEntryResource;
use App\Services\Finance\JournalEntryService;
use Filament\Resources\Pages\CreateRecord;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    /**
     * Before the form is filled, inject an auto-generated reference number
     * so it appears immediately in the disabled field and is dehydrated on save.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate reference number if not already set
        if (empty($data['reference_number'])) {
            $data['reference_number'] = app(JournalEntryService::class)
                ->generateReference(now()->year);
        }

        // Stamp the prepared_by from the authenticated user
        $data['prepared_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', [
            'record' => $this->getRecord(),
        ]);
    }
}
