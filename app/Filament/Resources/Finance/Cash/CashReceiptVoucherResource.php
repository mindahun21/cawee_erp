<?php

namespace App\Filament\Resources\Finance\Cash;

use App\Filament\Resources\Finance\Cash\Pages\CreateCashReceiptVoucher;
use App\Filament\Resources\Finance\Cash\Pages\EditCashReceiptVoucher;
use App\Filament\Resources\Finance\Cash\Pages\ListCashReceiptVouchers;
use App\Filament\Resources\Finance\Cash\Pages\ViewCashReceiptVoucher;
use App\Models\Currency;
use App\Models\Donor;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\BankAccount;
use App\Models\Finance\CashReceiptVoucher;
use App\Models\Finance\CostCenter;
use App\Models\Project;
use App\Models\User;
use App\Services\Finance\VoucherService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashReceiptVoucherResource extends Resource
{
    protected static ?string $model = CashReceiptVoucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Cash Receipts (CRV)';
    protected static ?int $navigationSort = 40;
    protected static ?string $recordTitleAttribute = 'crv_number';
    protected static bool $shouldSkipAuthorization = true;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }

    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($r): bool   { return $r->isDraft() && static::canViewAny(); }
    public static function canDelete($r): bool { return $r->isDraft() && static::canViewAny(); }

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Receipt Details')
                ->description('Identify the receipt, its period, and the source of income.')
                ->icon('heroicon-o-arrow-down-tray')
                ->columns(3)
                ->schema([
                    TextInput::make('crv_number')
                        ->label('CRV Number')
                        ->disabled()
                        ->dehydrated()
                        ->placeholder('Auto-generated on save'),

                    Select::make('accounting_period_id')
                        ->label('Accounting Period')
                        ->options(AccountingPeriod::openOptions())
                        ->required()
                        ->native(false)
                        ->searchable(),

                    DatePicker::make('receipt_date')
                        ->label('Receipt Date')
                        ->required()
                        ->native(false)
                        ->default(now()->toDateString()),

                    TextInput::make('received_from')
                        ->label('Received From')
                        ->required()
                        ->maxLength(200)
                        ->columnSpan(2)
                        ->placeholder('Name of donor, organization, or payer'),

                    Select::make('income_type')
                        ->label('Income Type')
                        ->options(CashReceiptVoucher::incomeTypes())
                        ->required()
                        ->native(false)
                        ->default('donation'),
                ]),

            Section::make('Amount & Currency')
                ->icon('heroicon-o-banknotes')
                ->columns(3)
                ->schema([
                    TextInput::make('amount')
                        ->label('Amount')
                        ->required()
                        ->numeric()
                        ->minValue(0.01)
                        ->extraInputAttributes(['class' => 'font-mono'])
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $get, $set) {
                            $rate = (float) ($get('exchange_rate_to_base') ?? 1);
                            $set('amount_in_base', round((float) $state * $rate, 2));
                        }),

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
                        ->extraInputAttributes(['class' => 'font-mono'])
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $get, $set) {
                            $amount = (float) ($get('amount') ?? 0);
                            $set('amount_in_base', round($amount * (float) $state, 2));
                        }),

                    TextInput::make('amount_in_base')
                        ->label('Amount in ETB')
                        ->numeric()
                        ->extraInputAttributes(['class' => 'font-mono'])
                        ->helperText('Auto-computed from Amount × Rate'),

                    Select::make('bank_account_id')
                        ->label('Deposited Into')
                        ->options(BankAccount::activeOptions())
                        ->native(false)
                        ->searchable()
                        ->nullable()
                        ->helperText('Leave blank if received as cash on hand'),
                ]),

            Section::make('NGO Dimensions')
                ->description('Tag this receipt to a donor, project, cost center and activity.')
                ->icon('heroicon-o-tag')
                ->columns(2)
                ->schema([
                    Select::make('donor_id')
                        ->label('Donor')
                        ->options(fn () => Donor::orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn ($d) => [$d->id => $d->full_name])
                            ->toArray()
                        )
                        ->native(false)
                        ->searchable()
                        ->nullable(),

                    Select::make('project_id')
                        ->label('Project')
                        ->options(fn () => Project::orderBy('project_name')
                            ->pluck('project_name', 'id')
                            ->toArray()
                        )
                        ->native(false)
                        ->searchable()
                        ->nullable(),

                    Select::make('cost_center_id')
                        ->label('Cost Center')
                        ->options(fn () => CostCenter::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                        ->native(false)
                        ->searchable()
                        ->nullable(),

                    TextInput::make('activity_code')
                        ->label('Activity Code')
                        ->maxLength(50)
                        ->nullable(),

                    TextInput::make('donor_code')
                        ->label('Donor Code')
                        ->maxLength(50)
                        ->nullable(),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2)
                        ->columnSpanFull()
                        ->nullable(),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('crv_number')
                    ->label('CRV #')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('receipt_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('received_from')
                    ->label('Received From')
                    ->searchable()
                    ->limit(35),

                TextColumn::make('income_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => CashReceiptVoucher::incomeTypes()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'grant'    => 'success',
                        'donation' => 'info',
                        'service'  => 'warning',
                        'interest' => 'primary',
                        default    => 'gray',
                    }),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono'),

                TextColumn::make('currency.code')
                    ->label('CCY')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('amount_in_base')
                    ->label('ETB')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->color('success'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => CashReceiptVoucher::statuses()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'draft'            => 'gray',
                        'pending_approval' => 'warning',
                        'approved'         => 'info',
                        'posted'           => 'success',
                        'rejected'         => 'danger',
                        default            => 'gray',
                    }),

                TextColumn::make('period.name')
                    ->label('Period')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(CashReceiptVoucher::statuses()),

                SelectFilter::make('income_type')
                    ->label('Income Type')
                    ->options(CashReceiptVoucher::incomeTypes()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (CashReceiptVoucher $record) => $record->isDraft()),
            ])
            ->defaultSort('receipt_date', 'desc');
    }

    // ── Infolist ──────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Receipt Details')
                ->icon('heroicon-o-arrow-down-tray')
                ->columns(4)
                ->schema([
                    TextEntry::make('crv_number')->label('CRV #')->badge()->color('primary')->fontFamily('mono'),
                    TextEntry::make('receipt_date')->label('Date')->date(),
                    TextEntry::make('income_type')
                        ->label('Income Type')
                        ->badge()
                        ->formatStateUsing(fn ($state) => CashReceiptVoucher::incomeTypes()[$state] ?? $state),
                    TextEntry::make('status')
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
                    TextEntry::make('received_from')->label('Received From')->columnSpan(2),
                    TextEntry::make('period.name')->label('Period'),
                    TextEntry::make('bankAccount.account_name')->label('Bank Account')->placeholder('Cash on Hand'),
                ]),

            Section::make('Amount')
                ->icon('heroicon-o-banknotes')
                ->columns(3)
                ->schema([
                    TextEntry::make('amount')->label('Amount')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('currency.code')->label('Currency')->badge()->color('primary'),
                    TextEntry::make('exchange_rate_to_base')->label('Exchange Rate')->fontFamily('mono'),
                    TextEntry::make('amount_in_base')->label('Amount in ETB')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold')->color('success'),
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
                    TextEntry::make('notes')->label('Notes')->placeholder('—'),
                ]),

            Section::make('Workflow')
                ->icon('heroicon-o-user-circle')
                ->columns(3)
                ->schema([
                    TextEntry::make('preparedBy.name')->label('Prepared By'),
                    TextEntry::make('approvedBy.name')->label('Approved By')->placeholder('Not yet approved'),
                    TextEntry::make('approved_at')->label('Approved At')->dateTime()->placeholder('—'),
                    TextEntry::make('posted_at')->label('Posted At')->dateTime()->placeholder('Not yet posted'),
                    TextEntry::make('journalEntry.reference_number')->label('Journal Entry')->placeholder('Not yet posted'),
                ]),
        ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListCashReceiptVouchers::route('/'),
            'create' => CreateCashReceiptVoucher::route('/create'),
            'view'   => ViewCashReceiptVoucher::route('/{record}'),
            'edit'   => EditCashReceiptVoucher::route('/{record}/edit'),
        ];
    }
}
