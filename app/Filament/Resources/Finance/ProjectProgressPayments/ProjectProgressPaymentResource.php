<?php

namespace App\Filament\Resources\Finance\ProjectProgressPayments;

use App\Filament\Resources\Finance\ProjectProgressPayments\Pages\CreateProjectProgressPayments;
use App\Filament\Resources\Finance\ProjectProgressPayments\Pages\EditProjectProgressPayments;
use App\Filament\Resources\Finance\ProjectProgressPayments\Pages\ListProjectProgressPayments;
use App\Filament\Resources\Finance\ProjectProgressPayments\Pages\ViewProjectProgressPayments;
use App\Models\Currency;
use App\Models\Donor;
use App\Models\Finance\BankAccount;
use App\Models\Finance\ProjectProgressPayment;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\Action as TblAction;
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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ProjectProgressPaymentResource extends Resource
{
    protected static ?string $model                          = ProjectProgressPayment::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static string|UnitEnum|null $navigationGroup  = 'Finance';
    protected static ?string $navigationLabel               = 'Project Payments / Donor Funds';
    protected static ?int    $navigationSort                = 87;
    protected static ?string $slug                          = 'finance/project-progress-payments';
    protected static bool $shouldSkipAuthorization          = true;

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        if (! $u) {
            return true;
        }

        return $u->isFinanceOfficer() || $u->isFinanceManager() || $u->isSuperAdmin();
    }
    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($record): bool   { return $record->status === 'received' && static::canViewAny(); }
    public static function canDelete($record): bool { return $record->status === 'received' && static::canViewAny(); }
    public static function canView($record): bool   { return static::canViewAny(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Fund Transfer Header')->icon('heroicon-o-banknotes')->columns(3)->schema([
                Select::make('project_id')->label('Project')->native(false)->nullable()->searchable()
                    ->options(fn () => Project::orderBy('project_name')->pluck('project_name', 'id')),
                Select::make('donor_id')->label('Donor')->native(false)->required()->searchable()
                    ->options(fn () => Donor::orderBy('first_name')->get()->mapWithKeys(fn ($d) => [$d->id => $d->full_name])),
                DatePicker::make('payment_date')->label('Received Date')->required()->default(now()),
                
                TextInput::make('invoice_reference')->label('Reference / Invoice #')->nullable(),
                TextInput::make('amount')->label('Amount Received')->numeric()->required()->default(0),
                Select::make('currency_id')->label('Currency')->native(false)->nullable()
                    ->options(fn () => Currency::orderBy('code')->pluck('code', 'id')),
                    
                Select::make('bank_account_id')->label('Receiving Bank Account')->native(false)->nullable()
                    ->options(fn () => BankAccount::orderBy('bank_name')->pluck('bank_name', 'id')),
                TextInput::make('cumulative_received')->label('Cumulative to Date')->numeric()->default(0),
            ]),
            Section::make('Notes')->schema([Textarea::make('description')->rows(2)->nullable()->columnSpanFull()])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_date')->label('Date')->date()->sortable(),
                TextColumn::make('donor.first_name')->label('Donor')->formatStateUsing(fn ($state, $record) => $record->donor?->full_name ?? '—')->searchable(),
                TextColumn::make('project.project_name')->label('Project')->limit(20)->placeholder('—'),
                TextColumn::make('amount')->label('Amount')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->weight('bold'),
                TextColumn::make('currency.code')->label('Currency')->badge()->color('gray'),
                TextColumn::make('invoice_reference')->label('Ref #')->searchable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($state) => match($state) { 'received' => 'success', 'partially_spent' => 'warning', 'fully_utilized' => 'info', default => 'gray' }),
            ])
            ->filters([SelectFilter::make('status')->options(ProjectProgressPayment::statuses())])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn ($record) => $record->status === 'received'),
                DeleteAction::make()->visible(fn ($record) => $record->status === 'received'),
            ])
            ->defaultSort('payment_date', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment Details')->icon('heroicon-o-banknotes')->columns(3)->schema([
                TextEntry::make('payment_date')->label('Received Date')->date(),
                TextEntry::make('donor.first_name')->label('Donor')
                    ->formatStateUsing(fn ($state, $record) => $record->donor?->full_name ?? '—'),
                TextEntry::make('project.project_name')->label('Project')->placeholder('—'),
                TextEntry::make('amount')->label('Amount Received')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold'),
                TextEntry::make('currency.code')->label('Currency')->badge()->color('gray'),
                TextEntry::make('invoice_reference')->label('Reference / Invoice #')->placeholder('—'),
                TextEntry::make('bankAccount.bank_name')->label('Receiving Bank Account')->placeholder('—'),
                TextEntry::make('cumulative_received')->label('Cumulative Received to Date')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextEntry::make('status')->label('Status')->badge()
                    ->color(fn ($state) => match($state) { 'received' => 'success', 'partially_spent' => 'warning', 'fully_utilized' => 'info', default => 'gray' }),
            ]),
            Section::make('Notes')->schema([TextEntry::make('description')->label('')->columnSpanFull()]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProjectProgressPayments::route('/'),
            'create' => CreateProjectProgressPayments::route('/create'),
            'view'   => ViewProjectProgressPayments::route('/{record}'),
            'edit'   => EditProjectProgressPayments::route('/{record}/edit'),
        ];
    }
}
