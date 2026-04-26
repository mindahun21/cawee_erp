<?php

namespace App\Filament\Resources\Finance\Bank;

use App\Filament\Resources\Finance\Bank\ReconciliationResource\Pages;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankReconciliation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Actions\Action as TblAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReconciliationResource extends Resource
{
    protected static ?string $model = BankReconciliation::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Bank Reconciliation';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'finance/bank/reconciliations';
    protected static bool $shouldSkipAuthorization = true;

    // Hidden from sidebar — accessed via Finance → Reconcile wizard
    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool { return true; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Reconciliation Details')->icon('heroicon-o-document-text')->columns(3)->schema([
                TextInput::make('reference')->label('Ref #')->disabled()->placeholder('Auto-generated')->dehydrated(),
                Select::make('bank_account_id')->label('Bank Account')->required()->searchable()->native(false)
                    ->options(fn() => BankAccount::where('is_active', true)->pluck('bank_name', 'id')),
                Select::make('accounting_period_id')->label('Accounting Period')->required()->native(false)->searchable()
                    ->options(fn() => AccountingPeriod::orderBy('start_date', 'desc')->pluck('name', 'id')),
                DatePicker::make('statement_date')->label('Statement Date')->required(),
                TextInput::make('statement_balance')->label('Bank Statement Balance')->numeric()->required()->default(0),
                TextInput::make('gl_balance')->label('GL Balance')->numeric()->required()->default(0),
            ]),

            Section::make('Outstanding Items')->icon('heroicon-o-list-bullet')->schema([
                Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        Select::make('item_type')->label('Type')->native(false)->required()
                            ->options([
                                'deposit' => 'Deposit in Transit',
                                'payment' => 'Outstanding Cheque/Payment',
                                'bank_charge' => 'Bank Charge',
                                'interest' => 'Interest',
                                'other' => 'Other'
                            ]),
                        DatePicker::make('transaction_date')->label('Date')->required(),
                        TextInput::make('description')->label('Description')->required(),
                        TextInput::make('amount')->label('Amount')->numeric()->required()->default(0),
                        TextInput::make('bank_reference')->label('Bank Ref/Cheque #')->nullable(),
                        Toggle::make('is_cleared')->label('Cleared?')->onColor('success')->offColor('gray'),
                    ])->columns(6)->addActionLabel('Add Item')
                     ->mutateRelationshipDataBeforeCreateUsing(function (array $data) {
                         // On save, we can calc cleared_date if cleared
                         if ($data['is_cleared'] ?? false) {
                             $data['cleared_date'] = now()->toDateString();
                         }
                         return $data;
                     })
                     ->mutateRelationshipDataBeforeSaveUsing(function (array $data) {
                         if ($data['is_cleared'] ?? false) {
                             $data['cleared_date'] = now()->toDateString();
                         } else {
                             $data['cleared_date'] = null;
                         }
                         return $data;
                     })
            ]),

            Section::make('Notes')->schema([
                Textarea::make('notes')->rows(3)->nullable()
            ])->collapsible()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Ref #')->badge()->color('primary')->searchable()->sortable(),
                TextColumn::make('bankAccount.bank_name')->label('Account')->limit(20)->searchable(),
                TextColumn::make('period.name')->label('Period'),
                TextColumn::make('statement_date')->label('Date')->date()->sortable(),
                TextColumn::make('statement_balance')->label('Bank Balance')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextColumn::make('gl_balance')->label('GL Balance')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextColumn::make('difference')->label('Difference')->numeric(decimalPlaces: 2)->fontFamily('mono')
                    ->badge()->color(fn ($state) => (float)$state == 0 ? 'success' : 'danger'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($state) => match($state) { 'in_progress' => 'gray', 'reconciled' => 'success', 'locked' => 'locked', default => 'gray' }),
            ])
            ->filters([SelectFilter::make('status')->options(BankReconciliation::statuses())])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn ($record) => $record->status === 'in_progress'),
                TblAction::make('mark_reconciled')->label('Mark Reconciled')->icon('heroicon-o-check-badge')->color('success')->button()
                    ->visible(fn ($record) => $record->status === 'in_progress' && abs((float)$record->difference) < 0.01)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->forceFill([
                            'status' => 'reconciled',
                            'reviewed_by' => auth()->id(),
                            'reconciled_at' => now(),
                        ])->save();
                        Notification::make()->success()->title('Bank reconciliation completed.')->send();
                    })
            ])
            ->defaultSort('statement_date', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Summary')->icon('heroicon-o-chart-pie')->columns(4)->schema([
                TextEntry::make('statement_balance')->label('Statement Balance')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextEntry::make('outstanding_deposits')->label('+ Deposits In Transit')->numeric(decimalPlaces: 2)->fontFamily('mono')->color('success'),
                TextEntry::make('outstanding_cheques')->label('- Outstanding Cheques')->numeric(decimalPlaces: 2)->fontFamily('mono')->color('danger'),
                TextEntry::make('adjusted_bank_balance')->label('= Adjusted Bank Balance')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold'),
                
                TextEntry::make('gl_balance')->label('GL Balance')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold'),
                TextEntry::make('difference')->label('Difference')->numeric(decimalPlaces: 2)->fontFamily('mono')
                    ->badge()->color(fn ($state) => (float)$state == 0 ? 'success' : 'danger'),
                TextEntry::make('status')->badge()->color(fn ($state) => match($state) { 'in_progress' => 'gray', 'reconciled' => 'success', 'locked' => 'locked', default => 'gray' }),
            ]),
            
            Section::make('Reconciliation Items')->schema([
                RepeatableEntry::make('items')->schema([
                    TextEntry::make('transaction_date')->label('Date')->date(),
                    TextEntry::make('item_type')->label('Type')->badge()->color('gray'),
                    TextEntry::make('description')->label('Description'),
                    TextEntry::make('amount')->label('Amount')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('is_cleared')->label('Cleared?')->badge()
                        ->color(fn ($state) => $state ? 'success' : 'warning')
                        ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                    TextEntry::make('bank_reference')->label('Ref/Cheque')->placeholder('—'),
                ])->columns(6)
            ])
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReconciliations::route('/'),
            'create' => Pages\CreateReconciliation::route('/create'),
            'view' => Pages\ViewReconciliation::route('/{record}'),
            'edit' => Pages\EditReconciliation::route('/{record}/edit'),
        ];
    }
}
