<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Text;

class ViewPurchaseRequest extends ViewRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Information')
                            ->schema([
                                Section::make('Purchase Request Information')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('code')
                                            ->label('Purchase Request Code'),
                                        TextEntry::make('name')
                                            ->label('Purchase Request Name'),
                                        TextEntry::make('requester.name')
                                            ->label('Requester'),
                                        TextEntry::make('created_at')
                                            ->label('Request time')
                                            ->dateTime(),
                                        TextEntry::make('description')
                                            ->columnSpanFull()
                                            ->placeholder('No description provided'),
                                    ]),
                                Section::make('Detail')
                                    ->schema([
                                        RepeatableEntry::make('items')
                                            ->schema([
                                                Grid::make(7)
                                                    ->schema([
                                                        TextEntry::make('item.name')
                                                            ->label('Item')
                                                            ->columnSpan(2),
                                                        TextEntry::make('quantity')
                                                            ->label('Quantity'),
                                                        TextEntry::make('unit_price')
                                                            ->label('Unit price')
                                                            ->money('USD'),
                                                        TextEntry::make('subtotal')
                                                            ->label('Subtotal (before tax)')
                                                            ->money('USD'),
                                                        TextEntry::make('tax.name')
                                                            ->label('Tax'),
                                                        TextEntry::make('tax_value')
                                                            ->label('Tax Value')
                                                            ->money('USD'),
                                                        TextEntry::make('total')
                                                            ->label('Total')
                                                            ->money('USD'),
                                                    ]),
                                            ])
                                            ->columns(1),
                                        
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('subtotal')
                                                    ->label('Subtotal')
                                                    ->money('USD'),
                                                TextEntry::make('tax_amount')
                                                    ->label('VAT (15.00%)')
                                                    ->money('USD'),
                                                TextEntry::make('total_amount')
                                                    ->label('Total')
                                                    ->money('USD')
                                                    ->weight('bold'),
                                            ]),
                                    ]),
                                Section::make('Approval Info')
                                    ->schema([
                                        TextEntry::make('status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'draft' => 'gray',
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'ordered' => 'info',
                                                'completed' => 'primary',
                                                default => 'gray',
                                            }),
                                    ]),
                            ]),
                        Tabs\Tab::make('Attachments')
                            ->schema([
                                Text::make('No attachments uploaded yet.'),
                            ]),
                        Tabs\Tab::make('Compare Quotes(0)')
                            ->schema([
                                Text::make('No quotes available to compare.'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
