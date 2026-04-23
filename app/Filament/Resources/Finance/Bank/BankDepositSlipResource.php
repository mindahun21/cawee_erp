<?php

namespace App\Filament\Resources\Finance\Bank;

use App\Filament\Resources\Finance\Bank\BankDepositSlipResource\Pages\CreateBankDepositSlip;
use App\Filament\Resources\Finance\Bank\BankDepositSlipResource\Pages\EditBankDepositSlip;
use App\Filament\Resources\Finance\Bank\BankDepositSlipResource\Pages\ListBankDepositSlips;
use App\Filament\Resources\Finance\Bank\BankDepositSlipResource\Pages\ViewBankDepositSlip;
use App\Models\Currency;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankDepositSlip;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class BankDepositSlipResource extends Resource
{
    protected static ?string $model = BankDepositSlip::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;
    protected static string|UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Bank Deposit Slips';
    protected static ?int $navigationSort = 33;
    protected static ?string $recordTitleAttribute = 'slip_number';
    protected static bool $shouldSkipAuthorization = true;

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        if (! $u) {
            return true;
        }

        return $u->isFinanceOfficer() || $u->isFinanceManager() || $u->isSuperAdmin();
    }

    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($r): bool   { return ($r->status ?? null) === 'draft' && static::canViewAny(); }
    public static function canDelete($r): bool { return ($r->status ?? null) === 'draft' && static::canViewAny(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Bank Deposit Slip')->icon('heroicon-o-inbox-arrow-down')->columns(3)->schema([
                TextInput::make('slip_number')
                    ->label('Slip #')
                    ->disabled()
                    ->dehydrated()
                    ->placeholder('Auto-generated on save'),

                DatePicker::make('deposit_date')
                    ->label('Deposit Date')
                    ->required()
                    ->native(false)
                    ->default(now()->toDateString()),

                Select::make('bank_account_id')
                    ->label('Bank Account')
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->options(fn () => BankAccount::where('is_active', true)
                        ->orderBy('account_name')
                        ->get()
                        ->mapWithKeys(fn ($b) => [$b->id => "{$b->account_name} ({$b->bank_name})"])
                        ->toArray()),

                TextInput::make('total_amount')
                    ->label('Total Amount')
                    ->numeric()
                    ->required()
                    ->default(0),

                Select::make('currency_id')
                    ->label('Currency')
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->options(fn () => Currency::orderBy('code')->pluck('code', 'id')->toArray()),

                Select::make('status')
                    ->label('Status')
                    ->native(false)
                    ->options([
                        'draft' => 'Draft',
                        'deposited' => 'Deposited',
                    ])
                    ->default('draft'),

                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->columnSpanFull()
                    ->nullable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slip_number')->label('Slip #')->badge()->color('primary')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('deposit_date')->label('Date')->date()->sortable(),
                TextColumn::make('bankAccount.account_name')->label('Bank Account')->limit(25),
                TextColumn::make('total_amount')->label('Amount')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono'),
                TextColumn::make('currency.code')->label('CCY')->badge()->color('gray'),
                TextColumn::make('status')->label('Status')->badge()->color(fn ($s) => $s === 'deposited' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->options(['draft' => 'Draft', 'deposited' => 'Deposited']),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (BankDepositSlip $record) => $record->status === 'draft'),
                DeleteAction::make()->visible(fn (BankDepositSlip $record) => $record->status === 'draft'),
            ])
            ->defaultSort('deposit_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBankDepositSlips::route('/'),
            'create' => CreateBankDepositSlip::route('/create'),
            'view'   => ViewBankDepositSlip::route('/{record}'),
            'edit'   => EditBankDepositSlip::route('/{record}/edit'),
        ];
    }
}
