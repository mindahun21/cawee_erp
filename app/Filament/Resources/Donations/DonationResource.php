<?php

namespace App\Filament\Resources\Donations;

use App\Filament\Resources\Donations\Pages\ManageDonations;
use App\Filament\Resources\Donations\Pages\ViewDonation;
use App\Models\Donation;

use App\Models\Donor;
use BackedEnum;
use UnitEnum;

use App\Services\ReportService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class DonationResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = Donation::class;

    protected static string|UnitEnum|null $navigationGroup = 'Donor Fundraising';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()->tabs([
                    Tab::make('Donation Details')
                        ->icon('heroicon-o-gift')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                                Select::make('donor_id')
                                    ->relationship('donor', 'id')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        \Filament\Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Select::make('donor_type')
                                                    ->options([
                                                        'individual' => 'Individual',
                                                        'corporate' => 'Corporate',
                                                        'foundation' => 'Foundation',
                                                    ])
                                                    ->required()
                                                    ->default('individual')
                                                    ->reactive(),
                                                TextInput::make('email')
                                                    ->email()
                                                    ->required()
                                                    ->unique('donors', 'email'),
                                                TextInput::make('first_name')
                                                    ->label('First Name')
                                                    ->required(fn ($get) => $get('donor_type') === 'individual')
                                                    ->hidden(fn ($get) => in_array($get('donor_type'), ['corporate', 'foundation'])),
                                                TextInput::make('last_name')
                                                    ->label('Last Name')
                                                    ->required(fn ($get) => $get('donor_type') === 'individual')
                                                    ->hidden(fn ($get) => in_array($get('donor_type'), ['corporate', 'foundation'])),
                                                TextInput::make('organization_name')
                                                    ->label('Organization Name')
                                                    ->required(fn ($get) => in_array($get('donor_type'), ['corporate', 'foundation']))
                                                    ->hidden(fn ($get) => $get('donor_type') === 'individual'),
                                                TextInput::make('phone')
                                                    ->tel(),
                                            ]),
                                    ]),
                                Select::make('campaign_id')
                                    ->relationship('campaign', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        \Filament\Schemas\Components\Grid::make(2)
                                            ->schema([
                                                TextInput::make('title')
                                                    ->required()
                                                    ->maxLength(255),
                                                TextInput::make('goal_amount')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(0),
                                                Select::make('currency_id')
                                                    ->relationship('currency', 'code')
                                                    ->required()
                                                    ->default(fn () => \App\Models\Currency::where('is_procurement_default', true)->value('id') ?? 1),
                                                DatePicker::make('start_date')
                                                    ->required()
                                                    ->default(now()),
                                                DatePicker::make('end_date')
                                                    ->required()
                                                    ->after('start_date')
                                                    ->default(now()->addMonth()),
                                            ]),
                                    ]),
                                Select::make('donation_type_id')
                                    ->relationship('donationType', 'name', fn ($query) => $query->active())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->createOptionForm([
                                        \Filament\Schemas\Components\Grid::make(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(100),
                                                TextInput::make('code')
                                                    ->required()
                                                    ->maxLength(20)
                                                    ->unique('donation_types', 'code'),
                                                \Filament\Forms\Components\Toggle::make('is_active')
                                                    ->label('Active')
                                                    ->default(true),
                                                \Filament\Forms\Components\Toggle::make('is_recurring')
                                                    ->label('Supports Recurring'),
                                            ]),
                                    ]),
                                DatePicker::make('donation_date')
                                    ->required()
                                    ->default(now()),
                                Select::make('payment_method')
                                    ->options([
                                        'cash' => 'Cash',
                                        'bank_transfer' => 'Bank Transfer',
                                        'check' => 'Check',
                                        'online' => 'Online Payment',
                                        'other' => 'Other',
                                    ])
                                    ->required()
                                    ->native(false),
                                TextInput::make('transaction_id')
                                    ->label('Transaction ID')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. Bank Ref, Receipt #'),
                                Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'completed' => 'Completed',
                                        'failed' => 'Failed',
                                        'refunded' => 'Refunded',
                                    ])
                                    ->default('completed')
                                    ->required(),
                            ]),
                        ]),

                    Tab::make('Payment Information')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                                Select::make('currency_id')
                                    ->relationship('currency', 'name')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                        $currency = \App\Models\Currency::find($state);
                                        if ($currency) {
                                            $set('exchange_rate', $currency->exchange_rate ?? 1);
                                            $amount = (float) $get('amount');
                                            if ($amount > 0) {
                                                $set('base_amount', round($amount * ($currency->exchange_rate ?? 1), 2));
                                            }
                                        }
                                    })
                                    ->default(fn () => \App\Models\Currency::where('code', 'ETB')->first()?->id ?? 1),
                                TextInput::make('amount')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get, $state) {
                                        $rate = (float) $get('exchange_rate') ?: 1;
                                        $amount = (float) $state;
                                        if ($amount > 0) {
                                            $set('base_amount', round($amount * $rate, 2));
                                        }
                                    })
                                    ->prefix(fn (\Filament\Schemas\Components\Utilities\Get $get) => \App\Models\Currency::find($get('currency_id'))?->code ?? 'ETB')
                                    ->minValue(0.01),
                                TextInput::make('exchange_rate')
                                    ->numeric()
                                    ->readOnly()
                                    ->label('Exchange Rate')
                                    ->default(1.000000)
                                    ->helperText('Current rate for selected currency'),
                                TextInput::make('base_amount')
                                    ->numeric()
                                    ->readOnly()
                                    ->label('Base Amount (ETB)')
                                    ->prefix('ETB')
                                    ->helperText('Amount converted to base currency'),
                                TextInput::make('payment_method')
                                    ->required()
                                    ->maxLength(50)
                                    ->placeholder('e.g., Bank Transfer, Telebirr, Cash'),
                                TextInput::make('transaction_id')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Transaction reference number'),
                                TextInput::make('receipt_number')
                                    ->maxLength(50)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('Auto-generated on save'),
                            ]),
                        ]),

                    Tab::make('Recurring & Pledge')
                        ->icon('heroicon-o-arrow-path')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                                Toggle::make('is_recurring')
                                    ->label('Recurring Donation')
                                    ->helperText('Will be processed automatically each month'),
                                TextInput::make('pledge_amount')
                                    ->numeric()
                                    ->prefix('ETB')
                                    ->helperText('Total pledge amount if applicable'),
                            ]),
                        ]),

                    Tab::make('Additional Information')
                        ->icon('heroicon-o-plus-circle')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                                Toggle::make('is_tax_deductible')
                                    ->label('Tax Deductible')
                                    ->helperText('Eligible for tax deduction receipt'),
                                Toggle::make('is_gift_aid_eligible')
                                    ->label('Gift Aid Eligible')
                                    ->helperText('Eligible for Gift Aid claim'),
                                Textarea::make('in_kind_description')
                                    ->label('In-Kind Description')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->helperText('For non-monetary donations'),
                                Textarea::make('notes')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->helperText('Internal notes'),
                            ]),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable()
                    ->weight('semibold'),
                TextColumn::make('donor.full_name')
                    ->label('Donor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('campaign.title')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('donationType.name')
                    ->label('Type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('amount')
                    ->money(fn ($record) => $record->currency?->code ?? 'ETB')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('donation_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                    }),
                IconColumn::make('is_recurring')
                    ->label('Recurring')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('campaign_id')
                    ->relationship('campaign', 'title')
                    ->label('Campaign')
                    ->multiple()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('donation_type_id')
                    ->relationship('donationType', 'name')
                    ->label('Donation Type')
                    ->multiple()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->multiple(),
                \Filament\Tables\Filters\Filter::make('is_recurring')
                    ->query(fn ($query) => $query->where('is_recurring', true))
                    ->label('Recurring Only'),
                \Filament\Tables\Filters\Filter::make('donation_date')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('From'),
                        DatePicker::make('date_to')
                            ->label('To'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['date_from'], fn ($q) => $q->where('donation_date', '>=', $data['date_from']))
                            ->when($data['date_to'], fn ($q) => $q->where('donation_date', '<=', $data['date_to']));
                    }),
                \Filament\Tables\Filters\SelectFilter::make('currency_id')
                    ->relationship('currency', 'code')
                    ->label('Currency'),
                \Filament\Tables\Filters\SelectFilter::make('donor_id')
                    ->label('Donor')
                    ->searchable()
                    ->options(fn () => \App\Models\Donor::all()->pluck('full_name', 'id')->toArray()),
                \Filament\Tables\Filters\Filter::make('amount')
                    ->form([
                        TextInput::make('min_amount')->numeric()->label('Min Amount'),
                        TextInput::make('max_amount')->numeric()->label('Max Amount'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['min_amount'], fn ($q) => $q->where('amount', '>=', $data['min_amount']))
                            ->when($data['max_amount'], fn ($q) => $q->where('amount', '<=', $data['max_amount']));
                    }),
            ])
            ->recordActions([
                Action::make('downloadReceipt')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function (Donation $record) {
                        $service = app(ReportService::class);
                        $pdfContent = $service->generateReceipt($record);
                        
                        return response()->streamDownload(
                            fn () => print($pdfContent),
                            "receipt-{$record->id}.pdf"
                        );
                    }),
                Action::make('generateReceipt')
                    ->label('Send Receipt')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Donation $record) {
                        $service = app(\App\Services\DonationService::class);
                        try {
                            $receiptData = $service->generateReceipt($record->id);
                            
                            \Illuminate\Support\Facades\Mail::to($record->donor->email)
                                ->queue(new \App\Mail\DonationReceipt($record, $receiptData));
                                
                            \Filament\Notifications\Notification::make()
                                ->title('Receipt Sent')
                                ->body('The donation receipt has been queued for sending.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error Sending Receipt')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->headerActions([
                Action::make('exportExcel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn () => \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\DonationsExport(), 'donations.xlsx')),
                Action::make('exportPDF')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->action(function () {
                        $service = app(ReportService::class);
                        $donations = Donation::with(['donor', 'campaign', 'currency'])->where('status', 'completed')->get();
                        $pdfContent = $service->generateFullReport($donations);
                        
                        return response()->streamDownload(
                            fn () => print($pdfContent),
                            "donations-report.pdf"
                        );
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('exportSelected')
                        ->label('Export Excel')
                        ->icon('heroicon-o-document-plus')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\DonationsExport(['ids' => $records->pluck('id')->toArray()]), 
                            'selected-donations.xlsx'
                        )),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\DonationStatsOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDonations::route('/'),
            'view' => ViewDonation::route('/{record}'),
        ];
    }
}
