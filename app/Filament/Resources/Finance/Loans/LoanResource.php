<?php

namespace App\Filament\Resources\Finance\Loans;

use App\Filament\Resources\Finance\Loans\Pages\CreateLoans;
use App\Filament\Resources\Finance\Loans\Pages\EditLoans;
use App\Filament\Resources\Finance\Loans\Pages\ListLoans;
use App\Filament\Resources\Finance\Loans\Pages\ViewLoans;
use App\Models\Finance\BankAccount;
use App\Models\Finance\Loan;
use BackedEnum;
use Filament\Actions\Action as TblAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;
use App\Traits\BelongsToModule;

class LoanResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model                          = Loan::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static string|UnitEnum|null $navigationGroup  = 'Finance';
    protected static ?string $navigationLabel               = 'Loans';
    protected static ?int    $navigationSort                = 65;
    protected static ?string $slug                          = 'finance/loans';
    protected static bool $shouldSkipAuthorization           = true;

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        if (! $u) {
            return true;
        }

        return $u->isFinanceOfficer() || $u->isFinanceManager() || $u->isSuperAdmin();
    }
    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($r): bool   { return static::canViewAny(); }
    public static function canDelete($r): bool { return static::canViewAny(); }
    public static function canView($r): bool   { return static::canViewAny(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Loan Details')
                ->icon('heroicon-o-credit-card')->columns(3)
                ->schema([
                    TextInput::make('loan_reference')->label('Loan Reference')
                        ->disabled()->dehydrated()->placeholder('Auto-generated'),
                    Select::make('borrower_type')->label('Borrower Type')
                        ->options(Loan::borrowerTypes())->required()->native(false),
                    TextInput::make('borrower_id')->label('Borrower ID (Employee/Org)')
                        ->numeric()->required()->helperText('Enter the employee or organization ID'),
                    TextInput::make('loan_purpose')->label('Loan Purpose')->nullable()->columnSpan(3),
                ]),

            Section::make('Financial Terms')
                ->icon('heroicon-o-banknotes')->columns(4)
                ->schema([
                    TextInput::make('principal_amount')->label('Principal Amount')->numeric()->required(),
                    TextInput::make('interest_rate')->label('Annual Interest Rate')
                        ->numeric()->default(0)->helperText('e.g. 0.1 = 10%'),
                    TextInput::make('tenor_months')->label('Tenor (Months)')->numeric()->required(),
                    Select::make('currency_id')->label('Currency')->required()->native(false)
                        ->options(fn () => \App\Models\Currency::orderBy('code')->pluck('code', 'id')),
                    DatePicker::make('disbursement_date')->label('Disbursement Date')->required(),
                    DatePicker::make('start_repayment_date')->label('First Repayment Date')->required(),
                    Select::make('bank_account_id')->label('Disbursement Account')->required()->native(false)
                        ->options(fn () => BankAccount::where('is_active', true)->orderBy('account_name')
                            ->get()->mapWithKeys(fn ($b) => [$b->id => "{$b->account_name} ({$b->bank_name})"])),
                ]),

            Section::make('Notes')->schema([Textarea::make('notes')->rows(2)->nullable()->columnSpanFull()])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('loan_reference')->label('Loan #')->badge()->color('primary')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('borrower_type')->label('Type')->badge()->color('gray'),
                TextColumn::make('borrower_id')->label('Borrower ID'),
                TextColumn::make('principal_amount')->label('Principal')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono'),
                TextColumn::make('outstanding_balance')->label('Outstanding')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono')
                    ->color(fn ($state) => (float)$state > 0 ? 'danger' : 'success'),
                TextColumn::make('disbursement_date')->label('Disbursed')->date()->sortable(),
                TextColumn::make('currency.code')->label('CCY')->badge()->color('gray'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($s) => match($s) { 'active' => 'warning', 'fully_paid' => 'success', 'written_off' => 'danger', default => 'gray' }),
            ])
            ->filters([SelectFilter::make('status')->options(Loan::statuses())])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (Loan $r) => $r->isActive() && !$r->approved_by),
                DeleteAction::make()->visible(fn (Loan $r) => !$r->approved_by),

                TblAction::make('tbl_approve')->label('Approve & Generate Schedule')
                    ->icon('heroicon-o-check-badge')->color('success')->button()
                    ->visible(fn (Loan $r) => $r->isActive() && !$r->approved_by)
                    ->requiresConfirmation()
                    ->modalDescription('This will approve the loan and auto-generate the repayment schedule.')
                    ->action(function (Loan $record) {
                        $record->forceFill([
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ])->save();
                        $record->generateSchedule();
                        Notification::make()->success()->title('Loan approved & schedule generated.')->send();
                    }),

                TblAction::make('tbl_write_off')->label('Write Off')
                    ->icon('heroicon-o-x-circle')->color('danger')->button()
                    ->visible(fn (Loan $r) => $r->isActive() && auth()->user()?->isSuperAdmin())
                    ->requiresConfirmation()
                    ->action(function (Loan $record) {
                        $record->forceFill(['status' => 'written_off'])->save();
                        Notification::make()->warning()->title('Loan written off.')->send();
                    }),
            ])
            ->defaultSort('disbursement_date', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Loan Details')->icon('heroicon-o-credit-card')->columns(4)
                ->schema([
                    TextEntry::make('loan_reference')->label('Loan #')->badge()->color('primary')->fontFamily('mono'),
                    TextEntry::make('borrower_type')->label('Type')->badge(),
                    TextEntry::make('borrower_id')->label('Borrower ID'),
                    TextEntry::make('status')->label('Status')->badge()
                        ->color(fn ($s) => match($s) { 'active' => 'warning', 'fully_paid' => 'success', 'written_off' => 'danger', default => 'gray' }),
                    TextEntry::make('principal_amount')->label('Principal')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('interest_rate')->label('Interest Rate')->suffix('%'),
                    TextEntry::make('tenor_months')->label('Tenor (months)'),
                    TextEntry::make('outstanding_balance')->label('Outstanding')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold'),
                    TextEntry::make('disbursement_date')->label('Disbursed')->date(),
                    TextEntry::make('start_repayment_date')->label('First Payment')->date(),
                    TextEntry::make('currency.code')->label('Currency')->badge()->color('gray'),
                    TextEntry::make('bankAccount.account_name')->label('Bank Account'),
                ]),
            Section::make('Approval')->icon('heroicon-o-clipboard-document-check')->columns(3)
                ->schema([
                    TextEntry::make('preparedBy.name')->label('Prepared By'),
                    TextEntry::make('approvedBy.name')->label('Approved By')->placeholder('Pending'),
                    TextEntry::make('approved_at')->label('Approved At')->dateTime()->placeholder('—'),
                ]),
            Section::make('Notes')->schema([TextEntry::make('notes')->placeholder('—')])->collapsible(),
        ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\Finance\Loans\RelationManagers\LoanScheduleRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLoans::route('/'),
            'create' => CreateLoans::route('/create'),
            'view'   => ViewLoans::route('/{record}'),
            'edit'   => EditLoans::route('/{record}/edit'),
        ];
    }
}
