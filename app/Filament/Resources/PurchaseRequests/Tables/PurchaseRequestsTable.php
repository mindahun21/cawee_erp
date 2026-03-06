<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class PurchaseRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('code')
                    ->label('Purchase Request Code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Purchase Request Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => new \Illuminate\Support\HtmlString('
                        <div class="hover-actions-wrapper flex gap-2 pt-1 items-center">
                            <a href="'.\App\Filament\Resources\PurchaseRequests\PurchaseRequestResource::getUrl('view', ['record' => $record]).'" class="hover-action-link text-gray-400 hover:text-gray-500">View</a>
                            <span class="text-gray-200">|</span>
                            <a href="'.\App\Filament\Resources\PurchaseRequests\PurchaseRequestResource::getUrl('edit', ['record' => $record]).'" class="hover-action-link text-primary-600 hover:text-primary-700">Edit</a>
                            <span class="text-gray-200">|</span>
                            <button type="button" 
                                x-on:click="$wire.mountTableAction(\'delete\', '.$record->id.')"
                                class="hover-action-link text-danger-600 hover:text-danger-700 font-medium">Delete</button>
                        </div>
                    '), position: 'below'),
                \Filament\Tables\Columns\TextColumn::make('requester.name')
                    ->label('Requester')
                    ->placeholder('No Requester')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Request time')
                    ->dateTime()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'ordered' => 'info',
                        'completed' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('PO No.')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name'),
                \Filament\Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from_date'),
                        \Filament\Forms\Components\DatePicker::make('to_date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from_date'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['to_date'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->label('Download')
                        ->action(fn () => null), // Placeholder
                    \Filament\Actions\Action::make('email')
                        ->icon('heroicon-o-envelope')
                        ->label('Email')
                        ->color('success')
                        ->action(fn () => null), // Placeholder
                    \Filament\Actions\Action::make('share')
                        ->icon('heroicon-o-share')
                        ->label('Share')
                        ->color('info')
                        ->action(fn () => null), // Placeholder
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
