<?php

namespace App\Filament\Resources\Finance\Payables;

use App\Filament\Resources\Finance\Payables\Pages\CreatePaymentRequisitions;
use App\Filament\Resources\Finance\Payables\Pages\EditPaymentRequisitions;
use App\Filament\Resources\Finance\Payables\Pages\ListPaymentRequisitions;
use App\Filament\Resources\Finance\Payables\Pages\ViewPaymentRequisitions;
use App\Models\Finance\ApprovalHistory;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\CostCenter;
use App\Models\Finance\PaymentRequisition;
use App\Models\Donor;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\Action as TblAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PaymentRequisitionResource extends Resource
{
    protected static ?string $model                           = PaymentRequisition::class;
    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-document-text';
    protected static string|UnitEnum|null $navigationGroup   = 'Finance';
    protected static ?string $navigationLabel                 = 'Payment Requisitions';
    protected static ?int    $navigationSort                  = 60;
    protected static ?string $slug                            = 'finance/payables/payment-requisitions';
    protected static bool $shouldSkipAuthorization            = true;

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

    // ── Form ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Requisition Details')
                ->icon('heroicon-o-document-text')
                ->columns(3)
                ->schema([
                    TextInput::make('pr_number')
                        ->label('PR Number')->disabled()->dehydrated()
                        ->placeholder('Auto-generated on save'),

                    DatePicker::make('requisition_date')
                        ->label('Requisition Date')->required()->default(today()),

                    Select::make('currency_id')
                        ->label('Currency')->required()->native(false)
                        ->options(fn () => \App\Models\Currency::orderBy('code')->pluck('code', 'id')),
                ]),

            Section::make('Payee Information')
                ->icon('heroicon-o-user')
                ->columns(2)
                ->schema([
                    TextInput::make('payee_name')->label('Payee Name')->required()->maxLength(120),
                    TextInput::make('payee_tin')->label('TIN')->maxLength(30)->nullable(),
                    TextInput::make('payee_bank_name')->label('Bank Name')->nullable(),
                    TextInput::make('payee_account_number')->label('Account Number')->nullable(),
                    TextInput::make('invoice_number')->label('Invoice No.')->nullable(),
                    DatePicker::make('invoice_date')->label('Invoice Date')->nullable(),
                ]),

            Section::make('Line Items')
                ->icon('heroicon-o-list-bullet')
                ->schema([
                    Repeater::make('lines')
                        ->relationship('lines')
                        ->schema([
                            Select::make('chart_of_account_id')
                                ->label('Expense Account')->required()->native(false)->searchable()
                                ->options(fn () => ChartOfAccount::where('is_active', true)
                                    ->whereHas('accountType', fn ($q) => $q->where('classification', 'expense'))
                                    ->orderBy('code')
                                    ->get()->mapWithKeys(fn ($a) => [$a->id => "[{$a->code}] {$a->name}"])
                                    ->toArray()
                                )->columnSpan(3),
                            TextInput::make('description')->label('Description')->required()->columnSpan(2),
                            TextInput::make('quantity')->label('Qty')->numeric()->default(1)->required(),
                            TextInput::make('unit_price')->label('Unit Price')->numeric()->required(),
                            TextInput::make('line_total')->label('Total')->disabled()->dehydrated()->numeric(),
                        ])
                        ->columns(8)
                        ->addActionLabel('Add Item')
                        ->columnSpanFull(),
                ]),

            Section::make('Taxes & Net Payable')
                ->icon('heroicon-o-calculator')
                ->columns(4)
                ->schema([
                    TextInput::make('total_amount')->label('Gross Amount')->numeric()->required(),
                    TextInput::make('withholding_tax_amount')->label('WHT Amount')->numeric()->default(0),
                    TextInput::make('vat_amount')->label('VAT Amount')->numeric()->default(0),
                    TextInput::make('net_payable')->label('Net Payable')->numeric()->required(),
                ]),

            Section::make('4-Dimension Coding')
                ->icon('heroicon-o-tag')
                ->columns(4)
                ->schema([
                    Select::make('cost_center_id')->label('Cost Center')->required()->native(false)
                        ->options(fn () => CostCenter::where('is_active', true)->pluck('name', 'id')),
                    Select::make('project_id')->label('Project')->native(false)->nullable()
                        ->options(fn () => Project::orderBy('project_name')->pluck('project_name', 'id')),
                    Select::make('donor_id')->label('Donor')->native(false)->nullable()
                        ->options(fn () => Donor::orderBy('first_name')->get()->mapWithKeys(fn ($d) => [$d->id => $d->full_name])),
                    TextInput::make('activity_code')->label('Activity Code')->nullable(),
                    TextInput::make('donor_code')->label('Donor Code')->nullable(),
                    TextInput::make('exchange_rate_to_base')->label('Exchange Rate')->numeric()->default(1),
                ]),

            Section::make('Notes')
                ->schema([Textarea::make('notes')->rows(3)->nullable()->columnSpanFull()])
                ->collapsible(),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pr_number')->label('PR #')->badge()->color('primary')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('requisition_date')->label('Date')->date()->sortable(),
                TextColumn::make('payee_name')->label('Payee')->limit(28)->searchable(),
                TextColumn::make('total_amount')->label('Gross')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono'),
                TextColumn::make('withholding_tax_amount')->label('WHT')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('net_payable')->label('Net Payable')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono')->weight('bold'),
                TextColumn::make('currency.code')->label('CCY')->badge()->color('gray'),
                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'draft' => 'gray', 'pending_approval' => 'warning',
                        'approved' => 'success', 'rejected' => 'danger', 'paid' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('preparedBy.name')->label('By')->limit(18)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([SelectFilter::make('status')->options(PaymentRequisition::statuses())])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (PaymentRequisition $r) => $r->isDraft()),
                DeleteAction::make()->visible(fn (PaymentRequisition $r) => $r->isDraft()),

                TblAction::make('tbl_submit')
                    ->label('Submit')->icon('heroicon-o-paper-airplane')->color('warning')->button()
                    ->visible(fn (PaymentRequisition $r) => $r->isDraft())
                    ->requiresConfirmation()
                    ->action(function (PaymentRequisition $record) {
                        $prev = $record->status;
                        $record->forceFill(['status' => 'pending_approval'])->save();
                        ApprovalHistory::log($record, 'forwarded', 'Finance Officer Submission', 1, $prev, 'pending_approval');
                        Notification::make()->success()->title('PR submitted for approval.')->send();
                    }),

                TblAction::make('tbl_approve')
                    ->label('Approve')->icon('heroicon-o-check-badge')->color('success')->button()
                    ->visible(fn (PaymentRequisition $r) =>
                        $r->isPendingApproval() &&
                        (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin())
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Approve Payment Requisition')
                    ->form([Textarea::make('comments')->label('Approval Comments')->rows(2)])
                    ->action(function (PaymentRequisition $record, array $data) {
                        $prev = $record->status;
                        $record->forceFill([
                            'status'      => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ])->save();
                        ApprovalHistory::log($record, 'approved', 'Finance Manager Approval', 2, $prev, 'approved', $data['comments'] ?? null);
                        Notification::make()->success()->title('PR approved.')->send();
                    }),

                TblAction::make('tbl_reject')
                    ->label('Reject')->icon('heroicon-o-x-circle')->color('danger')->button()
                    ->visible(fn (PaymentRequisition $r) =>
                        $r->isPendingApproval() &&
                        (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin())
                    )
                    ->requiresConfirmation()
                    ->form([Textarea::make('comments')->label('Rejection Reason')->required()->rows(2)])
                    ->action(function (PaymentRequisition $record, array $data) {
                        $prev = $record->status;
                        $record->forceFill(['status' => 'rejected'])->save();
                        ApprovalHistory::log($record, 'rejected', 'Finance Manager Rejection', 2, $prev, 'rejected', $data['comments']);
                        Notification::make()->danger()->title('PR rejected.')->send();
                    }),
            ])
            ->defaultSort('requisition_date', 'desc');
    }

    // ── Infolist ──────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            InfoSection::make('Requisition Details')
                ->icon('heroicon-o-document-text')->columns(4)
                ->schema([
                    TextEntry::make('pr_number')->label('PR #')->badge()->color('primary')->fontFamily('mono'),
                    TextEntry::make('requisition_date')->label('Date')->date(),
                    TextEntry::make('status')->label('Status')->badge()
                        ->color(fn ($state) => match($state) {
                            'draft' => 'gray', 'pending_approval' => 'warning',
                            'approved' => 'success', 'rejected' => 'danger', 'paid' => 'info',
                            default => 'gray',
                        }),
                    TextEntry::make('currency.code')->label('Currency')->badge()->color('gray'),
                    TextEntry::make('payee_name')->label('Payee')->columnSpan(2),
                    TextEntry::make('payee_tin')->label('TIN')->placeholder('—'),
                    TextEntry::make('payee_bank_name')->label('Bank')->placeholder('—'),
                    TextEntry::make('invoice_number')->label('Invoice No.')->placeholder('—'),
                    TextEntry::make('invoice_date')->label('Invoice Date')->date()->placeholder('—'),
                ]),

            InfoSection::make('Amounts')->icon('heroicon-o-calculator')->columns(4)
                ->schema([
                    TextEntry::make('total_amount')->label('Gross Amount')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('withholding_tax_amount')->label('WHT')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('vat_amount')->label('VAT')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('net_payable')->label('Net Payable')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold'),
                ]),

            InfoSection::make('Dimension Coding')->icon('heroicon-o-tag')->columns(4)
                ->schema([
                    TextEntry::make('costCenter.name')->label('Cost Center'),
                    TextEntry::make('project.project_name')->label('Project')->placeholder('—'),
                    TextEntry::make('donor.full_name')->label('Donor')->placeholder('—'),
                    TextEntry::make('activity_code')->label('Activity Code')->placeholder('—'),
                ]),

            InfoSection::make('Approval Trail')->icon('heroicon-o-clipboard-document-check')->columns(1)
                ->schema([
                    RepeatableEntry::make('approvalHistories')
                        ->schema([
                            TextEntry::make('stage_name')->label('Stage')->badge()->color('info'),
                            TextEntry::make('action')->label('Action')->badge()
                                ->color(fn ($s) => match($s) {
                                    'approved' => 'success', 'rejected' => 'danger',
                                    'returned' => 'warning', default => 'gray',
                                }),
                            TextEntry::make('actor.name')->label('By'),
                            TextEntry::make('actioned_at')->label('At')->dateTime(),
                            TextEntry::make('comments')->label('Comments')->placeholder('—'),
                        ])->columns(5),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPaymentRequisitions::route('/'),
            'create' => CreatePaymentRequisitions::route('/create'),
            'view'   => ViewPaymentRequisitions::route('/{record}'),
            'edit'   => EditPaymentRequisitions::route('/{record}/edit'),
        ];
    }
}
