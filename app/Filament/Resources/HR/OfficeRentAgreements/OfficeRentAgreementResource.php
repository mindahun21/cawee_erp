<?php

namespace App\Filament\Resources\HR\OfficeRentAgreements;

use App\Models\HrSettingOption;
use App\Models\OfficeRentAgreement;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class OfficeRentAgreementResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = OfficeRentAgreement::class;

    protected static ?string $cluster = \App\Filament\Clusters\CarRentManagement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Office Rent Agreements';

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('agreement_code')
                ->disabled()
                ->dehydrated()
                ->placeholder('Auto-generated'),

            Select::make('branch_id')
                ->relationship('branch', 'branch_name')
                ->searchable()
                ->required(),

            Select::make('landlord_id')
                ->relationship('landlord', 'name')
                ->searchable()
                ->nullable(),

            Select::make('payment_cycle_option_id')
                ->label('Payment Cycle')
                ->options(HrSettingOption::optionsFor('agreement_payment_cycle'))
                ->searchable()
                ->nullable(),

            TextInput::make('monthly_rent')
                ->numeric()
                ->prefix('ETB')
                ->required(),

            DatePicker::make('start_date')->required(),
            DatePicker::make('end_date')->afterOrEqual('start_date')->nullable(),

            Textarea::make('property_address')->required()->rows(2)->columnSpanFull(),

            FileUpload::make('contract_document_path')
                ->label('Scanned Contract')
                ->disk('local')
                ->directory('hr/office-rent/contracts')
                ->nullable(),

            Select::make('status')
                ->options([
                    'Draft' => 'Draft',
                    'Pending Legal' => 'Pending Legal',
                    'Approved' => 'Approved',
                    'Rejected' => 'Rejected',
                    'Active' => 'Active',
                    'Expired' => 'Expired',
                    'Terminated' => 'Terminated',
                ])
                ->default('Draft')
                ->disabled()
                ->dehydrated(),

            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('agreement_code')->label('Agreement ID')->searchable()->sortable()->copyable(),
                TextColumn::make('branch.branch_name')->label('Branch')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('landlord.name')->label('Landlord')->toggleable(),
                TextColumn::make('paymentCycle.label')->label('Cycle')->badge()->toggleable(),
                TextColumn::make('monthly_rent')->label('Rent')->money('ETB', true),
                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->sortable()->placeholder('Open-ended'),
                TextColumn::make('days_until_expiry')
                    ->label('Days Left')
                    ->badge()
                    ->colors([
                        'gray' => static fn ($state) => $state === null,
                        'danger' => static fn ($state) => $state < 0,
                        'warning' => static fn ($state) => $state <= 30,
                        'info' => static fn ($state) => $state <= 90,
                        'success' => static fn ($state) => $state > 90,
                    ]),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'Active',
                        'info' => 'Approved',
                        'warning' => 'Pending Legal',
                        'danger' => ['Rejected', 'Expired', 'Terminated'],
                        'gray' => 'Draft',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'Draft' => 'Draft',
                    'Pending Legal' => 'Pending Legal',
                    'Approved' => 'Approved',
                    'Rejected' => 'Rejected',
                    'Active' => 'Active',
                    'Expired' => 'Expired',
                    'Terminated' => 'Terminated',
                ]),
            ])
            ->defaultSort('start_date', 'desc')
            ->recordActions([
                Action::make('submit_legal')
                    ->label('Submit Legal')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (OfficeRentAgreement $record) => $record->status === 'Draft')
                    ->requiresConfirmation()
                    ->action(function (OfficeRentAgreement $record): void {
                        $record->update(['status' => 'Pending Legal']);
                        $record->branch()->update(['status' => 'Pending Agreement']);
                        Notification::make()->title('Agreement submitted for legal review')->info()->send();
                    }),

                Action::make('legal_approve')
                    ->label('Legal Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (OfficeRentAgreement $record) => $record->status === 'Pending Legal')
                    ->requiresConfirmation()
                    ->action(function (OfficeRentAgreement $record): void {
                        $record->update([
                            'status' => 'Approved',
                            'legal_reviewed_by' => auth()->id(),
                            'legal_reviewed_at' => now(),
                        ]);
                        Notification::make()->title('Agreement approved by legal')->success()->send();
                    }),

                Action::make('legal_reject')
                    ->label('Legal Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (OfficeRentAgreement $record) => $record->status === 'Pending Legal')
                    ->form([
                        Textarea::make('notes')->label('Rejection Notes')->required(),
                    ])
                    ->action(function (OfficeRentAgreement $record, array $data): void {
                        $record->update([
                            'status' => 'Rejected',
                            'legal_reviewed_by' => auth()->id(),
                            'legal_reviewed_at' => now(),
                            'notes' => trim(($record->notes ?? '') . "\n" . $data['notes']),
                        ]);
                        Notification::make()->title('Agreement rejected')->danger()->send();
                    }),

                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->visible(fn (OfficeRentAgreement $record) => $record->status === 'Approved')
                    ->requiresConfirmation()
                    ->action(function (OfficeRentAgreement $record): void {
                        $record->update([
                            'status' => 'Active',
                            'activated_at' => now(),
                        ]);
                        $record->branch()->update(['status' => 'Active']);
                        Notification::make()->title('Agreement activated')->success()->send();
                    }),

                Action::make('mark_expired')
                    ->label('Mark Expired')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->visible(fn (OfficeRentAgreement $record) => $record->status === 'Active')
                    ->requiresConfirmation()
                    ->action(function (OfficeRentAgreement $record): void {
                        $record->update(['status' => 'Expired']);
                        Notification::make()->title('Agreement marked expired')->warning()->send();
                    }),

                Action::make('terminate')
                    ->label('Terminate')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (OfficeRentAgreement $record) => in_array($record->status, ['Active', 'Approved', 'Expired']))
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('notes')->label('Termination Notes')->required(),
                    ])
                    ->action(function (OfficeRentAgreement $record, array $data): void {
                        $record->update([
                            'status' => 'Terminated',
                            'notes' => trim(($record->notes ?? '') . "\n" . $data['notes']),
                        ]);
                        Notification::make()->title('Agreement terminated')->danger()->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageOfficeRentAgreements::route('/'),
        ];
    }
}
