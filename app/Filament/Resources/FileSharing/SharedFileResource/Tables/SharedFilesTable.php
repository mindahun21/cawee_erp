<?php

namespace App\Filament\Resources\FileSharing\SharedFileResource\Tables;

use App\Models\FileAccessLog;
use App\Support\FileSharing\EmployeeRecipientOptions;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SharedFilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('folder.name')
                    ->label('Folder')
                    ->placeholder('Unfiled')
                    ->searchable(),
                TextColumn::make('original_name')
                    ->label('Stored Name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('visibility')
                    ->badge()
                    ->sortable(),
                TextColumn::make('version_no')
                    ->label('Version')
                    ->sortable(),
                TextColumn::make('size_bytes')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => number_format(((int) $state) / 1024, 2).' KB'),
                IconColumn::make('is_locked')
                    ->boolean()
                    ->label('Locked'),
                TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->placeholder('-'),
                TextColumn::make('active_shares_count')
                    ->label('Active Shares')
                    ->counts('activeShares'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('share')
                    ->icon('heroicon-o-share')
                    ->schema([
                        Select::make('share_type')
                            ->options([
                                'staff' => 'Employee',
                                'client' => 'Client',
                                'public' => 'Public',
                            ])
                            ->default('staff')
                            ->required()
                            ->live(),
                        Select::make('access_level')
                            ->options([
                                'view' => 'View',
                                'download' => 'Download',
                                'upload' => 'Upload',
                                'manage' => 'Manage',
                            ])
                            ->default('download')
                            ->required(),
                        Select::make('shared_with_user_id')
                            ->hidden(),
                        Hidden::make('shared_with_employee_id')
                            ->hidden(),
                        Select::make('recipient_employee_id')
                            ->label('Recipient Employee')
                            ->options(fn (): array => EmployeeRecipientOptions::employeeOptions())
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(function ($state, $set): void {
                                $set('shared_with_employee_id', $state);
                                $set('shared_with_user_id', EmployeeRecipientOptions::userIdForEmployeeId($state));
                            })
                            ->helperText('All employees are listed. If an employee has no linked login yet, the share still keeps the employee recipient record.')
                            ->visible(fn ($get) => $get('share_type') === 'staff')
                            ->dehydrated(false)
                            ->rules(['required_if:share_type,staff']),
                        TextInput::make('shared_with_email')
                            ->email()
                            ->label('Recipient Email')
                            ->visible(fn ($get) => $get('share_type') === 'client')
                            ->dehydrated(fn ($get) => $get('share_type') === 'client')
                            ->rules(['required_if:share_type,client']),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->label('Optional Password'),
                        TextInput::make('max_downloads')
                            ->numeric()
                            ->minValue(1),
                        DateTimePicker::make('expires_at')
                            ->seconds(false),
                    ])
                    ->action(function (array $data, $record): void {
                        $share = $record->shares()->create([
                            'share_type' => $data['share_type'],
                            'access_level' => $data['access_level'],
                            'shared_with_user_id' => $data['shared_with_user_id'] ?? null,
                            'shared_with_employee_id' => $data['shared_with_employee_id'] ?? null,
                            'shared_with_email' => $data['shared_with_email'] ?? null,
                            'password' => $data['password'] ?? null,
                            'max_downloads' => $data['max_downloads'] ?? null,
                            'expires_at' => $data['expires_at'] ?? null,
                            'created_by' => auth()->id(),
                            'is_active' => true,
                        ]);

                        FileAccessLog::create([
                            'shared_file_id' => $record->id,
                            'file_share_id' => $share->id,
                            'user_id' => auth()->id(),
                            'action' => 'shared',
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'accessed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Share created')
                            ->body('Share URL: '.$share->share_url)
                            ->success()
                            ->send();
                    }),
                Action::make('revokeShares')
                    ->label('Revoke Shares')
                    ->icon('heroicon-o-no-symbol')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->activeShares()->exists())
                    ->action(function ($record): void {
                        $record->activeShares()->update(['is_active' => false]);

                        FileAccessLog::create([
                            'shared_file_id' => $record->id,
                            'user_id' => auth()->id(),
                            'action' => 'revoked',
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'accessed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Active shares revoked')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
