<?php

namespace App\Filament\Resources\Finance\Receivables;

use App\Filament\Resources\Finance\Receivables\Pages\CreateIncomeRegisters;
use App\Filament\Resources\Finance\Receivables\Pages\EditIncomeRegisters;
use App\Filament\Resources\Finance\Receivables\Pages\ListIncomeRegisters;
use App\Filament\Resources\Finance\Receivables\Pages\ViewIncomeRegisters;
use App\Models\Finance\BankAccount;
use App\Models\Finance\CostCenter;
use App\Models\Finance\IncomeRegister;
use App\Models\Donor;
use App\Models\Project;
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
use Filament\Schemas\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class IncomeRegisterResource extends Resource
{
    protected static ?string $model                          = IncomeRegister::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static string|UnitEnum|null $navigationGroup  = 'Finance';
    protected static ?string $navigationLabel               = 'Income Registers';
    protected static ?int    $navigationSort                = 62;
    protected static ?string $slug                          = 'finance/receivables/income-registers';
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
            Section::make('Income Details')
                ->icon('heroicon-o-arrow-down-tray')
                ->columns(3)
                ->schema([
                    TextInput::make('reference')->label('Reference')->disabled()->dehydrated()
                        ->placeholder('Auto-generated on save'),
                    DatePicker::make('income_date')->label('Income Date')->required()->default(today()),
                    Select::make('income_type')->label('Income Type')->required()->native(false)
                        ->options(IncomeRegister::incomeTypes()),
                    TextInput::make('source_name')->label('Source Name')->required()->maxLength(120)->columnSpan(2),
                    Select::make('donor_id')->label('Donor')->native(false)->nullable()->searchable()
                        ->options(fn () => Donor::orderBy('first_name')->get()->mapWithKeys(fn ($d) => [$d->id => $d->full_name])),
                ]),

            Section::make('Amount & Currency')
                ->icon('heroicon-o-banknotes')
                ->columns(4)
                ->schema([
                    TextInput::make('amount')->label('Amount')->numeric()->required(),
                    Select::make('currency_id')->label('Currency')->required()->native(false)
                        ->options(fn () => \App\Models\Currency::orderBy('code')->pluck('code', 'id')),
                    TextInput::make('exchange_rate_to_base')->label('Exchange Rate to ETB')->numeric()->default(1),
                    TextInput::make('amount_in_base')->label('Amount in ETB')->numeric()->nullable()
                        ->helperText('Auto-computed: Amount × Rate'),
                    Select::make('bank_account_id')->label('Deposited To Bank Account')
                        ->options(fn () => BankAccount::where('is_active', true)->orderBy('account_name')
                            ->get()->mapWithKeys(fn ($b) => [$b->id => "{$b->account_name} ({$b->bank_name})"]))
                        ->native(false)->nullable()->columnSpan(2),
                    TextInput::make('receipt_reference')->label('Receipt/Ref No.')->nullable(),
                ]),

            Section::make('Dimension Coding')
                ->icon('heroicon-o-tag')
                ->columns(3)
                ->schema([
                    Select::make('cost_center_id')->label('Cost Center')->required()->native(false)
                        ->options(fn () => CostCenter::where('is_active', true)->pluck('name', 'id')),
                    Select::make('project_id')->label('Project')->native(false)->nullable()
                        ->options(fn () => Project::orderBy('project_name')->pluck('project_name', 'id')),
                    Textarea::make('description')->label('Description')->rows(2)->nullable()->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Ref #')->badge()->color('primary')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('income_date')->label('Date')->date()->sortable(),
                TextColumn::make('source_name')->label('Source')->limit(28)->searchable(),
                TextColumn::make('income_type')->label('Type')->badge()
                    ->color(fn ($s) => match($s) {
                        'grant' => 'success', 'service_fee' => 'info',
                        'interest' => 'warning', 'other' => 'gray', default => 'gray',
                    }),
                TextColumn::make('amount')->label('Amount')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono'),
                TextColumn::make('currency.code')->label('CCY')->badge()->color('gray'),
                TextColumn::make('amount_in_base')->label('ETB Equiv.')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->color(fn ($s) => match($s) { 'draft' => 'gray', 'confirmed' => 'warning', 'posted' => 'success', default => 'gray' }),
            ])
            ->filters([SelectFilter::make('status')->options(IncomeRegister::statuses())])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (IncomeRegister $r) => $r->isDraft()),
                DeleteAction::make()->visible(fn (IncomeRegister $r) => $r->isDraft()),

                TblAction::make('tbl_confirm')
                    ->label('Confirm')->icon('heroicon-o-check')->color('success')->button()
                    ->visible(fn (IncomeRegister $r) => $r->isDraft())
                    ->requiresConfirmation()
                    ->action(function (IncomeRegister $record) {
                        $record->forceFill([
                            'status'       => 'confirmed',
                            'confirmed_by' => auth()->id(),
                            'confirmed_at' => now(),
                        ])->save();
                        Notification::make()->success()->title('Income confirmed.')->send();
                    }),
            ])
            ->defaultSort('income_date', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            InfoSection::make('Income Details')->icon('heroicon-o-arrow-down-tray')->columns(4)
                ->schema([
                    TextEntry::make('reference')->label('Reference')->badge()->color('primary')->fontFamily('mono'),
                    TextEntry::make('income_date')->label('Date')->date(),
                    TextEntry::make('income_type')->label('Type')->badge(),
                    TextEntry::make('status')->label('Status')->badge()
                        ->color(fn ($s) => match($s) { 'draft' => 'gray', 'confirmed' => 'warning', 'posted' => 'success', default => 'gray' }),
                    TextEntry::make('source_name')->label('Source')->columnSpan(2),
                    TextEntry::make('donor.full_name')->label('Donor')->placeholder('—'),
                    TextEntry::make('bankAccount.account_name')->label('Deposited To')->placeholder('—'),
                    TextEntry::make('amount')->label('Amount')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('currency.code')->label('Currency')->badge()->color('gray'),
                    TextEntry::make('exchange_rate_to_base')->label('Rate'),
                    TextEntry::make('amount_in_base')->label('ETB Equivalent')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold'),
                ]),
            InfoSection::make('Dimension')->icon('heroicon-o-tag')->columns(3)
                ->schema([
                    TextEntry::make('costCenter.name')->label('Cost Center'),
                    TextEntry::make('project.project_name')->label('Project')->placeholder('—'),
                    TextEntry::make('description')->placeholder('—'),
                ]),
            InfoSection::make('Posting')
                ->schema([
                    TextEntry::make('preparedBy.name')->label('Prepared By'),
                    TextEntry::make('confirmedBy.name')->label('Confirmed By')->placeholder('—'),
                    TextEntry::make('confirmed_at')->label('Confirmed At')->dateTime()->placeholder('—'),
                    TextEntry::make('journalEntry.reference_number')->label('Journal Entry')->badge()->placeholder('Not posted'),
                ])->columns(4),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListIncomeRegisters::route('/'),
            'create' => CreateIncomeRegisters::route('/create'),
            'view'   => ViewIncomeRegisters::route('/{record}'),
            'edit'   => EditIncomeRegisters::route('/{record}/edit'),
        ];
    }
}
