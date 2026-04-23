<?php

namespace App\Filament\Resources\Finance\Payroll;

use App\Filament\Resources\Finance\Payroll\Pages\CreatePayrollSummaries;
use App\Filament\Resources\Finance\Payroll\Pages\ListPayrollSummaries;
use App\Filament\Resources\Finance\Payroll\Pages\ViewPayrollSummaries;
use App\Models\Employee;
use App\Models\Finance\CostCenter;
use App\Models\Finance\PayrollSummary;
use App\Models\Payroll;
use App\Services\Finance\PayrollGLPostingService;
use BackedEnum;
use Filament\Actions\Action as TblAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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

class PayrollSummaryResource extends Resource
{
    protected static ?string $model                          = PayrollSummary::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static string|UnitEnum|null $navigationGroup  = 'Finance';
    protected static ?string $navigationLabel               = 'Payroll Summaries';
    protected static ?int    $navigationSort                = 70;
    protected static ?string $slug                          = 'finance/payroll/summaries';
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
    public static function canEdit($r): bool   { return $r->isDraft() && static::canViewAny(); }
    public static function canDelete($r): bool { return $r->isDraft() && static::canViewAny(); }
    public static function canView($r): bool   { return static::canViewAny(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payroll Reference')->icon('heroicon-o-user-circle')->columns(3)->schema([
                Select::make('employee_id')->label('Employee')->required()->native(false)->searchable()
                    ->options(fn () => Employee::orderBy('first_name')
                        ->get()->mapWithKeys(fn ($e) => [$e->id => $e->full_name . ' (' . ($e->position ?? 'N/A') . ')'])),
                Select::make('payroll_id')->label('HR Payroll Record')->native(false)->searchable()->nullable()
                    ->options(fn () => Payroll::with('employee')->orderByDesc('year')->orderByDesc('month')->take(200)
                        ->get()->mapWithKeys(fn ($p) => [$p->id => "{$p->employee?->full_name} — {$p->year}-{$p->month}"])),
                Select::make('cost_center_id')->label('Cost Center')->native(false)->nullable()
                    ->options(fn () => CostCenter::where('is_active', true)->pluck('name', 'id')),

                TextInput::make('payroll_month')->label('Month (1-12)')->numeric()->required()
                    ->minValue(1)->maxValue(12),
                TextInput::make('payroll_year')->label('Year')->numeric()->required()
                    ->default(now()->year)->minValue(2020)->maxValue(2100),
                Select::make('currency_id')->label('Currency')->native(false)->nullable()
                    ->options(fn () => \App\Models\Currency::orderBy('code')->pluck('code', 'id')),
            ]),

            Section::make('Salary Components')->icon('heroicon-o-calculator')->columns(4)->schema([
                TextInput::make('basic_salary')->label('Basic Salary')->numeric()->required()->default(0),
                TextInput::make('allowances_total')->label('Allowances')->numeric()->default(0),
                TextInput::make('gross_pay')->label('Gross Pay')->numeric()->required()->default(0),
                TextInput::make('income_tax_withheld')->label('Income Tax (PAYE)')->numeric()->default(0),
                TextInput::make('pension_employee')->label('Pension (Employee 7%)')->numeric()->default(0),
                TextInput::make('pension_employer')->label('Pension (Employer 11%)')->numeric()->default(0),
                TextInput::make('deductions_total')->label('Total Deductions')->numeric()->default(0),
                TextInput::make('net_pay')->label('Net Pay')->numeric()->required()->default(0),
                TextInput::make('employer_total_cost')->label('Employer Total Cost')->numeric()->default(0)
                    ->helperText('Net Pay + Employer Pension'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.first_name')->label('Employee')
                    ->formatStateUsing(fn ($state, $record) => $record->employee?->full_name ?? '—')
                    ->searchable(['first_name', 'last_name'])->sortable(),
                TextColumn::make('payroll_month')->label('Month')
                    ->formatStateUsing(fn ($state, $record) => \Carbon\Carbon::createFromDate($record->payroll_year, $state, 1)->format('M Y')),
                TextColumn::make('gross_pay')->label('Gross')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono'),
                TextColumn::make('income_tax_withheld')->label('PAYE Tax')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pension_employee')->label('Pension (EE)')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pension_employer')->label('Pension (ER)')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('net_pay')->label('Net Pay')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono')->weight('bold'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($s) => match($s) { 'draft' => 'gray', 'journal_posted' => 'success', default => 'gray' }),
            ])
            ->filters([SelectFilter::make('status')->options(PayrollSummary::statuses())])
            ->recordActions([
                ViewAction::make(),
                TblAction::make('post_to_gl')
                    ->label('Post to GL')->icon('heroicon-o-arrow-up-circle')->color('success')->button()
                    ->visible(fn (PayrollSummary $r) => $r->isDraft() && auth()->user()?->isFinanceManager())
                    ->requiresConfirmation()
                    ->modalHeading('Post Payroll to General Ledger')
                    ->modalDescription('This will generate a double-entry journal for salary, pension and income tax. This cannot be undone.')
                    ->action(function (PayrollSummary $record) {
                        try {
                            app(PayrollGLPostingService::class)->postToGL($record);
                            Notification::make()->success()->title('Payroll posted to GL.')->send();
                        } catch (\Throwable $e) {
                            Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
                        }
                    }),
            ])
            ->defaultSort('payroll_year', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payroll Summary')->icon('heroicon-o-banknotes')->columns(4)->schema([
                TextEntry::make('employee.first_name')->label('Employee')
                    ->formatStateUsing(fn ($state, $record) => $record->employee?->full_name ?? '—'),
                TextEntry::make('payroll_month')->label('Period')
                    ->formatStateUsing(fn ($state, $record) => \Carbon\Carbon::createFromDate($record->payroll_year, $state, 1)->format('F Y')),
                TextEntry::make('status')->label('Status')->badge()
                    ->color(fn ($s) => match($s) { 'draft' => 'gray', 'journal_posted' => 'success', default => 'gray' }),
                TextEntry::make('costCenter.name')->label('Cost Center')->placeholder('—'),
            ]),
            Section::make('GL Amounts')->icon('heroicon-o-calculator')->columns(4)->schema([
                TextEntry::make('basic_salary')->label('Basic Salary')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextEntry::make('allowances_total')->label('Allowances')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextEntry::make('gross_pay')->label('Gross Pay')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold'),
                TextEntry::make('income_tax_withheld')->label('PAYE Income Tax')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextEntry::make('pension_employee')->label('Employee Pension (7%)')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextEntry::make('pension_employer')->label('Employer Pension (11%)')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextEntry::make('deductions_total')->label('Total Deductions')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextEntry::make('net_pay')->label('Net Pay')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold'),
                TextEntry::make('employer_total_cost')->label('Employer Total Cost')->numeric(decimalPlaces: 2)->fontFamily('mono'),
            ]),
            Section::make('GL Posting')->icon('heroicon-o-document-check')->columns(2)->schema([
                TextEntry::make('preparedBy.name')->label('Prepared By')->placeholder('—'),
                TextEntry::make('journalEntry.reference_number')->label('Journal Entry')->badge()->color('success')->placeholder('Not posted'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPayrollSummaries::route('/'),
            'create' => CreatePayrollSummaries::route('/create'),
            'view'   => ViewPayrollSummaries::route('/{record}'),
        ];
    }
}
