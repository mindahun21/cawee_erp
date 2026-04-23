<?php

namespace App\Filament\Resources\Finance\PettyCash;

use App\Filament\Resources\Finance\PettyCash\Pages\CreatePettyCashReplenishment;
use App\Filament\Resources\Finance\PettyCash\Pages\EditPettyCashReplenishment;
use App\Filament\Resources\Finance\PettyCash\Pages\ListPettyCashReplenishments;
use App\Filament\Resources\Finance\PettyCash\Pages\ViewPettyCashReplenishment;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\BankAccount;
use App\Models\Finance\PettyCashFund;
use App\Models\Finance\PettyCashReplenishment;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PettyCashReplenishmentResource extends Resource
{
    protected static ?string $model = PettyCashReplenishment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Petty Cash Replenishments';
    protected static ?int $navigationSort = 52;
    protected static ?string $recordTitleAttribute = 'replenishment_number';
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
            Section::make('Replenishment Request')
                ->description('Request funds to top up a petty cash fund.')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->columns(2)
                ->schema([
                    TextInput::make('replenishment_number')
                        ->label('Reference Number')
                        ->disabled()
                        ->dehydrated()
                        ->placeholder('Auto-generated'),

                    Select::make('petty_cash_fund_id')
                        ->label('Fund to Replenish')
                        ->options(PettyCashFund::activeOptions())
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $fund = PettyCashFund::find($state);
                                if ($fund) {
                                    $set('balance_before', $fund->current_balance);
                                    // Suggest replenishment of back up to max_limit
                                    $suggested = $fund->max_limit - $fund->current_balance;
                                    $set('amount_requested', max(0, $suggested));
                                }
                            }
                        }),

                    Select::make('accounting_period_id')
                        ->label('Accounting Period')
                        ->options(AccountingPeriod::openOptions())
                        ->required()
                        ->native(false),

                    DatePicker::make('request_date')
                        ->label('Request Date')
                        ->required()
                        ->native(false)
                        ->default(now()->toDateString()),

                    TextInput::make('balance_before')
                        ->label('Current Fund Balance')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->extraInputAttributes(['class' => 'font-mono'])
                        ->helperText('Balance at time of request — auto-filled from fund'),

                    TextInput::make('amount_requested')
                        ->label('Amount Requested')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->extraInputAttributes(['class' => 'font-mono']),

                    Select::make('bank_account_id')
                        ->label('Draw From (Bank Account)')
                        ->options(BankAccount::activeOptions())
                        ->native(false)
                        ->searchable()
                        ->nullable()
                        ->helperText('Bank account from which replenishment will be drawn'),

                    Textarea::make('justification')
                        ->label('Justification / Remarks')
                        ->rows(3)
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
                TextColumn::make('replenishment_number')
                    ->label('PCR #')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('request_date')->label('Date')->date()->sortable(),

                TextColumn::make('fund.fund_name')
                    ->label('Fund')
                    ->searchable(),

                TextColumn::make('balance_before')
                    ->label('Balance Before')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->color('warning'),

                TextColumn::make('amount_requested')
                    ->label('Requested')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono'),

                TextColumn::make('amount_approved')
                    ->label('Approved')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->color('success')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft'     => 'gray',
                        'pending'   => 'warning',
                        'approved'  => 'info',
                        'rejected'  => 'danger',
                        'disbursed' => 'success',
                        default     => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->options(PettyCashReplenishment::statuses()),
                SelectFilter::make('petty_cash_fund_id')
                    ->label('Fund')
                    ->options(fn () => PettyCashFund::pluck('fund_name', 'id')->toArray()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (PettyCashReplenishment $record) => $record->isDraft()),
                DeleteAction::make()->visible(fn (PettyCashReplenishment $record) => $record->isDraft()),
            ])
            ->defaultSort('request_date', 'desc');
    }

    // ── Infolist ──────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Request Details')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->columns(4)
                ->schema([
                    TextEntry::make('replenishment_number')->label('PCR #')->badge()->color('primary')->fontFamily('mono'),
                    TextEntry::make('request_date')->label('Date')->date(),
                    TextEntry::make('fund.fund_name')->label('Fund'),
                    TextEntry::make('status')->label('Status')->badge()
                        ->color(fn ($state) => match ($state) {
                            'draft' => 'gray', 'pending' => 'warning', 'approved' => 'info',
                            'rejected' => 'danger', 'disbursed' => 'success', default => 'gray',
                        }),
                    TextEntry::make('balance_before')->label('Balance Before')->numeric(decimalPlaces: 2)->fontFamily('mono')->color('warning'),
                    TextEntry::make('amount_requested')->label('Requested')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('amount_approved')->label('Approved')->numeric(decimalPlaces: 2)->fontFamily('mono')->color('success')->placeholder('—'),
                    TextEntry::make('bankAccount.account_name')->label('Source Bank Account')->placeholder('—'),
                    TextEntry::make('justification')->label('Justification')->columnSpanFull()->placeholder('—'),
                ]),

            Section::make('Approval Trail')
                ->icon('heroicon-o-user-circle')
                ->columns(3)
                ->schema([
                    TextEntry::make('requestedBy.name')->label('Requested By'),
                    TextEntry::make('approvedBy.name')->label('Approved By')->placeholder('—'),
                    TextEntry::make('approved_at')->label('Approved At')->dateTime()->placeholder('—'),
                    TextEntry::make('disbursedBy.name')->label('Disbursed By')->placeholder('—'),
                    TextEntry::make('disbursed_at')->label('Disbursed At')->dateTime()->placeholder('—'),
                    TextEntry::make('journalEntry.reference_number')->label('Journal Entry')->placeholder('—'),
                ]),
        ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListPettyCashReplenishments::route('/'),
            'create' => CreatePettyCashReplenishment::route('/create'),
            'view'   => ViewPettyCashReplenishment::route('/{record}'),
            'edit'   => EditPettyCashReplenishment::route('/{record}/edit'),
        ];
    }
}
