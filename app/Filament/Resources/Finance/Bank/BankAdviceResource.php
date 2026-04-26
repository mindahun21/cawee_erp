<?php

namespace App\Filament\Resources\Finance\Bank;

use App\Filament\Resources\Finance\Bank\BankAdviceResource\Pages\CreateBankAdvice;
use App\Filament\Resources\Finance\Bank\BankAdviceResource\Pages\EditBankAdvice;
use App\Filament\Resources\Finance\Bank\BankAdviceResource\Pages\ListBankAdvices;
use App\Filament\Resources\Finance\Bank\BankAdviceResource\Pages\ViewBankAdvice;
use App\Models\Currency;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankAdvice;
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
use App\Traits\BelongsToModule;

class BankAdviceResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = BankAdvice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static string|UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Bank Advices';
    protected static ?int $navigationSort = 32;
    protected static ?string $recordTitleAttribute = 'reference_number';
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
            Section::make('Bank Advice')->icon('heroicon-o-document-text')->columns(3)->schema([
                TextInput::make('reference_number')
                    ->label('Reference #')
                    ->disabled()
                    ->dehydrated()
                    ->placeholder('Auto-generated on save'),

                DatePicker::make('advice_date')
                    ->label('Advice Date')
                    ->required()
                    ->native(false)
                    ->default(now()->toDateString()),

                Select::make('advice_type')
                    ->label('Type')
                    ->required()
                    ->native(false)
                    ->options([
                        'credit' => 'Credit',
                        'debit' => 'Debit',
                    ]),

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

                TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->required()
                    ->default(0),

                Select::make('currency_id')
                    ->label('Currency')
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->options(fn () => Currency::orderBy('code')->pluck('code', 'id')->toArray()),

                Textarea::make('description')
                    ->label('Description')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),

                Select::make('status')
                    ->label('Status')
                    ->native(false)
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                    ])
                    ->default('draft'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->label('Ref #')->badge()->color('primary')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('advice_date')->label('Date')->date()->sortable(),
                TextColumn::make('bankAccount.account_name')->label('Bank Account')->limit(25),
                TextColumn::make('advice_type')->label('Type')->badge()->color('gray'),
                TextColumn::make('amount')->label('Amount')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono'),
                TextColumn::make('currency.code')->label('CCY')->badge()->color('gray'),
                TextColumn::make('status')->label('Status')->badge()->color(fn ($s) => $s === 'posted' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->options(['draft' => 'Draft', 'posted' => 'Posted']),
                SelectFilter::make('advice_type')->label('Type')->options(['credit' => 'Credit', 'debit' => 'Debit']),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (BankAdvice $record) => $record->status === 'draft'),
                DeleteAction::make()->visible(fn (BankAdvice $record) => $record->status === 'draft'),
            ])
            ->defaultSort('advice_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBankAdvices::route('/'),
            'create' => CreateBankAdvice::route('/create'),
            'view'   => ViewBankAdvice::route('/{record}'),
            'edit'   => EditBankAdvice::route('/{record}/edit'),
        ];
    }
}
