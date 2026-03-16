<?php

namespace App\Filament\Resources\Procurement\PurchaseOrders;

use App\Filament\Resources\Procurement\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Resources\Procurement\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Resources\Procurement\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Models\Currency;
use App\Models\Procurement\Requisition;
use App\Models\Procurement\PurchaseOrder;
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
use Filament\Forms\Components\Repeater;
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

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Purchase Orders';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('PO Details')->columns(2)->schema([
                TextInput::make('po_number')
                    ->label('PO Number')->disabled()->dehydrated()->placeholder('Auto-generated'),

                Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()->preload()->required(),

                Select::make('requisition_id')
                    ->label('Linked Requisition (optional)')
                    ->relationship('requisition', 'requisition_number')
                    ->searchable()->preload()->nullable()
                    ->live()
                    ->helperText('Select to auto-seed order items from the approved requisition')
                    ->afterStateUpdated(function (Get $get, \Filament\Schemas\Components\Utilities\Set $set, $state, $livewire) {
                        if (! $state) return;
                        // Only auto-seed if no items have been added yet (create mode)
                        $existing = $get('items') ?? [];
                        $hasItems = collect($existing)->filter(fn ($i) => ! empty($i['description']))->isNotEmpty();
                        if ($hasItems) return;

                        $req   = Requisition::with('items')->find($state);
                        if (! $req) return;

                        $seeded = $req->items->map(fn ($item) => [
                            'description'      => $item->description,
                            'quantity'         => $item->quantity,
                            'unit'             => $item->unit,
                            'unit_price'       => $item->estimated_unit_price,
                            'discount_percent' => 0,
                            'tax_rate'         => 15,
                            'line_total'       => round((float)$item->quantity * (float)$item->estimated_unit_price, 2),
                            'specifications'   => $item->specifications,
                        ])->values()->toArray();

                        $set('items', $seeded);
                    }),

                Select::make('tender_id')
                    ->label('Linked Tender (optional)')
                    ->relationship('tender', 'tender_number')
                    ->searchable()->preload()->nullable(),

                DatePicker::make('order_date')->required()->default(now()->toDateString()),
                DatePicker::make('delivery_date')->required(),
                DatePicker::make('supplier_acknowledged_at')->label('Supplier Acknowledged')->nullable(),

                TextInput::make('delivery_location')->maxLength(200)->nullable(),
                TextInput::make('payment_terms')->maxLength(100)->nullable()->placeholder('e.g., Net 30'),
                Select::make('currency')
                    ->label('Currency')
                    ->options(fn () => Currency::procurementOptions())
                    ->default(fn () => Currency::procurementDefault())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),

                Textarea::make('notes')->rows(2)->columnSpanFull()->nullable(),

                FileUpload::make('attachments')
                    ->multiple()->disk('local')->directory('procurement/pos')
                    ->nullable()->columnSpanFull(),
            ]),

            Section::make('Order Items')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        // Row 1 — Description (full width)
                        TextInput::make('description')
                            ->required()
                            ->maxLength(300)
                            ->columnSpanFull(),

                        // Row 2 — Qty | Unit | Unit Price | Discount %
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(0.0001)
                            ->default(1)
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateLineTotal($get, $set)),
                        Select::make('unit')
                            ->label('Unit')
                            ->options(fn () => \App\Models\Procurement\ProcurementUnit::where('is_active', true)->pluck('name', 'abbreviation')->toArray())
                            ->searchable()
                            ->preload()
                            ->placeholder('Select unit'),
                        TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->numeric()
                            ->default(0)
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateLineTotal($get, $set)),
                        TextInput::make('discount_percent')
                            ->label('Discount %')
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateLineTotal($get, $set)),

                        // Row 3 — VAT % | Net Line Total (span 2) | [empty slot]
                        TextInput::make('tax_rate')
                            ->label('VAT Rate %')
                            ->numeric()
                            ->default(15)
                            ->suffix('%')
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateLineTotal($get, $set)),
                        TextInput::make('line_total')
                            ->label('Net Line Total')
                            ->helperText('After discount · excl. VAT')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(3),

                        // Row 4 — Specifications (full width)
                        Textarea::make('specifications')
                            ->rows(2)
                            ->columnSpanFull()
                            ->nullable(),
                    ])
                    ->columns(4)
                    ->addActionLabel('+ Add Line Item')
                    ->defaultItems(1)
                    ->collapsible()
                    ->cloneable()
                    ->itemLabel(fn (array $state) =>
                        ($state['description'] ?? 'New Item') .
                        (isset($state['quantity'], $state['unit_price'])
                            ? '  ·  ' . (float)$state['quantity'] . ' × ' . number_format((float)$state['unit_price'], 2)
                            : '')
                    )
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateTotals($get, $set)),
            ]),


            // ── Summary & Totals ──────────────────────────────────────────
            Section::make('Summary & Totals')->schema([
                // Row 1: Subtotal, Discount, Net After Discount
                TextInput::make('subtotal')
                    ->label('Subtotal (Pre-Tax)')
                    ->helperText('Sum of all line net amounts (post per-line discount)')
                    ->numeric()
                    ->prefix(fn (Get $get) => Currency::symbolFor($get('currency') ?? 'ETB'))
                    ->disabled()
                    ->dehydrated()
                    ->default(0),

                TextInput::make('discount_amount')
                    ->label('Additional Discount (Amount)')
                    ->helperText('Applied on subtotal before VAT is calculated')
                    ->numeric()
                    ->prefix(fn (Get $get) => Currency::symbolFor($get('currency') ?? 'ETB'))
                    ->default(0)
                    ->live(debounce: 500)
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateTotals($get, $set)),

                TextInput::make('tax_amount')
                    ->label('Total VAT')
                    ->helperText('Computed from (Subtotal − Discount) × per-line VAT %')
                    ->numeric()
                    ->prefix(fn (Get $get) => Currency::symbolFor($get('currency') ?? 'ETB'))
                    ->disabled()
                    ->dehydrated()
                    ->default(0),

                // Row 2: Other charges + Grand Total
                TextInput::make('other_charges_description')
                    ->label('Other Charges Label')
                    ->placeholder('e.g., Shipping & Handling, Customs')
                    ->maxLength(200),

                TextInput::make('other_charges')
                    ->label('Other Charges (Amount)')
                    ->helperText('Added after VAT — e.g. freight, customs')
                    ->numeric()
                    ->prefix(fn (Get $get) => Currency::symbolFor($get('currency') ?? 'ETB'))
                    ->default(0)
                    ->live(debounce: 500)
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateTotals($get, $set)),

                TextInput::make('total_amount')
                    ->label('Grand Total')
                    ->helperText('(Subtotal − Discount) + VAT + Other Charges')
                    ->numeric()
                    ->prefix(fn (Get $get) => Currency::symbolFor($get('currency') ?? 'ETB'))
                    ->extraInputAttributes(['class' => 'font-bold text-lg'])
                    ->disabled()
                    ->dehydrated()
                    ->default(0)
                    ->columnSpanFull(),
            ])->columns(3),

            // ── Approval Trail ──────────────────────────────────────
            Section::make('Approval Trail')
                ->description('Live approval trail — configured under Procurement → Settings → Approval Workflows.')
                ->collapsible()
                ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('purchase_order'))
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('_approval_trail')
                        ->label('')
                        ->columnSpanFull()
                        ->content(fn (?PurchaseOrder $record) =>
                            \App\Services\Procurement\ProcurementApprovalService::renderApprovalTrailHtml($record, 'purchase_order')
                        ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->label('PO #')
                    ->searchable()->sortable()->weight('semibold')->copyable()->copyMessage('Copied!'),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()->sortable(),

                TextColumn::make('order_date')->date()->sortable(),
                TextColumn::make('delivery_date')->date()->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->numeric(2)->sortable()
                    ->formatStateUsing(fn ($state, PurchaseOrder $record) => $record->currency . ' ' . number_format((float)$state, 2)),

                TextColumn::make('approval_stage')
                    ->label('Approval')
                    ->badge()
                    ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('purchase_order'))
                    ->getStateUsing(fn (PurchaseOrder $r) => ProcurementApprovalService::currentStageLabel($r, 'purchase_order'))
                    ->color(fn ($state) => match (true) {
                        str_contains($state, 'Fully Approved') => 'success',
                        str_contains($state, 'Rejected')       => 'danger',
                        str_contains($state, 'Awaiting')       => 'warning',
                        $state === 'Not Started'               => 'gray',
                        default                                => 'info',
                    }),

                TextColumn::make('current_stage')
                    ->label('Stage')
                    ->badge()
                    ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('purchase_order'))
                    ->color(fn ($state) => match (true) {
                        str_contains($state, 'Approved') || str_contains($state, 'Closed') => 'success',
                        str_contains($state, 'Rejected')  => 'danger',
                        str_contains($state, 'Awaiting')  => 'warning',
                        $state === 'Draft'                 => 'gray',
                        default                            => 'info',
                    }),

                TextColumn::make('overall_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved', 'Sent to Supplier', 'Acknowledged', 'Closed' => 'success',
                        'Rejected', 'Cancelled'        => 'danger',
                        'Pending Approval'             => 'warning',
                        'Partially Received'           => 'info',
                        default                        => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('overall_status')
                    ->options([
                        'Draft' => 'Draft', 'Pending Approval' => 'Pending Approval',
                        'Approved' => 'Approved', 'Sent to Supplier' => 'Sent to Supplier',
                        'Acknowledged' => 'Acknowledged', 'Partially Received' => 'Partially Received',
                        'Received' => 'Received', 'Closed' => 'Closed', 'Cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                // Submit for approval
                Action::make('submit')
                    ->label(fn () => \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('purchase_order') ? 'Submit for Approval' : 'Approve PO')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (PurchaseOrder $r) =>
                        $r->overall_status === PurchaseOrder::STATUS_DRAFT
                        && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->action(function (PurchaseOrder $r) {
                        if (! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('purchase_order')) {
                            $r->update(['overall_status' => PurchaseOrder::STATUS_APPROVED]);
                            Notification::make()->title('PO approved directly (no workflow active)')->success()->send();
                        } else {
                            $r->update(['overall_status' => PurchaseOrder::STATUS_PENDING]);
                            ProcurementApprovalService::initialise($r, 'purchase_order');
                            Notification::make()->title('PO submitted — approval workflow started')->info()->send();
                        }
                    }),

                // ── Dynamic Approval via workflow config ──────────────────────────────────
                Action::make('approve')
                    ->label(fn (PurchaseOrder $r) =>
                        '✓ Approve — ' .
                        (ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'purchase_order')?->stage_name ?? 'Approve')
                    )
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PurchaseOrder $r) =>
                        !in_array($r->overall_status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_CANCELLED,
                                                       PurchaseOrder::STATUS_SENT, PurchaseOrder::STATUS_CLOSED,
                                                       PurchaseOrder::STATUS_DRAFT])
                        && \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('purchase_order')
                        && ProcurementApprovalService::canApprove(auth()->user(), $r, 'purchase_order')
                    )
                    ->requiresConfirmation()
                    ->form([Textarea::make('notes')->label('Approval Remarks (optional)')->rows(3)->nullable()])
                    ->action(function (PurchaseOrder $r, array $data) {
                        $user    = auth()->user();
                        $pending = ProcurementApprovalService::pendingRecordFor($user, $r, 'purchase_order');
                        if (! $pending) return;

                        ProcurementApprovalService::approve($r, 'purchase_order', $pending->stage_order, $user, $data['notes'] ?? null);

                        if (ProcurementApprovalService::isFullyApproved($r, 'purchase_order')) {
                            $r->update(['overall_status' => PurchaseOrder::STATUS_APPROVED]);
                            Notification::make()->title('✓ PO fully authorized — ready to send to supplier')->success()->send();
                        } else {
                            Notification::make()->title("✓ Approved: {$pending->stage_name} — advancing to next stage")->success()->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (PurchaseOrder $r) {
                        if ($r->isRejected() || $r->isFullyApproved() || $r->overall_status === PurchaseOrder::STATUS_DRAFT) return false;
                        if (! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('purchase_order')) return false;
                        $pending = ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'purchase_order');
                        return $pending && ($pending->stage?->can_reject ?? true);
                    })
                    ->requiresConfirmation()
                    ->form([Textarea::make('notes')->label('Rejection Reason')->required()->rows(3)])
                    ->action(function (PurchaseOrder $r, array $data) {
                        $user    = auth()->user();
                        $pending = ProcurementApprovalService::pendingRecordFor($user, $r, 'purchase_order');
                        if (! $pending) return;

                        ProcurementApprovalService::reject($r, 'purchase_order', $pending->stage_order, $user, $data['notes'] ?? null);
                        $r->update(['overall_status' => PurchaseOrder::STATUS_CANCELLED]);
                        Notification::make()->title("PO rejected at {$pending->stage_name}")->danger()->send();
                    }),                Action::make('send_to_supplier')
                    ->label('Send to Supplier')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->visible(fn (PurchaseOrder $r) =>
                        $r->overall_status === PurchaseOrder::STATUS_APPROVED
                        && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Send PO to Supplier')
                    ->modalDescription('Confirm that the Purchase Order has been dispatched to the supplier (email/portal/physical copy).')
                    ->action(fn (PurchaseOrder $r) =>
                        $r->update(['overall_status' => PurchaseOrder::STATUS_SENT])
                        && Notification::make()->title('PO sent to supplier')->info()->send()
                    ),

                // Reject
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PurchaseOrder $r) =>
                        ! $r->isRejected()
                        && ! $r->isFullyApproved()
                        && $r->overall_status !== PurchaseOrder::STATUS_DRAFT
                        && (auth()->user()->isProcurementOfficer() || auth()->user()->isProcurementFinance() || auth()->user()->isProcurementDirector())
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Reject Purchase Order')
                    ->action(function (PurchaseOrder $r) {
                        $updates = ['overall_status' => PurchaseOrder::STATUS_CANCELLED];
                        if ($r->canProcurementOfficerApprove()) {
                            $updates['procurement_officer_status'] = 'Rejected';
                        } elseif ($r->canFinanceApprove()) {
                            $updates['finance_status'] = 'Rejected';
                        } else {
                            $updates['director_status'] = 'Rejected';
                        }
                        $r->update($updates);
                        Notification::make()->title('PO rejected')->danger()->send();
                    }),

                EditAction::make(),
                DeleteAction::make()->visible(fn (PurchaseOrder $r) => $r->overall_status === PurchaseOrder::STATUS_DRAFT),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'edit'   => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }

    public static function calculateLineTotal(Get $get, Set $set): void
    {
        $quantity        = (float) ($get('quantity') ?? 0);
        $price           = (float) ($get('unit_price') ?? 0);
        $discountPercent = (float) ($get('discount_percent') ?? 0);
        $taxRate         = (float) ($get('tax_rate') ?? 0);

        $gross          = $quantity * $price;                          // e.g. 100
        $discountAmount = $gross * ($discountPercent / 100);           // e.g. 10
        $netAfterDisc   = max(0, $gross - $discountAmount);            // e.g. 90  (taxable base)

        // line_total = net taxable base (discount already applied, VAT NOT included here)
        // VAT is shown separately in the footer to avoid confusion
        $set('line_total', round($netAfterDisc, 2));
    }

    public static function calculateTotals(Get $get, Set $set): void
    {
        $items         = $get('items') ?? [];
        $subtotal      = 0;   // sum of per-line net amounts (post per-line discount, pre-VAT)
        $totalLineVat  = 0;   // sum of per-line VAT amounts

        foreach ($items as $item) {
            $qty      = (float) ($item['quantity']         ?? 0);
            $price    = (float) ($item['unit_price']       ?? 0);
            $discPct  = (float) ($item['discount_percent'] ?? 0);
            $taxPct   = (float) ($item['tax_rate']         ?? 0);

            $gross      = $qty * $price;
            $discAmt    = $gross * ($discPct / 100);
            $netLine    = max(0, $gross - $discAmt);       // taxable base per line
            $vatLine    = $netLine * ($taxPct / 100);      // VAT per line

            $subtotal     += $netLine;
            $totalLineVat += $vatLine;
        }

        // Additional discount is applied on the pre-tax subtotal
        // (standard in Ethiopian VAT: VAT is on the discounted taxable value)
        $additionalDiscount = (float) ($get('discount_amount') ?? 0);
        $otherCharges       = (float) ($get('other_charges')   ?? 0);

        $discountedSubtotal = max(0, $subtotal - $additionalDiscount);

        // Recalculate total VAT proportionally after additional discount
        // (if additional discount reduces the taxable base, VAT reduces proportionally)
        $vatAfterDiscount = ($subtotal > 0)
            ? $totalLineVat * ($discountedSubtotal / $subtotal)
            : 0;

        $grandTotal = round($discountedSubtotal + $vatAfterDiscount + $otherCharges, 2);

        $set('subtotal',     round($subtotal, 2));
        $set('tax_amount',   round($vatAfterDiscount, 2));
        $set('total_amount', $grandTotal);
    }
}
