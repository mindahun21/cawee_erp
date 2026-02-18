<?php

namespace App\Filament\Resources\CampaignEvents\CampaignEventResource\RelationManagers;

use App\Mail\EventInvitation;
use App\Mail\EventReminder;
use App\Models\Donor;
use App\Models\EventAttendee;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class AttendeesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendees';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('donor_id')
                    ->relationship('donor', 'first_name')
                    ->searchable(['first_name', 'last_name', 'organization_name'])
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name),
                TextInput::make('name')
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'confirmed' => 'Confirmed',
                        'pending' => 'Pending',
                        'declined' => 'Declined',
                    ])
                    ->required()
                    ->default('pending'),
                TextInput::make('guests')
                    ->numeric()
                    ->default(0),
                TextInput::make('tickets_purchased')
                    ->numeric()
                    ->default(0),
                TextInput::make('amount_paid')
                    ->numeric()
                    ->default(0)
                    ->prefix('$'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('donor.full_name') // Assuming Donor model has full_name attribute or handle manually
                    ->label('Donor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Non-Donor'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'pending' => 'warning',
                        'declined' => 'danger',
                    }),
                TextColumn::make('guests')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->money()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('bulkAddDonors')
                    ->label('Bulk Add Donors')
                    ->icon('heroicon-o-users')
                    ->color('info')
                    ->form([
                        Select::make('donor_ids')
                            ->label('Select Donors')
                            ->multiple()
                            ->placeholder('Start typing to search donors...')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Donor::query()
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('organization_name', 'like', "%{$search}%")
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn ($donor) => [$donor->id => $donor->full_name])
                                ->all())
                            ->getOptionLabelsUsing(fn (array $values): array => Donor::whereIn('id', $values)
                                ->get()
                                ->mapWithKeys(fn ($donor) => [$donor->id => $donor->full_name])
                                ->all())
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        foreach ($data['donor_ids'] as $donorId) {
                            $this->getOwnerRecord()->attendees()->firstOrCreate(
                                ['donor_id' => $donorId],
                                [
                                    'status' => 'pending',
                                    'guests' => 0,
                                    'amount_paid' => 0,
                                    'tickets_purchased' => 0,
                                ]
                            );
                        }
                    }),
                CreateAction::make()
                    ->label('Add Attendee')
                    ->modalHeading('Add Attendee')
                    ->icon('heroicon-o-plus')
                    ->using(function (array $data, string $model): EventAttendee {
                        // Handle donor uniqueness
                        if (!empty($data['donor_id'])) {
                            return $this->getOwnerRecord()->attendees()->firstOrCreate(
                                ['donor_id' => $data['donor_id']],
                                $data
                            );
                        }
                        
                        // Handle email uniqueness for non-donors
                        if (!empty($data['email'])) {
                            return $this->getOwnerRecord()->attendees()->firstOrCreate(
                                ['email' => $data['email']],
                                $data
                            );
                        }

                        return $this->getOwnerRecord()->attendees()->create($data);
                    }),
                Action::make('sendAllInvitations')
                    ->label('Send All Invitations')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Send Invitations to All Attendees')
                    ->modalDescription('This will send invitation emails to all attendees of this event.')
                    ->action(function (): void {
                        $attendees = $this->getOwnerRecord()->attendees;
                        $count = 0;
                        
                        foreach ($attendees as $attendee) {
                            $email = $attendee->email ?? $attendee->donor?->email;
                            if ($email) {
                                Mail::to($email)->queue(new EventInvitation($attendee, $this->getOwnerRecord()));
                                $count++;
                            }
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Invitations Sent')
                            ->body("Successfully queued {$count} invitation email(s).")
                            ->success()
                            ->send();
                    }),
                Action::make('sendAllReminders')
                    ->label('Send All Reminders')
                    ->icon('heroicon-o-bell')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Send Reminders to All Attendees')
                    ->modalDescription('This will send reminder emails to all attendees of this event.')
                    ->action(function (): void {
                        $attendees = $this->getOwnerRecord()->attendees;
                        $count = 0;
                        
                        foreach ($attendees as $attendee) {
                            $email = $attendee->email ?? $attendee->donor?->email;
                            if ($email) {
                                Mail::to($email)->queue(new EventReminder($attendee, $this->getOwnerRecord()));
                                $count++;
                            }
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Reminders Sent')
                            ->body("Successfully queued {$count} reminder email(s).")
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('sendInvitations')
                        ->label('Send Invitations')
                        ->icon('heroicon-o-envelope')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Send Invitations to Selected Attendees')
                        ->modalDescription('This will send invitation emails to the selected attendees.')
                        ->action(function (Collection $records): void {
                            $count = 0;
                            
                            foreach ($records as $attendee) {
                                $email = $attendee->email ?? $attendee->donor?->email;
                                if ($email) {
                                    Mail::to($email)->queue(new EventInvitation($attendee, $this->getOwnerRecord()));
                                    $count++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Invitations Sent')
                                ->body("Successfully queued {$count} invitation email(s).")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('sendReminders')
                        ->label('Send Reminders')
                        ->icon('heroicon-o-bell')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Send Reminders to Selected Attendees')
                        ->modalDescription('This will send reminder emails to the selected attendees.')
                        ->action(function (Collection $records): void {
                            $count = 0;
                            
                            foreach ($records as $attendee) {
                                $email = $attendee->email ?? $attendee->donor?->email;
                                if ($email) {
                                    Mail::to($email)->queue(new EventReminder($attendee, $this->getOwnerRecord()));
                                    $count++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Reminders Sent')
                                ->body("Successfully queued {$count} reminder email(s).")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
