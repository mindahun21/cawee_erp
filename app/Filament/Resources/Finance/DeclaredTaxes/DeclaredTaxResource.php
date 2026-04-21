<?php

namespace App\Filament\Resources\Finance\DeclaredTaxes;

use App\Filament\Resources\Finance\DeclaredTaxes\Pages\CreateDeclaredTaxes;
use App\Filament\Resources\Finance\DeclaredTaxes\Pages\EditDeclaredTaxes;
use App\Filament\Resources\Finance\DeclaredTaxes\Pages\ListDeclaredTaxes;
use App\Filament\Resources\Finance\DeclaredTaxes\Pages\ViewDeclaredTaxes;
use App\Models\Finance\DeclaredTax;
use App\Models\Finance\TaxType;
use BackedEnum;
use Filament\Actions\Action as TblAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class DeclaredTaxResource extends Resource
{
    protected static ?string $model                          = DeclaredTax::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';
    protected static string|UnitEnum|null $navigationGroup  = 'Finance';
    protected static ?string $navigationLabel               = 'Declared Taxes';
    protected static ?int    $navigationSort                = 86;
    protected static ?string $slug                          = 'finance/declared-taxes';
    protected static bool $shouldSkipAuthorization          = true;

    public static function canViewAny(): bool  { $u = auth()->user(); return $u && ($u->isFinanceOfficer() || $u->isFinanceManager() || $u->isSuperAdmin()); }
    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($record): bool   { return $record->status === 'draft' && static::canViewAny(); }
    public static function canDelete($record): bool { return $record->status === 'draft' && static::canViewAny(); }
    public static function canView($record): bool   { return static::canViewAny(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tax Declaration Header')->icon('heroicon-o-receipt-percent')->columns(3)->schema([
                Select::make('tax_type_id')->label('Tax Type')->native(false)->required()->searchable()
                    ->options(fn () => TaxType::pluck('name', 'id')),
                TextInput::make('declaration_period')->label('Period (e.g. 2026-03 or Q1)')->required()->maxLength(20),
                DatePicker::make('declaration_date')->label('Declaration Date')->required()->default(now()),
                
                TextInput::make('total_income')->label('Total Base Amount')->numeric()->default(0),
                TextInput::make('taxable_income')->label('Taxable Amount')->numeric()->default(0),
                TextInput::make('tax_payable')->label('Tax Payable')->numeric()->required()->default(0),
            ]),
            Section::make('Payment Information')->icon('heroicon-o-banknotes')->columns(3)->schema([
                TextInput::make('paid_amount')->label('Paid Amount')->numeric()->default(0),
                DatePicker::make('payment_date')->label('Payment Date')->nullable(),
                TextInput::make('reference_number')->label('Payment Ref #')->nullable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('taxType.name')->label('Tax Type')->badge()->color('primary'),
                TextColumn::make('declaration_period')->label('Period')->sortable(),
                TextColumn::make('taxable_income')->label('Taxable Amount')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd(),
                TextColumn::make('tax_payable')->label('Tax Payable')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->weight('bold'),
                TextColumn::make('paid_amount')->label('Paid')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()
                    ->color(fn ($state, $record) => (float)$state < (float)$record->tax_payable ? 'danger' : 'success'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($state) => match($state) { 'draft' => 'gray', 'filed' => 'warning', 'paid' => 'success', default => 'gray' }),
            ])
            ->filters([SelectFilter::make('status')->options(DeclaredTax::statuses())])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn ($record) => $record->status === 'draft'),
                DeleteAction::make()->visible(fn ($record) => $record->status === 'draft'),
            ])
            ->defaultSort('declaration_date', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Declaration Details')->icon('heroicon-o-receipt-percent')->columns(3)->schema([
                TextEntry::make('taxType.name')->label('Tax Type')->badge()->color('primary'),
                TextEntry::make('declaration_period')->label('Period'),
                TextEntry::make('declaration_date')->label('Declaration Date')->date(),
                TextEntry::make('status')->label('Status')->badge()
                    ->color(fn ($state) => match($state) { 'draft' => 'gray', 'filed' => 'warning', 'paid' => 'success', default => 'gray' }),
            ]),
            Section::make('Amounts')->icon('heroicon-o-calculator')->columns(3)->schema([
                TextEntry::make('total_income')->label('Total Base Amount')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextEntry::make('taxable_income')->label('Taxable Amount')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextEntry::make('tax_payable')->label('Tax Payable')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold'),
            ]),
            Section::make('Payment Info')->icon('heroicon-o-banknotes')->columns(3)->schema([
                TextEntry::make('paid_amount')->label('Paid Amount')->numeric(decimalPlaces: 2)->fontFamily('mono')
                    ->color(fn ($state, $record) => (float)$state < (float)$record->tax_payable ? 'danger' : 'success'),
                TextEntry::make('payment_date')->label('Payment Date')->date()->placeholder('—'),
                TextEntry::make('reference_number')->label('Payment Ref #')->placeholder('—'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDeclaredTaxes::route('/'),
            'create' => CreateDeclaredTaxes::route('/create'),
            'view'   => ViewDeclaredTaxes::route('/{record}'),
            'edit'   => EditDeclaredTaxes::route('/{record}/edit'),
        ];
    }
}
