<?php

namespace App\Filament\Resources\InventoryMovements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ExportAction;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use App\Filament\Exports\InventoryMovementExporter;

class InventoryMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('asset.name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => new \Illuminate\Support\HtmlString('
                        <div class="hover-actions-wrapper flex gap-2 pt-1 items-center">
                            <a href="'.\App\Filament\Resources\InventoryMovements\InventoryMovementResource::getUrl('view', ['record' => $record]).'" class="hover-action-link text-gray-400 hover:text-gray-500">View</a>
                            <span class="text-gray-200">|</span>
                            <a href="'.\App\Filament\Resources\InventoryMovements\InventoryMovementResource::getUrl('edit', ['record' => $record]).'" class="hover-action-link text-primary-600 hover:text-primary-700">Edit</a>
                            <span class="text-gray-200">|</span>
                            <button type="button" 
                                x-on:click="$wire.mountTableAction(\'delete\', '.$record->id.')"
                                class="hover-action-link text-danger-600 hover:text-danger-700 font-medium">Delete</button>
                        </div>
                    '), position: 'below'),
                \Filament\Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Stock In' => 'success',
                        'Stock Out' => 'danger',
                        'Transfer' => 'info',
                        'Return' => 'warning',
                        'Adjustment' => 'gray',
                        'Damage' => 'danger',
                        'Disposal' => 'black',
                        default => 'gray',
                    })
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('reason')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_transit' => 'info',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('quantity')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('fromLocation.name')
                    ->label('From'),
                \Filament\Tables\Columns\TextColumn::make('toLocation.name')
                    ->label('To'),
                \Filament\Tables\Columns\TextColumn::make('reference_no')
                    ->label('Ref #')
                    ->searchable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'Stock In' => 'Stock In',
                        'Stock Out' => 'Stock Out',
                        'Transfer' => 'Transfer',
                        'Return' => 'Return',
                        'Adjustment' => 'Adjustment',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('from_location_id')
                    ->label('From Location')
                    ->relationship('fromLocation', 'name'),
                \Filament\Tables\Filters\SelectFilter::make('to_location_id')
                    ->label('To Location')
                    ->relationship('toLocation', 'name'),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->recordActions([
                \Filament\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->type === 'Transfer' && $record->status === 'pending')
                    ->action(fn ($record) => $record->update(['status' => 'in_transit'])),
                \Filament\Actions\Action::make('receive')
                    ->label('Receive')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->type === 'Transfer' && $record->status === 'in_transit')
                    ->action(fn ($record) => $record->update(['status' => 'completed'])),
                \Filament\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->type === 'Transfer' && $record->status === 'pending')
                    ->action(fn ($record) => $record->update(['status' => 'rejected'])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()->exporter(InventoryMovementExporter::class),
            ]);
    }
}
