<?php

namespace App\Filament\Resources\Donations;

use App\Filament\Resources\Donations\DonationTypeResource\Pages;
use App\Models\DonationType;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class DonationTypeResource extends Resource
{
    protected static ?string $model = DonationType::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-gift';

    // protected static string | \UnitEnum | null $navigationGroup = 'Donations';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Basic Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (empty($state)) return;
                                $set('code', Str::slug($state, '_'));
                            }),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->regex('/^[a-z0-9_]+$/')
                            ->helperText('Unique identifier (lowercase, underscores)'),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(3),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Available for use in donations'),
                    ]),
                \Filament\Schemas\Components\Section::make('Features & Configuration')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Recurring Donation')
                            ->helperText('Enable recurring donations support'),
                        Forms\Components\Toggle::make('has_pledge_management')
                            ->label('Pledge Management')
                            ->helperText('Enable tracking pledged amounts')
                            ->reactive(),
                        Forms\Components\Toggle::make('is_in_kind')
                            ->label('In-Kind Donation')
                            ->helperText('Non-monetary donations')
                            ->reactive(),
                        Forms\Components\Toggle::make('supports_gift_aid')
                            ->label('Gift Aid Support')
                            ->helperText('Supports Gift Aid tax reclaim'),
                    ]),
                \Filament\Schemas\Components\Section::make('Additional Settings')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('requires_pledge_amount')
                            ->label('Requires Pledge Amount')
                            ->visible(fn ($get) => $get('has_pledge_management')),
                        Forms\Components\Toggle::make('requires_in_kind_description')
                            ->label('Requires Description')
                            ->visible(fn ($get) => $get('is_in_kind')),
                        Forms\Components\Toggle::make('tax_deductible')
                            ->label('Tax Deductible')
                            ->default(true),
                        Forms\Components\TextInput::make('receipt_template')
                            ->label('Receipt Template Path')
                            ->placeholder('e.g., receipts.templates.default'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (DonationType $record) => Str::limit($record->description, 50)),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('features')
                    ->label('Features')
                    ->options([
                        'heroicon-o-arrow-path' => fn ($state, $record) => $record->is_recurring,
                        'heroicon-o-calendar' => fn ($state, $record) => $record->has_pledge_management,
                        'heroicon-o-archive-box' => fn ($state, $record) => $record->is_in_kind,
                    ])
                    ->getStateUsing(function (DonationType $record) {
                        $features = [];
                        if ($record->is_recurring) $features[] = 'recurring';
                        if ($record->has_pledge_management) $features[] = 'pledge';
                        if ($record->is_in_kind) $features[] = 'in_kind';
                        return $features;
                    }), 
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\Filter::make('is_recurring')
                    ->query(fn ($query) => $query->where('is_recurring', true))
                    ->label('Recurring Only'),
                Tables\Filters\Filter::make('has_pledge_management')
                    ->query(fn ($query) => $query->where('has_pledge_management', true))
                    ->label('Pledge Only'),
                Tables\Filters\Filter::make('is_in_kind')
                    ->query(fn ($query) => $query->where('is_in_kind', true))
                    ->label('In-Kind Only'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDonationTypes::route('/'),
        ];
    }
}
