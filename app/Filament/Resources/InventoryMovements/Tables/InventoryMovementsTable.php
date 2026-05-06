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
                \Filament\Tables\Columns\TextColumn::make('item.name')
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
                \Filament\Tables\Columns\TextColumn::make('fromWarehouse.name')
                    ->label('From')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('destination')
                    ->label('To')
                    ->state(function ($record) {
                        if ($record->destination_type === 'warehouse') {
                            return $record->toWarehouse?->name;
                        }
                        return ($record->toLocation?->location_name ?: '') . 
                               ($record->toDepartment?->name ? ' (' . $record->toDepartment->name . ')' : '');
                    }),
                \Filament\Tables\Columns\TextColumn::make('quantity')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending Approval' => 'warning',
                        'In Transit' => 'info',
                        'Completed / Received' => 'success',
                        'Rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('reason.name')
                    ->label('Reason')
                    ->sortable()
                    ->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('reference_no')
                    ->label('Ref #')
                    ->searchable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('item_id')
                    ->label('Item')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => \App\Models\Item::exists()),
                \Filament\Tables\Filters\SelectFilter::make('reason_id')
                    ->label('Reason')
                    ->relationship('reason', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => \App\Models\InventoryMovementReason::exists()),
                \Filament\Tables\Filters\SelectFilter::make('status_id')
                    ->label('Status')
                    ->relationship('status', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => \App\Models\InventoryMovementStatus::exists()),
                \Filament\Tables\Filters\SelectFilter::make('from_warehouse_id')
                    ->label('From Warehouse')
                    ->relationship('fromWarehouse', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => \App\Models\Warehouse::exists()),
                \Filament\Tables\Filters\SelectFilter::make('to_warehouse_id')
                    ->label('To Warehouse')
                    ->relationship('toWarehouse', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => \App\Models\Warehouse::exists()),
                \Filament\Tables\Filters\SelectFilter::make('to_location_id')
                    ->label('To Location')
                    ->relationship('toLocation', 'location_name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => \App\Models\Location::exists()),
                \Filament\Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Handled By')
                    ->relationship('employee', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                    ->searchable()
                    ->preload()
                    ->visible(fn () => \App\Models\Employee::exists()),
                \Filament\Tables\Filters\SelectFilter::make('to_department_id')
                    ->label('To Department')
                    ->relationship('toDepartment', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => \App\Models\Department::exists()),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->recordActions([
                \Filament\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status?->name === 'Pending Approval')
                    ->action(function ($record) {
                        $status = \App\Models\InventoryMovementStatus::where('name', 'In Transit')->first();
                        if ($status) {
                            $record->update(['status_id' => $status->id]);
                        }
                    }),
                \Filament\Actions\Action::make('receive')
                    ->label('Receive')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status?->name === 'In Transit')
                    ->action(function ($record) {
                        $status = \App\Models\InventoryMovementStatus::where('name', 'Completed / Received')->first();
                        if ($status) {
                            $record->update(['status_id' => $status->id]);
                        }
                    }),
                \Filament\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status?->name === 'Pending Approval')
                    ->action(function ($record) {
                        $status = \App\Models\InventoryMovementStatus::where('name', 'Rejected')->first();
                        if ($status) {
                            $record->update(['status_id' => $status->id]);
                        }
                    }),
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
