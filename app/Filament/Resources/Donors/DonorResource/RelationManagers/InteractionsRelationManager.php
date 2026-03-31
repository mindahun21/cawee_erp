<?php

namespace App\Filament\Resources\Donors\DonorResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class InteractionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interactions';

    protected static ?string $recordTitleAttribute = 'subject';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('interaction_type')
                            ->label('Type')
                            ->options([
                                'call' => 'Call',
                                'email' => 'Email',
                                'meeting' => 'Meeting',
                                'note' => 'Note',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->native(false),
                        DateTimePicker::make('interaction_date')
                            ->label('Date & Time')
                            ->default(now())
                            ->required()
                            ->native(false),
                        TextInput::make('subject')
                            ->label('Subject')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Detailed Notes')
                            ->rows(4)
                            ->columnSpanFull(),
                        Select::make('created_by')
                            ->relationship('creator', 'name')
                            ->label('Assigned Staff')
                            ->default(auth()->id())
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                TextColumn::make('interaction_date')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('interaction_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'call' => 'info',
                        'email' => 'success',
                        'meeting' => 'warning',
                        'note' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('subject')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('creator.name')
                    ->label('Staff')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
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
