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
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;

class DonationsRelationManager extends RelationManager
{
    protected static string $relationship = 'donations';

    protected static ?string $recordTitleAttribute = 'receipt_number';

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
                // Donations should typically be created through the Donations module or via a specific "Add Donation" action
                // but we can add it here if needed.
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
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('donation_date', 'desc');
    }
}
