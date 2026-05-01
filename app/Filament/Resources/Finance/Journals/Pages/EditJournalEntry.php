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
     * JEs with 30+ lines are also blocked — the repeater cannot render that many
     * fields in a single Livewire request without exhausting PHP memory.
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
            return;
        }

        // Guard against very large JEs — the repeater Blade template exhausts memory
        // when rendering 30+ lines each with multiple searchable Select fields.
        $lineCount = $record->lines()->count();
        if ($lineCount > 30) {
            \Filament\Notifications\Notification::make()
                ->title('Entry too large to edit in the UI')
                ->body(
                    "This journal entry has {$lineCount} lines. " .
                    "The form editor supports up to 30 lines. " .
                    "To modify it, use the Submit or Post actions on the View page."
                )
                ->warning()
                ->persistent()
                ->send();

            $this->redirect(
                $this->getResource()::getUrl('view', ['record' => $record])
            );
        }
    }
}
