<?php

namespace App\Filament\Resources\Donors\DonorResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Models\Currency;

class PledgesRelationManager extends RelationManager
{
    protected static string $relationship = 'pledges';

    protected static ?string $recordTitleAttribute = 'total_amount';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('campaign_id')
                            ->relationship('campaign', 'title')
                            ->searchable()
                            ->preload(),
                        TextInput::make('total_amount')
                            ->label('Pledge Amount')
                            ->required()
                            ->numeric()
                            ->prefix('ETB'),
                        Select::make('currency_id')
                            ->relationship('currency', 'name')
                            ->required()
                            ->default(fn () => Currency::where('code', 'ETB')->first()?->id ?? 1),
                        DatePicker::make('start_date')
                            ->required()
                            ->default(now()),
                        DatePicker::make('end_date'),
                        Select::make('frequency')
                            ->options([
                                'one_time' => 'One Time',
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'yearly' => 'Yearly',
                            ])
                            ->default('one_time')
                            ->required(),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'overdue' => 'Overdue',
                            ])
                            ->default('active')
                            ->required(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('total_amount')
            ->columns([
                TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('ETB')
                    ->sortable(),
                TextColumn::make('fulfilled_amount')
                    ->label('Fulfilled')
                    ->money('ETB')
                    ->badge()
                    ->color(fn ($state, $record) => $state >= $record->total_amount ? 'success' : 'warning'),
                TextColumn::make('percent_fulfilled')
                    ->label('%')
                    ->suffix('%')
                    ->numeric(1),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'gray',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
