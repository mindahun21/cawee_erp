<?php

namespace App\Filament\Resources\Procurement\Invoices;

use App\Filament\Resources\Procurement\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Procurement\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Procurement\Invoices\Pages\ListInvoices;
use App\Models\Procurement\Invoice;
use App\Models\Procurement\ThreeWayMatch;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';
    protected static ?string $navigationLabel = 'Supplier Invoices';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Invoice Details')->columns(2)->schema([
                TextInput::make('invoice_number')->label('Invoice #')->disabled()->dehydrated()->placeholder('Auto-generated'),
                TextInput::make('supplier_invoice_number')->label("Supplier's Invoice #")->maxLength(100)->nullable(),

                Select::make('purchase_order_id')
                    ->label('Purchase Order')
                    ->relationship('purchaseOrder', 'po_number')
                    ->searchable()->preload()->required(),

                Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()->preload()->required(),

                DatePicker::make('invoice_date')->required()->default(now()->toDateString()),
                DatePicker::make('due_date')->required(),

                TextInput::make('subtotal')->numeric()->prefix('ETB')->required()->default(0),
                TextInput::make('tax_amount')->numeric()->prefix('ETB')->default(0)->label('VAT / Tax Amount'),
                TextInput::make('total_amount')->numeric()->prefix('ETB')->default(0)->disabled()->dehydrated(),
                TextInput::make('currency')->default('ETB')->maxLength(10),

                Textarea::make('notes')->rows(2)->columnSpanFull()->nullable(),

                FileUpload::make('attachments')
                    ->label('Invoice Documents')
                    ->multiple()->disk('local')->directory('procurement/invoices')
                    ->nullable()->columnSpanFull(),
            ]),

            Section::make('Approval Trail')
                ->description('Approved via action buttons on the list view.')
                ->columns(2)
                ->schema([
                    Select::make('finance_status')
                        ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'])
                        ->default('Pending')->disabled()->dehydrated()->label('Finance'),
                    Select::make('director_status')
                        ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'])
                        ->default('Pending')->disabled()->dehydrated()->label('Director'),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->label('Invoice #')->searchable()->sortable()->weight('semibold')->copyable()->copyMessage('Copied!'),
                TextColumn::make('supplier_invoice_number')->label("Supp. Invoice #")->searchable()->toggleable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable()->sortable(),
                TextColumn::make('purchaseOrder.po_number')->label('PO #')->searchable(),
                TextColumn::make('invoice_date')->date()->sortable(),
                TextColumn::make('due_date')
                    ->date()->sortable()
                    ->color(fn (Invoice $record) =>
                        $record->isOverdue() ? 'danger' : null
                    ),
                TextColumn::make('total_amount')->label('Total (ETB)')->numeric(2)->prefix('ETB ')->sortable(),
                TextColumn::make('finance_status')->label('Finance')->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved' => 'success', 'Rejected' => 'danger', default => 'warning',
                    }),
                TextColumn::make('director_status')->label('Director')->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved' => 'success', 'Rejected' => 'danger', default => 'warning',
                    }),
                TextColumn::make('current_stage')->label('Stage')->badge()
                    ->color(fn ($state) => match (true) {
                        str_contains($state, 'Paid ✓') || str_contains($state, 'Approved') => 'success',
                        str_contains($state, 'Rejected')    => 'danger',
                        str_contains($state, 'Awaiting')    => 'warning',
                        default                              => 'info',
                    }),
                TextColumn::make('status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'Paid'     => 'success',
                        'Approved' => 'success',
                        'Matched'  => 'info',
                        'Overdue'  => 'danger',
                        'Disputed' => 'warning',
                        'Rejected' => 'danger',
                        default    => 'gray',
                    }),
            ])
            ->defaultSort('due_date', 'asc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Matched' => 'Matched',
                        'Approved' => 'Approved', 'Paid' => 'Paid', 'Overdue' => 'Overdue',
                        'Disputed' => 'Disputed', 'Rejected' => 'Rejected',
                    ]),
                SelectFilter::make('finance_status')
                    ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected']),
            ])
            ->recordActions([
                // Submit invoice
                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Invoice $r) => $r->status === Invoice::STATUS_DRAFT && auth()->user()->isProcurementFinance())
                    ->requiresConfirmation()
                    ->action(fn (Invoice $r) =>
                        $r->update(['status' => Invoice::STATUS_SUBMITTED])
                        && Notification::make()->title('Invoice submitted — run 3-way match')->info()->send()
                    ),

                // Run 3-Way Match
                Action::make('run_match')
                    ->label('Run 3-Way Match')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('primary')
                    ->visible(fn (Invoice $r) =>
                        $r->status === Invoice::STATUS_SUBMITTED && auth()->user()->isProcurementFinance()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('3-Way Match Verification')
                    ->modalDescription('System will compare this invoice against the Purchase Order and Goods Receipt. Proceed?')
                    ->action(function (Invoice $invoice) {
                        $po   = $invoice->purchaseOrder;
                        $grn  = $po?->goodsReceipts()->where('status', 'Accepted')->first();

                        $matchStatus = 'Pending';
                        $variance    = 0;

                        if ($po && $grn) {
                            $poAmt      = (float) $po->total_amount;
                            $invoiceAmt = (float) $invoice->total_amount;
                            $variance   = $invoiceAmt - $poAmt;
                            $matchStatus = abs($variance) < 0.01 ? 'Matched' : 'Price Mismatch';
                        } elseif (!$grn) {
                            $matchStatus = 'Quantity Mismatch';
                            $variance    = (float) $invoice->total_amount;
                        }

                        ThreeWayMatch::updateOrCreate(
                            ['invoice_id' => $invoice->id],
                            [
                                'purchase_order_id' => $po?->id,
                                'goods_receipt_id'  => $grn?->id,
                                'match_status'      => $matchStatus,
                                'po_amount'         => $po?->total_amount ?? 0,
                                'grn_amount'        => $po?->total_amount ?? 0,
                                'invoice_amount'    => $invoice->total_amount,
                                'variance'          => $variance,
                                'matched_by'        => auth()->id(),
                                'matched_at'        => now(),
                            ]
                        );

                        if ($matchStatus === 'Matched') {
                            $invoice->update(['status' => Invoice::STATUS_MATCHED]);
                            Notification::make()->title('✅ 3-Way Match: PASSED — Invoice matched to PO & GRN')->success()->send();
                        } else {
                            $invoice->update(['status' => Invoice::STATUS_DISPUTED]);
                            Notification::make()
                                ->title("⚠️ 3-Way Match: {$matchStatus} — Variance: ETB " . number_format($variance, 2))
                                ->warning()->send();
                        }
                    }),

                // Finance Approve
                Action::make('finance_approve')
                    ->label('Approve (Finance)')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Invoice $r) => $r->canFinanceApprove() && auth()->user()->isProcurementFinance())
                    ->requiresConfirmation()
                    ->modalHeading('Finance Invoice Approval')
                    ->modalDescription('Approve this invoice for payment processing. It will advance to the Director for final authorization.')
                    ->form([Textarea::make('finance_remarks')->label('Remarks (optional)')->rows(3)->nullable()])
                    ->action(function (Invoice $r, array $data) {
                        $r->update([
                            'finance_status'      => 'Approved',
                            'finance_approved_by' => auth()->id(),
                            'finance_approved_at' => now(),
                            'finance_remarks'     => $data['finance_remarks'] ?? null,
                        ]);
                        Notification::make()->title('✓ Invoice approved by Finance — forwarded to Director')->success()->send();
                    }),

                // Director Approve (Final)
                Action::make('director_approve')
                    ->label('Authorize (Director)')
                    ->icon('heroicon-o-shield-check')
                    ->color('primary')
                    ->visible(fn (Invoice $r) => $r->canDirectorApprove() && auth()->user()->isProcurementDirector())
                    ->requiresConfirmation()
                    ->modalHeading('Director Invoice Authorization')
                    ->modalDescription('Final authorization to process payment for this invoice.')
                    ->modalSubmitActionLabel('Authorize')
                    ->action(function (Invoice $r) {
                        $r->update([
                            'director_status'      => 'Approved',
                            'director_approved_by' => auth()->id(),
                            'director_approved_at' => now(),
                            'status'               => Invoice::STATUS_APPROVED,
                        ]);
                        Notification::make()->title('✅ Invoice authorized — ready for payment scheduling')->success()->send();
                    }),

                // Reject
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Invoice $r) =>
                        !in_array($r->status, [Invoice::STATUS_PAID, Invoice::STATUS_REJECTED])
                        && (auth()->user()->isProcurementFinance() || auth()->user()->isProcurementDirector())
                    )
                    ->requiresConfirmation()
                    ->form([Textarea::make('rejection_reason')->label('Rejection Reason')->required()->rows(3)])
                    ->action(fn (Invoice $r, array $data) =>
                        $r->update(['status' => Invoice::STATUS_REJECTED, 'finance_remarks' => $data['rejection_reason']])
                        && Notification::make()->title('Invoice rejected')->danger()->send()
                    ),

                EditAction::make(),
                DeleteAction::make()->visible(fn (Invoice $r) => $r->status === Invoice::STATUS_DRAFT),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit'   => EditInvoice::route('/{record}/edit'),
        ];
    }
}
