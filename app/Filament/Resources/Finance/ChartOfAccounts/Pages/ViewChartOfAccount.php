<?php

namespace App\Filament\Resources\Finance\ChartOfAccounts\Pages;

use App\Filament\Resources\Finance\ChartOfAccounts\ChartOfAccountResource;
use App\Models\Finance\ChartOfAccount;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewChartOfAccount extends ViewRecord
{
    protected static string $resource = ChartOfAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => $this->record->is_active),

            Action::make('toggle_active')
                ->label(fn () => $this->record->is_active ? 'Deactivate Account' : 'Activate Account')
                ->icon(fn () => $this->record->is_active
                    ? 'heroicon-o-x-circle'
                    : 'heroicon-o-check-circle'
                )
                ->color(fn () => $this->record->is_active ? 'danger' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->is_active
                    ? "Deactivate [{$this->record->code}] {$this->record->name}?"
                    : "Activate [{$this->record->code}] {$this->record->name}?"
                )
                ->modalDescription(fn () => $this->record->is_active
                    ? 'Inactive accounts are hidden from journal entry dropdowns and block new postings. All existing GL history is preserved.'
                    : 'This account will become available for journal entry line selection.'
                )
                ->action(function () {
                    $this->record->update(['is_active' => ! $this->record->is_active]);

                    Notification::make()
                        ->title($this->record->is_active
                            ? "Account [{$this->record->code}] activated."
                            : "Account [{$this->record->code}] deactivated."
                        )
                        ->color($this->record->is_active ? 'success' : 'warning')
                        ->send();

                    $this->refreshFormData(['is_active']);
                }),
        ];
    }
}
