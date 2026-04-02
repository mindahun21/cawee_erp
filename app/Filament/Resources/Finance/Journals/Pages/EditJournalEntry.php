<?php

namespace App\Filament\Resources\Finance\Journals\Pages;

use App\Filament\Resources\Finance\Journals\JournalEntryResource;
use App\Models\Finance\JournalEntry;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditJournalEntry extends EditRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),

            DeleteAction::make()
                ->visible(fn () => $this->record->isDraft()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    /**
     * Only draft journal entries may be edited.
     * Any other status redirects back to the view page with an error notification.
     */
    protected function authorizeAccess(): void
    {
        /** @var JournalEntry $record */
        $record = $this->getRecord();

        if (! $record->isEditable()) {
            \Filament\Notifications\Notification::make()
                ->title('Cannot edit this journal entry')
                ->body("Only draft entries can be edited. Current status: {$record->status}.")
                ->warning()
                ->send();

            $this->redirect(
                $this->getResource()::getUrl('view', ['record' => $record])
            );
        }
    }
}
