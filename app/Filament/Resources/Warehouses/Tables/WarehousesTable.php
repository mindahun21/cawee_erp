<?php

namespace App\Filament\Resources\Warehouses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Tables\Enums\FiltersLayout;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('warehouse_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(
                        fn ($record) => new \Illuminate\Support\HtmlString('
                        <div class="hover-actions-wrapper flex gap-2 pt-1 items-center">
                            <a href="'.\App\Filament\Resources\Warehouses\WarehouseResource::getUrl('view', ['record' => $record]).'" class="hover-action-link text-gray-400 hover:text-gray-500">View</a>
                            <span class="text-gray-200">|</span>
                            <a href="'.\App\Filament\Resources\Warehouses\WarehouseResource::getUrl('edit', ['record' => $record]).'" class="hover-action-link text-primary-600 hover:text-primary-700">Edit</a>
                        </div>
                    '), position: 'below'
                    ),
                \Filament\Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('country')
                    ->formatStateUsing(fn ($state) => config("countries.{$state}", $state))
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('order')
                    ->label('Sort Order')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
