<?php

namespace App\Filament\Resources\Finance\Perdiem;

use App\Models\Finance\PerdiemTaxRule;
use App\Models\Finance\PerdiemType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PerdiemTaxRuleResource extends Resource
{
    protected static ?string $model = PerdiemTaxRule::class;
    protected static bool $shouldRegisterNavigation = false;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationParentItem = 'Settings';
    protected static ?string $navigationLabel = 'Per Diem Tax Rules';
    protected static ?string $slug = 'finance/settings/perdiem-tax-rules';
    protected static bool $shouldSkipAuthorization = true;

    public static function canViewAny(): bool { return true; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tax Rule Configuration')->columns(2)->schema([
                Select::make('perdiem_type_id')->label('Per Diem Type')->native(false)->required()->searchable()
                    ->options(fn () => PerdiemType::pluck('name', 'id')),
                Select::make('tax_type')->native(false)->required()
                    ->options(['income_tax' => 'Income Tax', 'withholding' => 'Withholding', 'none' => 'None']),
                TextInput::make('threshold_amount')->numeric()->required()->default(0),
                TextInput::make('tax_rate')->numeric()->required()->default(0)->helperText('Decimal (e.g. 0.15 for 15%)'),
                DatePicker::make('effective_date')->required()->default(now()),
                DatePicker::make('expiry_date')->nullable(),
                Textarea::make('notes')->nullable()->columnSpanFull(),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('perdiemType.name')->searchable()->sortable(),
                TextColumn::make('tax_type')->badge()->color('warning'),
                TextColumn::make('threshold_amount')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextColumn::make('tax_rate')->numeric(decimalPlaces: 4)->fontFamily('mono'),
                TextColumn::make('effective_date')->date(),
                TextColumn::make('expiry_date')->date()->placeholder('Indefinite'),
            ])
            ->defaultSort('effective_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Finance\Perdiem\PerdiemTaxRuleResource\Pages\ManagePerdiemTaxRules::route('/'),
        ];
    }
}
