<?php

namespace App\Filament\Resources\Donors\DonorResource\RelationManagers;

use App\Models\Donation;
use App\Services\ReportService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use App\Models\Currency;

class DonationsRelationManager extends RelationManager
{
    protected static string $relationship = 'donations';

    protected static ?string $recordTitleAttribute = 'receipt_number';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('donation_type_id')
                            ->relationship('donationType', 'name', fn ($query) => $query->active())
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('receipt_number')
                            ->label('Receipt Number')
                            ->placeholder('Auto-generated')
                            ->disabled()
                            ->hidden(fn ($record) => $record === null), // Hide on create, show on edit
                        TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->prefix('ETB'),
                        Select::make('currency_id')
                            ->relationship('currency', 'name')
                            ->default(fn () => Currency::where('code', 'ETB')->first()?->id ?? 1)
                            ->required(),
                        DatePicker::make('donation_date')
                            ->label('Donation Date')
                            ->default(now())
                            ->required()
                            ->native(false),
                        Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'bank_transfer' => 'Bank Transfer',
                                'check' => 'Check',
                                'online' => 'Online Payment',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('transaction_id')
                            ->label('Transaction ID')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Bank Ref, Receipt #'),
                        Select::make('campaign_id')
                            ->relationship('campaign', 'title')
                            ->searchable()
                            ->preload()
                            ->placeholder('General Donation'),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->default('completed')
                            ->required()
                            ->native(false),
                        Textarea::make('notes')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_number')
                    ->label('Receipt #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('semibold'),
                TextColumn::make('amount')
                    ->money(fn ($record) => $record->currency?->code ?? 'ETB')
                    ->sortable(),
                TextColumn::make('donation_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                    }),
                TextColumn::make('payment_method')
                    ->label('Method')
                    ->toggleable(),
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->placeholder('General')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\Filter::make('donation_date')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('to')->label('To'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->where('donation_date', '>=', $data['from']))
                            ->when($data['to'], fn ($q) => $q->where('donation_date', '<=', $data['to']));
                    }),
            ])
            ->headerActions([
                CreateAction::make('create')
                    ->label('Add Donation')
                    ->icon('heroicon-m-plus')
                    ->authorize(true)
                    ->visible(true),
            ])
            ->emptyStateActions([
                CreateAction::make('create')
                    ->label('Add Donation')
                    ->icon('heroicon-m-plus')
                    ->authorize(true)
                    ->visible(true),
            ])
            ->actions([
                Action::make('downloadReceipt')
                    ->label('Receipt')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function (Donation $record) {
                        $service = app(ReportService::class);
                        $pdfContent = $service->generateReceipt($record);
                        
                        return response()->streamDownload(
                            fn () => print($pdfContent),
                            "receipt-{$record->id}.pdf"
                        );
                    }),
                ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('donation_date', 'desc');
    }
}
