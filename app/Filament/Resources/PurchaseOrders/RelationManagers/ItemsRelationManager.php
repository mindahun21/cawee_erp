<?php

namespace App\Filament\Resources\PurchaseOrders\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                TextInput::make('model')
                    ->maxLength(255),
                TextInput::make('unit')
                    ->maxLength(50),
                TextInput::make('quantity')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->reactive()
                    ->afterStateUpdated(fn ($state, $set, $get) => $set('total_cost', $state * $get('unit_cost'))),
                TextInput::make('unit_cost')
                    ->numeric()
                    ->prefix('INR')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, $set, $get) => $set('total_cost', $state * $get('quantity'))),
                TextInput::make('total_cost')
                    ->numeric()
                    ->prefix('INR')
                    ->required()
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('model'),
                TextColumn::make('unit'),
                TextColumn::make('quantity'),
                TextColumn::make('unit_cost')
                    ->money('INR'),
                TextColumn::make('total_cost')
                    ->money('INR'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(fn ($livewire) => $livewire->dispatch('refreshForm')),
                AssociateAction::make()
                    ->after(fn ($livewire) => $livewire->dispatch('refreshForm')),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn ($livewire) => $livewire->dispatch('refreshForm')),
                DissociateAction::make()
                    ->after(fn ($livewire) => $livewire->dispatch('refreshForm')),
                DeleteAction::make()
                    ->after(fn ($livewire) => $livewire->dispatch('refreshForm')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make()
                        ->after(fn ($livewire) => $livewire->dispatch('refreshForm')),
                    DeleteBulkAction::make()
                        ->after(fn ($livewire) => $livewire->dispatch('refreshForm')),
                ]),
            ]);
    }
}
