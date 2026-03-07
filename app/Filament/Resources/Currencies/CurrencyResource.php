<?php

namespace App\Filament\Resources\Currencies;

use App\Filament\Resources\Currencies\Pages\ManageCurrencies;
use App\Filament\Resources\Currencies\Pages\ViewCurrency;
use App\Models\Currency;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|\UnitEnum|null $navigationGroup = 'Donor Fundraising / Settings';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Currency Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(10)
                            ->placeholder('e.g., USD, EUR, GBP')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('code', strtoupper($state)))
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('e.g., US Dollar, Euro'),
                        TextInput::make('symbol')
                            ->required()
                            ->maxLength(10)
                            ->placeholder('e.g., $, €')
                            ->live(onBlur: true),
                        
                        Placeholder::make('preview')
                            ->label('Preview')
                            ->columnSpanFull()
                            ->content(function (Get $get) {
                                $symbol = $get('symbol') ?: '$';
                                $code = strtoupper($get('code')) ?: 'USD';
                                $name = $get('name') ?: 'US Dollar';
                                
                                return new HtmlString("
                                    <div class='flex flex-col items-center justify-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'>
                                        <div class='text-4xl font-bold text-primary-600 dark:text-primary-400 mb-2'>
                                            <span>{$symbol}</span>
                                            <span class='ml-2 font-mono text-2xl bg-primary-100 dark:bg-primary-900 px-2 py-1 rounded'>{$code}</span>
                                        </div>
                                        <div class='text-gray-500 dark:text-gray-400'>{$name}</div>
                                        <div class='mt-2 text-xs text-gray-400'>This is how the currency will appear in the system</div>
                                    </div>
                                ");
                            }),
                    ])
                    ->headerActions([
                        Action::make('fillUSD')
                            ->label('USD Example')
                            ->icon('heroicon-m-plus')
                            ->color('gray')
                            ->action(function ($set) {
                                $set('code', 'USD');
                                $set('name', 'US Dollar');
                                $set('symbol', '$');
                            }),
                        Action::make('fillEUR')
                            ->label('EUR Example')
                            ->icon('heroicon-m-plus')
                            ->color('gray')
                            ->action(function ($set) {
                                $set('code', 'EUR');
                                $set('name', 'Euro');
                                $set('symbol', '€');
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        $currencies = Currency::all();
                        $csv = "ID,Code,Name,Symbol\n";
                        foreach ($currencies as $currency) {
                            $csv .= "{$currency->id},{$currency->code},{$currency->name},{$currency->symbol}\n";
                        }
                        
                        return response()->streamDownload(function () use ($csv) {
                            echo $csv;
                        }, 'currencies.csv');
                    }),
                Action::make('import')
                    ->label('Import CSV')
                    ->icon('heroicon-m-arrow-up-on-square')
                    ->color('gray')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('file')
                            ->label('CSV File')
                            ->required()
                            ->acceptedFileTypes(['text/csv', 'application/csv']),
                    ])
                    ->action(function (array $data) {
                        $file = storage_path('app/public/' . $data['file']);
                        if (!file_exists($file)) return;
                        
                        $handle = fopen($file, 'r');
                        fgetcsv($handle); // skip header
                        
                        while (($row = fgetcsv($handle)) !== FALSE) {
                            Currency::updateOrCreate(
                                ['code' => $row[1]],
                                [
                                    'name' => $row[2],
                                    'symbol' => $row[3],
                                ]
                            );
                        }
                        fclose($handle);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Import Successful')
                            ->success()
                            ->send();
                    }),
            ])
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->badge(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => new \Illuminate\Support\HtmlString('
                        <div class="hover-actions-wrapper flex gap-2 pt-1 items-center">
                            <a href="'.\App\Filament\Resources\Currencies\CurrencyResource::getUrl('view', ['record' => $record]).'" class="hover-action-link text-gray-400 hover:text-gray-500">View</a>
                            <span class="text-gray-200">|</span>
                            <a href="'.\App\Filament\Resources\Currencies\CurrencyResource::getUrl('edit', ['record' => $record]).'" class="hover-action-link text-primary-600 hover:text-primary-700">Edit</a>
                            <span class="text-gray-200">|</span>
                            <button type="button" 
                                x-on:click="$wire.mountTableAction(\'delete\', '.$record->id.')"
                                class="hover-action-link text-danger-600 hover:text-danger-700 font-medium">Delete</button>
                        </div>
                    '), position: 'below'),
                TextColumn::make('symbol')
                    ->alignCenter()
                    ->weight('bold')
                    ->color('primary'),
            ])
            ->filters([
                //
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCurrencies::route('/'),
            'view' => ViewCurrency::route('/{record}'),
        ];
    }
}
