<?php

namespace App\Filament\Resources\Procurement\Invoices;

use App\Filament\Resources\Procurement\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Procurement\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Procurement\Invoices\Pages\ListInvoices;
use App\Models\Currency;
use App\Models\Procurement\Invoice;
use App\Models\Procurement\ThreeWayMatch;
use App\Services\Procurement\ProcurementApprovalService;
use BackedEnum;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Placeholder;
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
                TextInput::make('invoice_number')
                    ->label('Invoice #')
                    ->disabled()->dehydrated()
                    ->placeholder('Auto-generated'),

                TextInput::make('supplier_invoice_number')
                    ->label("Supplier's Invoice #")
                    ->maxLength(100)->nullable(),

                Select::make('purchase_order_id')
                    ->label('Purchase Order')
                    ->relationship('purchaseOrder', 'po_number')
                    ->searchable()->preload()->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        if (! $state) return;

                        $po = \App\Models\Procurement\PurchaseOrder::with('supplier')->find($state);
                        if (! $po) return;

                        // Auto-fill from PO — user can override if invoice differs
                        $set('supplier_id',  $po->supplier_id);
                        $set('currency',     $po->currency);
                        $set('subtotal',     (float) $po->subtotal);
                        $set('tax_amount',   (float) $po->tax_amount);
                        $set('total_amount', (float) $po->total_amount);
                    })
                    ->helperText('Selecting a PO auto-fills amounts below — adjust if the supplier billed differently'),

                Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()->preload()->required(),

                DatePicker::make('invoice_date')->required()->default(now()->toDateString()),
                DatePicker::make('due_date')->required(),

                Select::make('currency')
                    ->label('Currency')
                    ->options(fn () => Currency::procurementOptions())
                    ->default(fn () => Currency::procurementDefault())
                    ->searchable()->preload()->live()->required(),
            ]),

            Section::make('Financial Summary')->columns(3)->schema([
                TextInput::make('subtotal')
                    ->label('Subtotal (Pre-Tax)')
                    ->helperText('Sum of line net amounts from PO')
                    ->numeric()
                    ->prefix(fn (Get $get) => Currency::symbolFor($get('currency') ?? 'ETB'))
                    ->required()->default(0)
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $set('total_amount', round(
                            (float)$get('subtotal') + (float)$get('tax_amount'), 2
                        ));
                    }),

                TextInput::make('tax_amount')
                    ->label('VAT / Tax Amount')
                    ->helperText('Adjust only if supplier billed different tax')
                    ->numeric()
                    ->prefix(fn (Get $get) => Currency::symbolFor($get('currency') ?? 'ETB'))
                    ->default(0)
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $set('total_amount', round(
                            (float)$get('subtotal') + (float)$get('tax_amount'), 2
                        ));
                    }),

                TextInput::make('total_amount')
                    ->label('Invoice Total')
                    ->helperText('Subtotal + VAT — must match supplier invoice')
                    ->numeric()
                    ->prefix(fn (Get $get) => Currency::symbolFor($get('currency') ?? 'ETB'))
                    ->default(0)
                    ->disabled()->dehydrated(),

                Textarea::make('notes')->rows(2)->columnSpanFull()->nullable(),

                FileUpload::make('attachments')
                    ->label('Invoice Documents')
                    ->multiple()->disk('local')->directory('procurement/invoices')
                    ->nullable()->columnSpanFull(),
            ]),

            Section::make('Approval Trail')
                ->description('Live approval trail — updates instantly when approvers act on the list view.')
                ->collapsible()
                ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('invoice'))
                ->schema([
                    Placeholder::make('_approval_trail')
                        ->label('')
                        ->columnSpanFull()
                        ->content(fn (?Invoice $record) =>
                            ProcurementApprovalService::renderApprovalTrailHtml($record, 'invoice')
                        ),
                ]),
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
                TextColumn::make('total_amount')->label('Total')->numeric(2)->sortable()
                    ->formatStateUsing(fn ($state, Invoice $record) => ($record->currency ?? 'ETB') . ' ' . number_format((float)$state, 2)),
                TextColumn::make('approval_stage')
                    ->label('Approval')
                    ->badge()
                    ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('invoice'))
                    ->getStateUsing(fn (Invoice $r) => ProcurementApprovalService::currentStageLabel($r, 'invoice'))
                    ->color(fn ($state) => match (true) {
                        str_contains($state, 'Fully Approved') => 'success',
                        str_contains($state, 'Rejected')       => 'danger',
                        str_contains($state, 'Awaiting')       => 'warning',
                        $state === 'Not Started'               => 'gray',
                        default                                => 'info',
                    }),
                TextColumn::make('current_stage')->label('Stage')->badge()
                    ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('invoice'))
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
            ])
            ->recordActions([
                // Submit invoice
                Action::make('submit')
                    ->label(fn () => \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('invoice') ? 'Submit for Approval' : 'Approve Invoice')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Invoice $r) => $r->status === Invoice::STATUS_DRAFT && auth()->user()->isProcurementFinance())
                    ->requiresConfirmation()
                    ->action(function (Invoice $r) {
                        if (! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('invoice')) {
                            $r->update(['status' => Invoice::STATUS_APPROVED]);
                            Notification::make()->title('Invoice approved directly (no workflow active)')->success()->send();
                        } else {
                            $r->update(['status' => Invoice::STATUS_SUBMITTED]);
                            ProcurementApprovalService::initialise($r, 'invoice');
                            Notification::make()->title('Invoice submitted — approval workflow started')->info()->send();
                        }
                    }),

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
                            Notification::make()->title('  3-Way Match: PASSED — Invoice matched to PO & GRN')->success()->send();
                        } else {
                            $invoice->update(['status' => Invoice::STATUS_DISPUTED]);
                            Notification::make()
                                ->title("⚠️ 3-Way Match: {$matchStatus} — Variance: ETB " . number_format($variance, 2))
                                ->warning()->send();
                        }
                    }),

                // ── Dynamic Approval via workflow config ──────────────────────────────────
                Action::make('approve')
                    ->label(fn (Invoice $r) =>
                        '✓ Approve — ' .
                        (ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'invoice')?->stage_name ?? 'Approve')
                    )
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Invoice $r) =>
                        !in_array($r->status, [Invoice::STATUS_PAID, Invoice::STATUS_REJECTED])
                        && \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('invoice')
                        && ProcurementApprovalService::canApprove(auth()->user(), $r, 'invoice')
                    )
                    ->requiresConfirmation()
                    ->form([Textarea::make('notes')->label('Approval Remarks (optional)')->rows(3)->nullable()])
                    ->action(function (Invoice $r, array $data) {
                        $user    = auth()->user();
                        $pending = ProcurementApprovalService::pendingRecordFor($user, $r, 'invoice');
                        if (! $pending) return;

                        ProcurementApprovalService::approve($r, 'invoice', $pending->stage_order, $user, $data['notes'] ?? null);

                        if (ProcurementApprovalService::isFullyApproved($r, 'invoice')) {
                            $r->update(['status' => Invoice::STATUS_APPROVED]);
                            Notification::make()->title('✓ Invoice fully approved — ready for payment scheduling')->success()->send();
                        } else {
                            Notification::make()->title("✓ Approved: {$pending->stage_name} — advancing to next stage")->success()->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (Invoice $r) {
                        if (in_array($r->status, [Invoice::STATUS_PAID, Invoice::STATUS_REJECTED])) return false;
                        if (! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('invoice')) return false;
                        $pending = ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'invoice');
                        return $pending && ($pending->stage?->can_reject ?? true);
                    })
                    ->requiresConfirmation()
                    ->form([Textarea::make('notes')->label('Rejection Reason')->required()->rows(3)])
                    ->action(function (Invoice $r, array $data) {
                        $user    = auth()->user();
                        $pending = ProcurementApprovalService::pendingRecordFor($user, $r, 'invoice');
                        if (! $pending) return;

                        ProcurementApprovalService::reject($r, 'invoice', $pending->stage_order, $user, $data['notes'] ?? null);
                        $r->update(['status' => Invoice::STATUS_REJECTED]);
                        Notification::make()->title("Invoice rejected at {$pending->stage_name}")->danger()->send();
                    }),

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
