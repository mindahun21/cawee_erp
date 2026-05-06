<?php

namespace App\Filament\Resources\Finance\ChartOfAccounts;

use App\Filament\Resources\Finance\ChartOfAccounts\Pages\CreateChartOfAccount;
use App\Filament\Resources\Finance\ChartOfAccounts\Pages\EditChartOfAccount;
use App\Filament\Resources\Finance\ChartOfAccounts\Pages\ListChartOfAccounts;
use App\Filament\Resources\Finance\ChartOfAccounts\Pages\ViewChartOfAccount;
use App\Models\Currency;
use App\Models\Finance\AccountType;
use App\Models\Finance\AccountSubClassification;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\FinancialStatementCategory;
use App\Models\Finance\GeneralLedger;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use App\Traits\BelongsToModule;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column as ExcelColumn;
use Maatwebsite\Excel\Excel as ExcelType;

class ChartOfAccountResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = ChartOfAccount::class;

    // ── Navigation ────────────────────────────────────────────────────

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Chart of Accounts';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $shouldSkipAuthorization = true;

    // ── Authorization ─────────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isFinanceOfficer() || $user->isSuperAdmin());
    }

    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($r): bool   { return static::canViewAny(); }
    public static function canDelete($r): bool { return static::canViewAny(); }

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── Core Identity ─────────────────────────────────────────
            Section::make('Account Identity')
                ->description('The account code and name as they will appear on financial statements.')
                ->icon('heroicon-o-identification')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Account Code')
                        ->required()
                        ->maxLength(20)
                        ->placeholder('e.g. 1101, 5310')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('code', strtoupper(trim($state ?? ''))))
                        ->unique(ChartOfAccount::class, 'code', ignoreRecord: true)
                        ->helperText('Must be unique. Use a consistent numbering convention (e.g. 1xxx = Assets, 5xxx = Expenses).')
                        ->extraInputAttributes(['class' => 'font-mono']),

                    TextInput::make('name')
                        ->label('Account Name')
                        ->required()
                        ->maxLength(200)
                        ->placeholder('e.g. Petty Cash — Head Office'),

                    Select::make('account_type_id')
                        ->label('Account Type')
                        ->options(fn () => AccountType::where('is_active', true)
                            ->orderBy('classification')
                            ->get()
                            ->mapWithKeys(fn ($t) => [$t->id => "[{$t->code}] {$t->name}"])
                            ->toArray()
                        )
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->helperText('Asset / Liability / Equity / Income / Expense — drives normal balance direction.')
                        ->preload()
                        ->live(),

                    Select::make('sub_classification_id')
                        ->label('Sub-Classification')
                        ->options(fn (Get $get): array =>
                            ($typeId = $get('account_type_id')) && ($type = AccountType::find($typeId))
                                ? AccountSubClassification::optionsForClassification($type->classification)
                                : AccountSubClassification::groupedOptions()
                        )
                        ->nullable()
                        ->native(false)
                        ->searchable()
                        ->placeholder('Select a sub-classification...')
                        ->helperText('e.g. Cash & Cash Equivalents, Bank, Accounts Receivable, Fixed Asset.')
                        ->preload(),

                    Select::make('financial_statement_category_id')
                        ->label('Financial Statement Category')
                        ->options(FinancialStatementCategory::groupedOptions())
                        ->native(false)
                        ->searchable()
                        ->nullable()
                        ->helperText('Maps this account to a section of the Balance Sheet, Income Statement, or Cash Flow.'),
                ]),

            // ── Hierarchy ─────────────────────────────────────────────
            Section::make('Account Hierarchy')
                ->description('Organise this account within the chart tree. Header accounts cannot receive direct journal postings.')
                ->icon('heroicon-o-folder-open')
                ->columns(2)
                ->schema([
                    Select::make('parent_id')
                        ->label('Parent Account')
                        ->options(fn () => ChartOfAccount::hierarchyOptions())
                        ->nullable()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->helperText('Leave blank to make this a top-level account.')
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            // Auto-set level when parent is chosen
                            if ($state) {
                                $parent = ChartOfAccount::find($state);
                                $set('level', ($parent->level ?? 0) + 1);
                            } else {
                                $set('level', 0);
                            }
                        }),

                    TextInput::make('level')
                        ->label('Depth Level')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(5)
                        ->disabled()
                        ->dehydrated()
                        ->helperText('0 = root section, auto-computed when parent is selected.'),

                    Toggle::make('is_header')
                        ->label('Header Account (structural grouping only)')
                        ->default(false)
                        ->helperText('Header accounts appear as section titles. They cannot receive direct journal postings.')
                        ->columnSpanFull()
                        ->live(),
                ]),

            // ── Control & Currency ────────────────────────────────────
            Section::make('Control Account & Currency')
                ->description('Sub-ledger control flags and multi-currency override.')
                ->icon('heroicon-o-adjustments-horizontal')
                ->columns(2)
                ->schema([
                    Select::make('is_control_account')
                        ->label('Control Account Type')
                        ->options(ChartOfAccount::controlAccountOptions())
                        ->default('none')
                        ->native(false)
                        ->required()
                        ->helperText('AP/AR/Bank control accounts link to their respective sub-ledgers.'),

                    Select::make('currency_id')
                        ->label('Currency Override')
                        ->options(fn () => Currency::orderBy('code')->pluck('name', 'id'))
                        ->searchable()
                        ->nullable()
                        ->native(false)
                        ->helperText('Leave blank to use the functional currency (ETB). Set only for foreign-currency bank accounts.'),

                    Toggle::make('is_donor_fund_account')
                        ->label('Donor-Restricted Fund Account')
                        ->default(false)
                        ->helperText('Tag this account for automatic inclusion in donor utilisation and restricted-fund reports.')
                        ->columnSpanFull(),
                ]),

            // ── Status & Notes ────────────────────────────────────────
            Section::make('Status & Notes')
                ->icon('heroicon-o-pencil-square')
                ->columns(1)
                ->schema([
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive accounts are hidden from journal entry dropdowns and block new postings.'),

                    Textarea::make('notes')
                        ->rows(3)
                        ->nullable()
                        ->placeholder('Optional guidance for finance staff on when/how to use this account...'),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                // ── Code ─────────────────────────────────────────────
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray')
                    ->width('100px'),

                // ── Account Name (indented by level) ──────────────────
                TextColumn::make('name')
                    ->label('Account Name')
                    ->searchable()
                    ->sortable()
                    ->html()
                    ->formatStateUsing(function (string $state, ChartOfAccount $record): HtmlString {
                        $indent = str_repeat(
                            '<span class="inline-block w-4"></span>',
                            max(0, (int) $record->level)
                        );

                        $prefix = $record->is_header
                            ? '<span class="text-gray-400 mr-1">▸</span>'
                            : '<span class="inline-block w-4"></span>';

                        $weight = $record->is_header
                            ? 'font-semibold text-gray-700 dark:text-gray-200'
                            : 'text-gray-900 dark:text-gray-100';

                        $donorBadge = $record->is_donor_fund_account
                            ? ' <span class="ml-1 text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded px-1 py-0.5">DONOR</span>'
                            : '';

                        return new HtmlString(
                            "{$indent}{$prefix}<span class=\"{$weight}\">{$state}</span>{$donorBadge}"
                        );
                    }),

                // ── Account Type ──────────────────────────────────────
                TextColumn::make('accountType.name')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state, $record) => match ($record->accountType?->classification ?? '') {
                        'asset'     => 'success',
                        'liability' => 'danger',
                        'equity'    => 'warning',
                        'income'    => 'info',
                        'expense'   => 'gray',
                        default     => 'gray',
                    }),

                // ── FSC ───────────────────────────────────────────────
                TextColumn::make('financialStatementCategory.code')
                    ->label('FSC')
                    ->badge()
                    ->color('primary')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── Control Account ───────────────────────────────────
                TextColumn::make('is_control_account')
                    ->label('Control')
                    ->badge()
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? 'none'))
                    ->color(fn ($state) => match ($state) {
                        'ap'   => 'warning',
                        'ar'   => 'info',
                        'bank' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── Current GL Balance ────────────────────────────────
                TextColumn::make('gl_balance')
                    ->label('Balance')
                    ->getStateUsing(function (ChartOfAccount $record): ?string {
                        if ($record->is_header) {
                            return null;
                        }
                        $balance = $record->currentBalance();
                        return number_format($balance, 2);
                    })
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->color(fn ($state) => $state === null
                        ? 'gray'
                        : ((float) str_replace(',', '', $state ?? '0') >= 0 ? 'success' : 'danger'))
                    ->placeholder('—')
                    ->toggleable(),

                // ── Flags ─────────────────────────────────────────────
                IconColumn::make('is_header')
                    ->label('Header')
                    ->boolean()
                    ->trueIcon('heroicon-o-folder')
                    ->falseIcon('heroicon-o-document')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])

            ->filters([
                SelectFilter::make('account_type_id')
                    ->label('Account Type')
                    ->options(fn () => AccountType::where('is_active', true)
                        ->pluck('name', 'id')
                        ->toArray()
                    ),

                SelectFilter::make('financial_statement_category_id')
                    ->label('FSC')
                    ->options(FinancialStatementCategory::activeOptions()),

                SelectFilter::make('is_control_account')
                    ->label('Control Account')
                    ->options(ChartOfAccount::controlAccountOptions()),

                TernaryFilter::make('is_header')
                    ->label('Headers Only')
                    ->trueLabel('Headers only')
                    ->falseLabel('Leaf accounts only')
                    ->placeholder('All accounts'),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->placeholder('All')
                    ->default(true),

                TernaryFilter::make('is_donor_fund_account')
                    ->label('Donor Fund Accounts')
                    ->trueLabel('Donor-restricted only')
                    ->falseLabel('Non-donor only')
                    ->placeholder('All accounts'),
            ])

            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                // Quick-toggle active status
                Action::make('toggle_active')
                    ->label(fn (ChartOfAccount $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (ChartOfAccount $record) => $record->is_active
                        ? 'heroicon-o-x-circle'
                        : 'heroicon-o-check-circle'
                    )
                    ->color(fn (ChartOfAccount $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (ChartOfAccount $record) => $record->is_active
                        ? "Deactivate [{$record->code}] {$record->name}?"
                        : "Activate [{$record->code}] {$record->name}?"
                    )
                    ->modalDescription(fn (ChartOfAccount $record) => $record->is_active
                        ? 'Inactive accounts cannot receive new journal postings. Existing GL history is preserved.'
                        : 'This account will become available for use in journal entries.'
                    )
                    ->action(function (ChartOfAccount $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()
                            ->title($record->is_active
                                ? "Account [{$record->code}] activated."
                                : "Account [{$record->code}] deactivated."
                            )
                            ->color($record->is_active ? 'success' : 'warning')
                            ->send();
                    }),

                DeleteAction::make()
                    ->visible(fn (ChartOfAccount $record) =>
                        ! GeneralLedger::where('account_id', $record->id)->exists()
                    )
                    ->modalDescription('This account will be soft-deleted. It can only be deleted if it has no General Ledger history.'),
            ])

            ->defaultSort('code', 'asc')
            ->striped()
            ->paginated([50, 100, 'all'])
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make('excel')->withFilename('chart-of-accounts-' . now()->format('Y-m-d'))
                        ->withWriterType(ExcelType::XLSX)
                        ->withColumns([
                            ExcelColumn::make('code')->heading('Code'),
                            ExcelColumn::make('name')->heading('Account Name'),
                            ExcelColumn::make('accountType.name')->heading('Account Type'),
                            ExcelColumn::make('accountType.classification')->heading('Classification'),
                            ExcelColumn::make('accountType.normal_balance')->heading('Normal Balance'),
                            ExcelColumn::make('parent.name')->heading('Parent Account'),
                            ExcelColumn::make('is_header')->heading('Header')->formatStateUsing(fn ($s) => $s ? 'Yes' : 'No'),
                            ExcelColumn::make('is_active')->heading('Active')->formatStateUsing(fn ($s) => $s ? 'Active' : 'Inactive'),
                            ExcelColumn::make('notes')->heading('Notes'),
                        ]),
                    ExcelExport::make('csv')->withFilename('chart-of-accounts-' . now()->format('Y-m-d'))
                        ->withWriterType(ExcelType::CSV)
                        ->withColumns([
                            ExcelColumn::make('code')->heading('Code'),
                            ExcelColumn::make('name')->heading('Account Name'),
                            ExcelColumn::make('accountType.name')->heading('Account Type'),
                            ExcelColumn::make('accountType.classification')->heading('Classification'),
                            ExcelColumn::make('parent.name')->heading('Parent Account'),
                            ExcelColumn::make('is_header')->heading('Header')->formatStateUsing(fn ($s) => $s ? 'Yes' : 'No'),
                            ExcelColumn::make('is_active')->heading('Active')->formatStateUsing(fn ($s) => $s ? 'Active' : 'Inactive'),
                            ExcelColumn::make('notes')->heading('Notes'),
                        ]),
                ])->label('Export All'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()->exports([
                        ExcelExport::make('excel')->withFilename('chart-of-accounts-selected')
                            ->withWriterType(ExcelType::XLSX)
                            ->withColumns([
                                ExcelColumn::make('code')->heading('Code'),
                                ExcelColumn::make('name')->heading('Account Name'),
                                ExcelColumn::make('accountType.name')->heading('Account Type'),
                                ExcelColumn::make('accountType.classification')->heading('Classification'),
                                ExcelColumn::make('parent.name')->heading('Parent Account'),
                                ExcelColumn::make('is_header')->heading('Header')->formatStateUsing(fn ($s) => $s ? 'Yes' : 'No'),
                                ExcelColumn::make('is_active')->heading('Active')->formatStateUsing(fn ($s) => $s ? 'Active' : 'Inactive'),
                                ExcelColumn::make('notes')->heading('Notes'),
                            ]),
                        ExcelExport::make('csv')->withFilename('chart-of-accounts-selected')
                            ->withWriterType(ExcelType::CSV)
                            ->withColumns([
                                ExcelColumn::make('code')->heading('Code'),
                                ExcelColumn::make('name')->heading('Account Name'),
                                ExcelColumn::make('accountType.name')->heading('Account Type'),
                                ExcelColumn::make('accountType.classification')->heading('Classification'),
                                ExcelColumn::make('parent.name')->heading('Parent Account'),
                                ExcelColumn::make('is_header')->heading('Header')->formatStateUsing(fn ($s) => $s ? 'Yes' : 'No'),
                                ExcelColumn::make('is_active')->heading('Active')->formatStateUsing(fn ($s) => $s ? 'Active' : 'Inactive'),
                                ExcelColumn::make('notes')->heading('Notes'),
                            ]),
                    ]),
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Chart of Accounts')
                        ->modalDescription('Are you sure you want to delete these accounts? This action cannot be undone.')
                ]),
            ]);
    }

    // ── Infolist (View page) ──────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([

            // ── Account Summary ───────────────────────────────────────
            Section::make('Account Details')
                ->icon('heroicon-o-identification')
                ->columns(3)
                ->schema([
                    TextEntry::make('code')
                        ->label('Account Code')
                        ->fontFamily('mono')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('name')
                        ->label('Account Name')
                        ->weight('bold')
                        ->columnSpan(2),

                    TextEntry::make('accountType.name')
                        ->label('Account Type')
                        ->badge()
                        ->color(fn ($state, $record) => match($record->accountType?->classification ?? '') {
                            'asset'     => 'success',
                            'liability' => 'danger',
                            'equity'    => 'warning',
                            'income'    => 'info',
                            'expense'   => 'gray',
                            default     => 'gray',
                        }),

                    TextEntry::make('accountType.normal_balance')
                        ->label('Normal Balance')
                        ->badge()
                        ->formatStateUsing(fn ($state) => ucfirst($state ?? ''))
                        ->color(fn ($state) => $state === 'debit' ? 'success' : 'primary'),

                    TextEntry::make('is_control_account')
                        ->label('Control Account')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'ap'   => 'AP Control',
                            'ar'   => 'AR Control',
                            'bank' => 'Bank Control',
                            default => 'None',
                        })
                        ->color(fn ($state) => match ($state) {
                            'ap'   => 'warning',
                            'ar'   => 'info',
                            'bank' => 'success',
                            default => 'gray',
                        }),

                    TextEntry::make('financialStatementCategory.name')
                        ->label('Financial Statement Category')
                        ->placeholder('Not assigned'),

                    TextEntry::make('subClassification.name')
                        ->label('Sub-Classification')
                        ->badge()
                        ->color('primary')
                        ->placeholder('Not assigned'),

                    TextEntry::make('parent.name')
                        ->label('Parent Account')
                        ->placeholder('Root account (no parent)'),

                    TextEntry::make('currency.code')
                        ->label('Currency Override')
                        ->badge()
                        ->color('info')
                        ->placeholder('ETB (functional currency)'),
                ]),

            // ── Live Balance ──────────────────────────────────────────
            Section::make('Current GL Balance')
                ->icon('heroicon-o-scale')
                ->columns(3)
                ->schema([
                    TextEntry::make('current_balance')
                        ->label('Balance (all-time)')
                        ->getStateUsing(fn (ChartOfAccount $record) =>
                            $record->is_header
                                ? '— (header account)'
                                : number_format($record->currentBalance(), 2)
                        )
                        ->fontFamily('mono')
                        ->color(fn ($state, ChartOfAccount $record) => match(true) {
                            $record->is_header => 'gray',
                            $record->currentBalance() >= 0 => 'success',
                            default => 'danger',
                        })
                        ->weight('bold'),

                    TextEntry::make('gl_entry_count')
                        ->label('Total GL Postings')
                        ->getStateUsing(fn (ChartOfAccount $record) =>
                            number_format(
                                \App\Models\Finance\GeneralLedger::where('account_id', $record->id)->count()
                            )
                        )
                        ->fontFamily('mono'),

                    TextEntry::make('last_posting_date')
                        ->label('Last Posting Date')
                        ->getStateUsing(fn (ChartOfAccount $record) =>
                            \App\Models\Finance\GeneralLedger::where('account_id', $record->id)
                                ->orderByDesc('transaction_date')
                                ->value('transaction_date') ?? '—'
                        ),
                ]),

            // ── Flags & Status ────────────────────────────────────────
            Section::make('Flags & Status')
                ->icon('heroicon-o-flag')
                ->columns(4)
                ->schema([
                    IconEntry::make('is_header')
                        ->label('Header Account')
                        ->boolean()
                        ->trueIcon('heroicon-o-folder')
                        ->falseIcon('heroicon-o-document')
                        ->trueColor('warning')
                        ->falseColor('gray'),

                    IconEntry::make('is_donor_fund_account')
                        ->label('Donor Fund Account')
                        ->boolean()
                        ->trueIcon('heroicon-o-heart')
                        ->falseIcon('heroicon-o-x-mark')
                        ->trueColor('amber')
                        ->falseColor('gray'),

                    IconEntry::make('is_active')
                        ->label('Active')
                        ->boolean()
                        ->trueIcon('heroicon-o-check-circle')
                        ->falseIcon('heroicon-o-x-circle')
                        ->trueColor('success')
                        ->falseColor('danger'),

                    TextEntry::make('level')
                        ->label('Hierarchy Level')
                        ->badge()
                        ->color('gray'),
                ]),

            // ── Notes ─────────────────────────────────────────────────
            Section::make('Notes')
                ->icon('heroicon-o-pencil-square')
                ->schema([
                    TextEntry::make('notes')
                        ->label('')
                        ->placeholder('No notes recorded.')
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(fn ($record) => empty($record->notes)),
        ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListChartOfAccounts::route('/'),
            'create' => CreateChartOfAccount::route('/create'),
            'view'   => ViewChartOfAccount::route('/{record}'),
            'edit'   => EditChartOfAccount::route('/{record}/edit'),
        ];
    }
}
