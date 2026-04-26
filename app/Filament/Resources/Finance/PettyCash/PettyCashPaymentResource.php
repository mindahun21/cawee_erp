<?php

namespace App\Filament\Resources\Finance\PettyCash;

use App\Filament\Resources\Finance\PettyCash\Pages\CreatePettyCashPayment;
use App\Filament\Resources\Finance\PettyCash\Pages\EditPettyCashPayment;
use App\Filament\Resources\Finance\PettyCash\Pages\ListPettyCashPayments;
use App\Filament\Resources\Finance\PettyCash\Pages\ViewPettyCashPayment;
use App\Models\Donor;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\CostCenter;
use App\Models\Finance\PettyCashFund;
use App\Models\Finance\PettyCashPayment;
use App\Models\Project;
use App\Models\User;
use App\Services\Finance\PettyCashService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class PettyCashPaymentResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = PettyCashPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Petty Cash Payments';
    protected static ?int $navigationSort = 51;
    protected static ?string $recordTitleAttribute = 'payment_number';
    protected static bool $shouldSkipAuthorization = true;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }
    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($r): bool   { return $r->isPending() && static::canViewAny(); }
    public static function canDelete($r): bool { return $r->isPending() && static::canViewAny(); }

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment Details')
                ->description('Record a petty cash expense from a fund.')
                ->icon('heroicon-o-receipt-percent')
                ->columns(3)
                ->schema([
                    TextInput::make('payment_number')
                        ->label('Payment Number')
                        ->disabled()
                        ->dehydrated()
                        ->placeholder('Auto-generated on save'),

                    Select::make('petty_cash_fund_id')
                        ->label('Petty Cash Fund')
                        ->options(PettyCashFund::activeOptions())
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->helperText('Only active funds are listed.'),

                    Select::make('accounting_period_id')
                        ->label('Accounting Period')
                        ->options(AccountingPeriod::openOptions())
                        ->required()
                        ->native(false),

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
                        ->placeholder('Name of recipient'),

                    TextInput::make('amount')
                        ->label('Amount')
                        ->required()
                        ->numeric()
                        ->minValue(0.01)
                        ->extraInputAttributes(['class' => 'font-mono']),

                    TextInput::make('receipt_number')
                        ->label('Receipt / Invoice Number')
                        ->maxLength(60)
                        ->nullable(),

                    Textarea::make('description')
                        ->label('Description of Expense')
                        ->required()
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ]),

            Section::make('Expense Coding')
                ->description('Classify this expense for reporting and GL posting.')
                ->icon('heroicon-o-tag')
                ->columns(2)
                ->schema([
                    Select::make('chart_of_account_id')
                        ->label('Expense Account')
                        ->options(fn () => ChartOfAccount::where('is_active', true)
                            ->whereHas('accountType', fn ($q) => $q->where('classification', 'expense'))
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn ($a) => [$a->id => "[{$a->code}] {$a->name}"])
                            ->toArray()
                        )
                        ->native(false)
                        ->searchable()
                        ->nullable()
                        ->helperText('The expense GL account to debit in consolidated journals'),

                    TextInput::make('activity_code')
                        ->label('Activity Code')
                        ->maxLength(50)
                        ->nullable(),

                    Select::make('project_id')
                        ->label('Project')
                        ->options(fn () => Project::orderBy('project_name')
                            ->pluck('project_name', 'id')
                            ->toArray()
                        )
                        ->native(false)->searchable()->nullable(),

                    Select::make('donor_id')
                        ->label('Donor')
                        ->options(fn () => Donor::orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn ($d) => [$d->id => $d->full_name])
                            ->toArray()
                        )
                        ->native(false)->searchable()->nullable(),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_number')
                    ->label('Payment #')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_date')->label('Date')->date()->sortable(),

                TextColumn::make('fund.fund_name')
                    ->label('Fund')
                    ->searchable(),

                TextColumn::make('payee_name')->label('Payee')->searchable()->limit(30),

                TextColumn::make('description')->label('Description')->limit(40)->placeholder('—'),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->color('danger'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->options(PettyCashPayment::statuses()),
                SelectFilter::make('petty_cash_fund_id')
                    ->label('Fund')
                    ->options(fn () => PettyCashFund::pluck('fund_name', 'id')->toArray()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (PettyCashPayment $record) => $record->isPending()),
                DeleteAction::make()->visible(fn (PettyCashPayment $record) => $record->isPending()),
            ])
            ->defaultSort('payment_date', 'desc');
    }

    // ── Infolist ──────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment Details')
                ->icon('heroicon-o-receipt-percent')
                ->columns(4)
                ->schema([
                    TextEntry::make('payment_number')->label('Payment #')->badge()->color('primary')->fontFamily('mono'),
                    TextEntry::make('payment_date')->label('Date')->date(),
                    TextEntry::make('status')->label('Status')->badge()
                        ->color(fn ($state) => match ($state) {
                            'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', default => 'gray',
                        }),
                    TextEntry::make('fund.fund_name')->label('Fund'),
                    TextEntry::make('payee_name')->label('Payee')->columnSpan(2),
                    TextEntry::make('amount')->label('Amount')->numeric(decimalPlaces: 2)->fontFamily('mono')->color('danger'),
                    TextEntry::make('receipt_number')->label('Receipt #')->placeholder('—'),
                    TextEntry::make('description')->label('Description')->columnSpanFull(),
                ]),
            Section::make('Expense Coding')
                ->icon('heroicon-o-tag')
                ->columns(3)
                ->schema([
                    TextEntry::make('expenseAccount.name')->label('Expense Account')->placeholder('—'),
                    TextEntry::make('activity_code')->label('Activity Code')->placeholder('—'),
                    TextEntry::make('project.name')->label('Project')->placeholder('—'),
                    TextEntry::make('donor.name')->label('Donor')->placeholder('—'),
                    TextEntry::make('preparedBy.name')->label('Prepared By'),
                    TextEntry::make('approvedBy.name')->label('Approved By')->placeholder('—'),
                ]),
        ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListPettyCashPayments::route('/'),
            'create' => CreatePettyCashPayment::route('/create'),
            'view'   => ViewPettyCashPayment::route('/{record}'),
            'edit'   => EditPettyCashPayment::route('/{record}/edit'),
        ];
    }
}
