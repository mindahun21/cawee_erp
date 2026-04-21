<?php

namespace App\Filament\Resources\Finance\Cash;

use App\Filament\Resources\Finance\Cash\Pages\CreatePaymentVoucher;
use App\Filament\Resources\Finance\Cash\Pages\EditPaymentVoucher;
use App\Filament\Resources\Finance\Cash\Pages\ListPaymentVouchers;
use App\Filament\Resources\Finance\Cash\Pages\ViewPaymentVoucher;
use App\Models\Currency;
use App\Models\Donor;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\BankAccount;
use App\Models\Finance\CostCenter;
use App\Models\Finance\PaymentVoucher;
use App\Models\Finance\TaxType;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class PaymentVoucherResource extends Resource
{
    protected static ?string $model = PaymentVoucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Payment Vouchers (PV)';
    protected static ?int $navigationSort = 41;
    protected static ?string $recordTitleAttribute = 'pv_number';
    protected static bool $shouldSkipAuthorization = true;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return true;
        }

        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }

    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($r): bool   { return $r->isDraft() && static::canViewAny(); }
    public static function canDelete($r): bool { return $r->isDraft() && static::canViewAny(); }

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Payment Details')
                ->description('Identify the payment, accouning period, and payee information.')
                ->icon('heroicon-o-arrow-up-tray')
                ->columns(3)
                ->schema([
                    TextInput::make('pv_number')
                        ->label('PV Number')
                        ->disabled()
                        ->dehydrated()
                        ->placeholder('Auto-generated on save'),

                    Select::make('accounting_period_id')
                        ->label('Accounting Period')
                        ->options(AccountingPeriod::openOptions())
                        ->required()
                        ->native(false)
                        ->searchable(),

                    DatePicker::make('payment_date')
                        ->label('Payment Date')
                        ->required()
                        ->native(false)
                        ->default(now()->toDateString()),

                    TextInput::make('payee_name')
                        ->label('Payee Name')
                        ->required()
                        ->maxLength(200)
                        ->columnSpan(2)
                        ->placeholder('Full name of the supplier or employee'),

                    Select::make('payee_type')
                        ->label('Payee Type')
                        ->options(PaymentVoucher::payeeTypes())
                        ->required()
                        ->native(false)
                        ->default('supplier'),

                    TextInput::make('payee_tin')
                        ->label('Payee TIN')
                        ->maxLength(30)
                        ->nullable()
                        ->helperText('Required for WHT documentation'),

                    TextInput::make('invoice_number')
                        ->label('Invoice Number')
                        ->maxLength(60)
                        ->nullable(),

                    DatePicker::make('invoice_date')
                        ->label('Invoice Date')
                        ->native(false)
                        ->nullable(),
                ]),

            Section::make('Payment Method')
                ->icon('heroicon-o-credit-card')
                ->columns(3)
                ->schema([
                    Select::make('bank_account_id')
                        ->label('Pay From (Bank Account)')
                        ->options(BankAccount::activeOptions())
                        ->native(false)
                        ->searchable()
                        ->nullable()
                        ->helperText('Leave blank for cash payments'),

                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options(PaymentVoucher::paymentMethods())
                        ->required()
                        ->native(false)
                        ->live()
                        ->default('bank_transfer'),

                    TextInput::make('cheque_number')
                        ->label('Cheque Number')
                        ->maxLength(30)
                        ->nullable()
                        ->visible(fn ($get) => $get('payment_method') === 'cheque'),

                    TextInput::make('transfer_reference')
                        ->label('Transfer Reference')
                        ->maxLength(60)
                        ->nullable()
                        ->visible(fn ($get) => $get('payment_method') === 'bank_transfer'),
                ]),

            Section::make('Amount & Tax')
                ->description('Enter the gross amount and applicable tax rates. Net amount is computed automatically.')
                ->icon('heroicon-o-calculator')
                ->columns(3)
                ->schema([
                    Select::make('currency_id')
                        ->label('Currency')
                        ->options(Currency::pluck('code', 'id')->toArray())
                        ->required()
                        ->native(false)
                        ->default(fn () => Currency::where('code', 'ETB')->value('id')),

                    TextInput::make('exchange_rate_to_base')
                        ->label('Exchange Rate to ETB')
                        ->numeric()
                        ->default(1)
                        ->extraInputAttributes(['class' => 'font-mono']),

                    TextInput::make('gross_amount')
                        ->label('Gross Amount')
                        ->required()
                        ->numeric()
                        ->minValue(0.01)
                        ->extraInputAttributes(['class' => 'font-mono'])
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $get, $set) {
                            self::recomputeTax($get, $set);
                        }),

                    // ── WHT ──────────────────────────────────────────
                    TextInput::make('withholding_tax_rate')
                        ->label('WHT Rate')
                        ->numeric()
                        ->suffix('%')
                        ->default(0)
                        ->helperText('e.g. 2 for 2%')
                        ->extraInputAttributes(['class' => 'font-mono'])
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $get, $set) {
                            $set('withholding_tax_rate', (float) $state / 100);
                            self::recomputeTax($get, $set);
                        }),

                    TextInput::make('withholding_tax_amount')
                        ->label('WHT Amount')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->extraInputAttributes(['class' => 'font-mono']),

                    // ── VAT ──────────────────────────────────────────
                    Select::make('vat_type')
                        ->label('VAT Type')
                        ->options(PaymentVoucher::vatTypes())
                        ->native(false)
                        ->default('none')
                        ->live()
                        ->afterStateUpdated(fn ($get, $set) => self::recomputeTax($get, $set)),

                    TextInput::make('vat_rate')
                        ->label('VAT Rate')
                        ->numeric()
                        ->suffix('%')
                        ->default(15)
                        ->extraInputAttributes(['class' => 'font-mono'])
                        ->visible(fn ($get) => in_array($get('vat_type'), ['collected', 'payable']))
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $get, $set) {
                            $set('vat_rate', (float) $state / 100);
                            self::recomputeTax($get, $set);
                        }),

                    TextInput::make('vat_amount')
                        ->label('VAT Amount')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->visible(fn ($get) => in_array($get('vat_type'), ['collected', 'payable']))
                        ->extraInputAttributes(['class' => 'font-mono']),

                    // ── Net ──────────────────────────────────────────
                    TextInput::make('net_amount')
                        ->label('Net Amount (to Pay)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->extraInputAttributes(['class' => 'font-mono font-bold'])
                        ->helperText('Gross − WHT (amount actually disbursed)'),
                ]),

            Section::make('NGO Dimensions')
                ->icon('heroicon-o-tag')
                ->columns(2)
                ->schema([
                    Select::make('project_id')
                        ->label('Project')
                        ->options(fn () => Project::orderBy('project_name')
                            ->pluck('project_name', 'id')
                            ->toArray()
                        )
                        ->native(false)->searchable()->nullable(),

                    Select::make('cost_center_id')
                        ->label('Cost Center')
                        ->options(fn () => CostCenter::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                        ->native(false)->searchable()->nullable(),

                    Select::make('donor_id')
                        ->label('Donor')
                        ->options(fn () => Donor::orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn ($d) => [$d->id => $d->full_name])
                            ->toArray()
                        )
                        ->native(false)->searchable()->nullable(),

                    TextInput::make('activity_code')->label('Activity Code')->maxLength(50)->nullable(),

                    TextInput::make('donor_code')->label('Donor Code')->maxLength(50)->nullable(),

                    Textarea::make('notes')->label('Notes')->rows(2)->columnSpanFull()->nullable(),
                ]),
        ]);
    }

    /**
     * Recompute WHT, VAT, and net amounts from form state.
     */
    private static function recomputeTax($get, $set): void
    {
        $gross  = (float) ($get('gross_amount') ?? 0);
        $whtRate = (float) ($get('withholding_tax_rate') ?? 0);
        $vatType = $get('vat_type') ?? 'none';
        $vatRate = (float) ($get('vat_rate') ?? 0);

        $whtAmount = round($gross * $whtRate, 2);
        $vatAmount = in_array($vatType, ['collected', 'payable'])
            ? round($gross * $vatRate, 2)
            : 0.0;
        $netAmount = round($gross - $whtAmount, 2);

        $set('withholding_tax_amount', $whtAmount);
        $set('vat_amount', $vatAmount);
        $set('net_amount', $netAmount);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pv_number')
                    ->label('PV #')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_date')->label('Date')->date()->sortable(),

                TextColumn::make('payee_name')->label('Payee')->searchable()->limit(30),

                TextColumn::make('payee_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PaymentVoucher::payeeTypes()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'supplier' => 'warning',
                        'employee' => 'info',
                        default    => 'gray',
                    }),

                TextColumn::make('gross_amount')
                    ->label('Gross')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono'),

                TextColumn::make('withholding_tax_amount')
                    ->label('WHT')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->color('warning'),

                TextColumn::make('net_amount')
                    ->label('Net Payable')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->color('danger')
                    ->weight('semibold'),

                TextColumn::make('currency.code')->label('CCY')->badge()->color('gray'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft'            => 'gray',
                        'pending_approval' => 'warning',
                        'approved'         => 'info',
                        'posted'           => 'success',
                        'rejected'         => 'danger',
                        default            => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->options(PaymentVoucher::statuses()),
                SelectFilter::make('payee_type')->label('Payee Type')->options(PaymentVoucher::payeeTypes()),
                SelectFilter::make('payment_method')->label('Method')->options(PaymentVoucher::paymentMethods()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (PaymentVoucher $record) => $record->isDraft()),
            ])
            ->defaultSort('payment_date', 'desc');
    }

    // ── Infolist ──────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment Header')
                ->icon('heroicon-o-arrow-up-tray')
                ->columns(4)
                ->schema([
                    TextEntry::make('pv_number')->label('PV #')->badge()->color('primary')->fontFamily('mono'),
                    TextEntry::make('payment_date')->label('Payment Date')->date(),
                    TextEntry::make('period.name')->label('Period'),
                    TextEntry::make('status')->label('Status')->badge()
                        ->color(fn ($state) => match ($state) {
                            'draft' => 'gray', 'pending_approval' => 'warning',
                            'approved' => 'info', 'posted' => 'success', 'rejected' => 'danger',
                            default => 'gray',
                        }),
                    TextEntry::make('payee_name')->label('Payee')->columnSpan(2),
                    TextEntry::make('payee_type')->label('Payee Type')->badge()
                        ->formatStateUsing(fn ($state) => PaymentVoucher::payeeTypes()[$state] ?? $state),
                    TextEntry::make('payee_tin')->label('TIN')->placeholder('—'),
                ]),

            Section::make('Amount & Tax')
                ->icon('heroicon-o-calculator')
                ->columns(4)
                ->schema([
                    TextEntry::make('gross_amount')->label('Gross Amount')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('withholding_tax_amount')->label('WHT')->numeric(decimalPlaces: 2)->fontFamily('mono')->color('warning'),
                    TextEntry::make('vat_amount')->label('VAT')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('net_amount')->label('Net Payable')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold')->color('danger'),
                    TextEntry::make('currency.code')->label('Currency')->badge()->color('primary'),
                    TextEntry::make('payment_method')->label('Method')->badge()
                        ->formatStateUsing(fn ($state) => PaymentVoucher::paymentMethods()[$state] ?? $state),
                    TextEntry::make('cheque_number')->label('Cheque #')->placeholder('—'),
                    TextEntry::make('transfer_reference')->label('Transfer Ref')->placeholder('—'),
                ]),

            Section::make('NGO Dimensions')
                ->icon('heroicon-o-tag')
                ->columns(3)
                ->schema([
                    TextEntry::make('donor.name')->label('Donor')->placeholder('—'),
                    TextEntry::make('project.name')->label('Project')->placeholder('—'),
                    TextEntry::make('costCenter.name')->label('Cost Center')->placeholder('—'),
                    TextEntry::make('activity_code')->label('Activity Code')->placeholder('—'),
                    TextEntry::make('donor_code')->label('Donor Code')->placeholder('—'),
                ]),

            Section::make('Workflow')
                ->icon('heroicon-o-user-circle')
                ->columns(3)
                ->schema([
                    TextEntry::make('preparedBy.name')->label('Prepared By'),
                    TextEntry::make('approvedBy.name')->label('Approved By')->placeholder('—'),
                    TextEntry::make('approved_at')->label('Approved At')->dateTime()->placeholder('—'),
                    TextEntry::make('posted_at')->label('Posted At')->dateTime()->placeholder('Not yet posted'),
                    TextEntry::make('journalEntry.reference_number')->label('Journal Entry')->placeholder('Not yet posted'),
                    TextEntry::make('invoice_number')->label('Invoice #')->placeholder('—'),
                ]),
        ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListPaymentVouchers::route('/'),
            'create' => CreatePaymentVoucher::route('/create'),
            'view'   => ViewPaymentVoucher::route('/{record}'),
            'edit'   => EditPaymentVoucher::route('/{record}/edit'),
        ];
    }
}
