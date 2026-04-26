<?php

namespace App\Filament\Resources\Finance\Perdiem;

use App\Models\Finance\PerdiemSettlement;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class PerdiemSettlementResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = PerdiemSettlement::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-refund';
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationParentItem = 'Per Diem';
    protected static ?string $navigationLabel = 'Settlements';
    protected static ?string $slug = 'finance/perdiem/settlements';
    protected static bool $shouldSkipAuthorization = true;

    public static function canViewAny(): bool { return true; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Settlement Details')->columns(2)->schema([
                Select::make('perdiem_request_id')->relationship('perdiemRequest', 'reference')->searchable()->required()->native(false),
                DatePicker::make('settlement_date')->required()->default(now()),
                TextInput::make('actual_days')->numeric()->required(),
                TextInput::make('actual_amount')->numeric()->required(),
                TextInput::make('advance_paid')->numeric()->required(),
                TextInput::make('balance_to_recover')->numeric()->required(),
                Textarea::make('notes')->nullable()->columnSpanFull(),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('perdiemRequest.reference')->label('Request Ref')->searchable()->sortable(),
                TextColumn::make('settlement_date')->date()->sortable(),
                TextColumn::make('actual_days')->numeric(),
                TextColumn::make('actual_amount')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextColumn::make('advance_paid')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextColumn::make('balance_to_recover')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextColumn::make('status')->badge()
                    ->color(fn ($state) => match($state) { 'draft' => 'gray', 'approved' => 'success', 'closed' => 'gray', default => 'gray' }),
            ])
            ->defaultSort('settlement_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Finance\Perdiem\PerdiemSettlementResource\Pages\ManagePerdiemSettlements::route('/'),
        ];
    }
}
