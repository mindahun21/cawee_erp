<?php

namespace App\Filament\Resources\Procurement\GoodsReceipts;

use App\Filament\Resources\Procurement\GoodsReceipts\Pages\CreateGoodsReceipt;
use App\Filament\Resources\Procurement\GoodsReceipts\Pages\EditGoodsReceipt;
use App\Filament\Resources\Procurement\GoodsReceipts\Pages\ListGoodsReceipts;
use App\Models\Procurement\GoodsReceipt;
use App\Models\Procurement\SupplierReturn;
use BackedEnum;
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
use App\Traits\BelongsToModule;

class GoodsReceiptResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = GoodsReceipt::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;
    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';
    protected static ?string $navigationLabel = 'Goods Receipts (GRN)';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Receipt Details')->columns(2)->schema([
                TextInput::make('grn_number')->label('GRN #')->disabled()->dehydrated()->placeholder('Auto-generated'),

                Select::make('purchase_order_id')
                    ->label('Purchase Order')
                    ->relationship('purchaseOrder', 'po_number')
                    ->searchable()->preload()->required(),

                DatePicker::make('receipt_date')->required()->default(now()->toDateString()),
                TextInput::make('delivery_note_number')->maxLength(100)->nullable()->label('Delivery Note #'),
                TextInput::make('delivery_location')->maxLength(200)->nullable(),

                Select::make('overall_condition')
                    ->options(['Good' => 'Good', 'Partial' => 'Partial', 'Rejected' => 'Rejected'])
                    ->default('Good')->required(),

                Textarea::make('inspection_notes')
                    ->label('Overall Inspection Notes')
                    ->helperText('Summary observations after inspecting the full shipment')
                    ->rows(3)->columnSpanFull()->nullable(),

                FileUpload::make('attachments')
                    ->multiple()->disk('local')->directory('procurement/grn')
                    ->nullable()->columnSpanFull(),
            ]),

            Section::make('Items Received & Inspected')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        // Row 1: PO Line (full width)
                        Select::make('po_item_id')
                            ->label('PO Line Item')
                            ->relationship('poItem', 'description')
                            ->searchable()->preload()->required()
                            ->columnSpanFull(),

                        // Row 2: Received | Accepted | Rejected | Condition
                        TextInput::make('received_quantity')
                            ->label('Received Qty')
                            ->numeric()->minValue(0)->default(0)->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) {
                                $received = (float) $state;
                                $accepted = (float) ($get('accepted_quantity') ?? 0);
                                $rejected = (float) ($get('rejected_quantity') ?? 0);
                                // Auto-set accepted to received if starting fresh
                                if ($accepted === 0.0 && $rejected === 0.0 && $received > 0) {
                                    $set('accepted_quantity', $received);
                                }
                            }),

                        TextInput::make('accepted_quantity')
                            ->label('Accepted Qty')
                            ->helperText('Must ≤ Received')
                            ->numeric()->minValue(0)->default(0)->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) {
                                $received = (float) ($get('received_quantity') ?? 0);
                                $accepted = (float) $state;
                                // Auto-compute rejected
                                $set('rejected_quantity', max(0, round($received - $accepted, 4)));
                            }),

                        TextInput::make('rejected_quantity')
                            ->label('Rejected Qty')
                            ->helperText('Accepted + Rejected = Received')
                            ->numeric()->minValue(0)->default(0)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) {
                                $received = (float) ($get('received_quantity') ?? 0);
                                $rejected = (float) $state;
                                // Auto-compute accepted
                                $set('accepted_quantity', max(0, round($received - $rejected, 4)));
                            }),

                        Select::make('condition')
                            ->label('Condition')
                            ->options(['Pass' => 'Pass', 'Partial' => 'Partial', 'Fail' => 'Fail'])
                            ->default('Pass'),

                        // Row 3: Remarks (full width)
                        Textarea::make('inspection_remarks')
                            ->label('Inspection Remarks')
                            ->helperText('Describe defects, damage, or quality observations for this line')
                            ->rows(2)->nullable()->columnSpanFull(),
                    ])
                    ->columns(4)
                    ->addActionLabel('+ Add Item')
                    ->defaultItems(1)
                    ->collapsible()
                    ->cloneable()
                    ->itemLabel(fn (array $state) =>
                        'Item #' . ($state['po_item_id'] ?? '?') .
                        (isset($state['received_quantity']) ? '  ·  Received: ' . (float)$state['received_quantity'] : '') .
                        (isset($state['condition']) ? '  ·  ' . str_replace(['✅', '⚠️', '❌'], '', $state['condition']) : '')
                    ),
            ]),

            Section::make('Approval Trail')
                ->description('Live approval trail — configured under Procurement → Settings → Approval Workflows.')
                ->collapsible()
                ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('goods_receipt'))
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('_approval_trail')
                        ->label('')
                        ->columnSpanFull()
                        ->content(fn (?GoodsReceipt $record) =>
                            \App\Services\Procurement\ProcurementApprovalService::renderApprovalTrailHtml($record, 'goods_receipt')
                        ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grn_number')->label('GRN #')->searchable()->sortable()->weight('semibold')->copyable(),
                TextColumn::make('purchaseOrder.po_number')->label('PO #')->searchable()->sortable(),
                TextColumn::make('purchaseOrder.supplier.name')->label('Supplier')->searchable(),
                TextColumn::make('receipt_date')->date()->sortable(),
                TextColumn::make('overall_condition')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Good'     => 'success', 'Partial' => 'warning', 'Rejected' => 'danger', default => 'gray',
                    }),
                TextColumn::make('approval_stage')
                    ->label('Approval')
                    ->badge()
                    ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('goods_receipt'))
                    ->getStateUsing(fn (GoodsReceipt $r) => \App\Services\Procurement\ProcurementApprovalService::currentStageLabel($r, 'goods_receipt'))
                    ->color(fn ($state) => match (true) {
                        str_contains($state, 'Fully Approved') => 'success',
                        str_contains($state, 'Rejected')       => 'danger',
                        str_contains($state, 'Awaiting')       => 'warning',
                        $state === 'Not Started'               => 'gray',
                        default                                => 'info',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Accepted' => 'success', 'Partial' => 'warning', 'Approved' => 'success',
                        'Rejected' => 'danger', 'Inspecting' => 'info', 'Pending Approval' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('receipt_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(['Draft' => 'Draft', 'Inspecting' => 'Inspecting', 'Accepted' => 'Accepted', 'Partial' => 'Partial', 'Rejected' => 'Rejected']),
            ])
            ->recordActions([
                Action::make('inspect')
                    ->label('Start Inspection')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->visible(fn (GoodsReceipt $r) => $r->canInspect() && auth()->user()->isProcurementStore())
                    ->requiresConfirmation()
                    ->action(fn (GoodsReceipt $r) =>
                        $r->update(['status' => 'Inspecting', 'inspected_by' => auth()->id(), 'inspected_at' => now()])
                        && Notification::make()->title('Inspection started — fill in quantities and conditions per item')->info()->send()
                    ),

                Action::make('submit')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (GoodsReceipt $r) => $r->status === 'Inspecting' && \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('goods_receipt'))
                    ->requiresConfirmation()
                    ->action(function (GoodsReceipt $r) {
                        $r->update(['status' => 'Pending Approval']);
                        \App\Services\Procurement\ProcurementApprovalService::initialise($r, 'goods_receipt');
                        Notification::make()->title('GRN submitted — approval workflow started')->info()->send();
                    }),

                Action::make('accept')
                    ->label('Accept Delivery')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (GoodsReceipt $r) => $r->status === 'Inspecting' && auth()->user()->isProcurementStore() && ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('goods_receipt'))
                    ->requiresConfirmation()
                    ->modalHeading('Accept Goods Delivery')
                    ->modalDescription('Confirm that goods have been inspected and accepted.')
                    ->action(fn (GoodsReceipt $r) =>
                        $r->update(['status' => 'Accepted', 'approved_by' => auth()->id(), 'approved_at' => now()])
                        && Notification::make()->title('Goods accepted — ready for 3-way matching')->success()->send()
                    ),

                Action::make('partial_accept')
                    ->label('Partial Accept')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (GoodsReceipt $r) => $r->status === 'Inspecting' && auth()->user()->isProcurementStore() && ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('goods_receipt'))
                    ->requiresConfirmation()
                    ->action(fn (GoodsReceipt $r) =>
                        $r->update(['status' => 'Partial', 'approved_by' => auth()->id(), 'approved_at' => now()])
                        && Notification::make()->title('Partial delivery accepted — backorder noted')->warning()->send()
                    ),

                Action::make('workflow_approve')
                    ->label(fn (GoodsReceipt $r) =>
                        '✓ Approve — ' .
                        (\App\Services\Procurement\ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'goods_receipt')?->stage_name ?? 'Approve')
                    )
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (GoodsReceipt $r) =>
                        $r->status === 'Pending Approval'
                        && \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('goods_receipt')
                        && \App\Services\Procurement\ProcurementApprovalService::canApprove(auth()->user(), $r, 'goods_receipt')
                    )
                    ->requiresConfirmation()
                    ->form([Textarea::make('notes')->label('Approval Remarks (optional)')->rows(3)->nullable()])
                    ->action(function (GoodsReceipt $r, array $data) {
                        $user    = auth()->user();
                        $pending = \App\Services\Procurement\ProcurementApprovalService::pendingRecordFor($user, $r, 'goods_receipt');
                        if (! $pending) return;

                        \App\Services\Procurement\ProcurementApprovalService::approve($r, 'goods_receipt', $pending->stage_order, $user, $data['notes'] ?? null);

                        if (\App\Services\Procurement\ProcurementApprovalService::isFullyApproved($r, 'goods_receipt')) {
                            $isPartial = $r->items->contains(fn ($item) => $item->accepted_quantity < $item->received_quantity);
                            $r->update([
                                'status'      => $isPartial ? 'Partial' : 'Accepted',
                                'approved_by' => $user->id,
                                'approved_at' => now(),
                            ]);
                            Notification::make()->title('✓ GRN fully approved — ready for invoice matching!')->success()->send();
                        } else {
                            Notification::make()->title("✓ Approved: {$pending->stage_name} — advancing to next stage")->success()->send();
                        }
                    }),

                // ── Return to Vendor (RTV) ────────────────────────────────────────────
                Action::make('create_return')
                    ->label('Return to Supplier (RTV)')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (GoodsReceipt $r) =>
                        in_array($r->status, ['Inspecting', 'Pending Approval', 'Accepted', 'Partial', 'Rejected'])
                        && $r->items()->where('rejected_quantity', '>', 0)->exists()
                        && ! SupplierReturn::where('goods_receipt_id', $r->id)->where('status', '!=', 'Cancelled')->exists()
                    )
                    ->modalHeading('Create Return to Supplier (RTV)')
                    ->modalDescription('A formal return document will be created for all rejected items.')
                    ->form([
                        Select::make('reason')
                            ->label('Primary Return Reason')
                            ->options([
                                'Quality Defect'            => 'Quality Defect',
                                'Wrong Item Delivered'      => 'Wrong Item Delivered',
                                'Quantity Shortage'         => 'Quantity Shortage',
                                'Damaged on Arrival'        => 'Damaged on Arrival',
                                'Expired / Past Shelf Life' => 'Expired / Past Shelf Life',
                                'Other'                     => 'Other',
                            ])
                            ->required()->default('Quality Defect'),
                        DatePicker::make('return_date')
                            ->label('Return Date')
                            ->default(now()->toDateString())->required(),
                        DatePicker::make('expected_resolution_date')
                            ->label('Expected Resolution Date')->nullable(),
                        Textarea::make('return_notes')
                            ->label('Return Notes')
                            ->helperText('Will be communicated to the supplier')
                            ->rows(3)->nullable(),
                    ])
                    ->action(function (GoodsReceipt $r, array $data) {
                        $rtv = SupplierReturn::create([
                            'goods_receipt_id'         => $r->id,
                            'purchase_order_id'        => $r->purchase_order_id,
                            'supplier_id'              => $r->purchaseOrder->supplier_id,
                            'return_date'              => $data['return_date'],
                            'reason'                   => $data['reason'],
                            'return_notes'             => $data['return_notes'] ?? null,
                            'expected_resolution_date' => $data['expected_resolution_date'] ?? null,
                            'status'                   => 'Draft',
                        ]);

                        foreach ($r->items()->where('rejected_quantity', '>', 0)->with('poItem')->get() as $item) {
                            $rtv->items()->create([
                                'grn_item_id'       => $item->id,
                                'description'       => $item->poItem->description ?? 'Item',
                                'quantity_returned'  => $item->rejected_quantity,
                                'unit'              => $item->poItem->unit ?? null,
                                'reason'            => $data['reason'],
                                'notes'             => $item->inspection_remarks,
                            ]);
                        }

                        Notification::make()
                            ->title("Return #{$rtv->return_number} created — {$rtv->items()->count()} rejected item(s) queued for return")
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject Delivery')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (GoodsReceipt $r) {
                        if (in_array($r->status, ['Accepted', 'Partial', 'Rejected'])) return false;
                        if (! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('goods_receipt')) {
                            return $r->status === 'Inspecting' && auth()->user()->isProcurementStore();
                        }
                        if ($r->status === 'Pending Approval') {
                            $pending = \App\Services\Procurement\ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'goods_receipt');
                            return $pending && ($pending->stage?->can_reject ?? true);
                        }
                        return false;
                    })
                    ->requiresConfirmation()
                    ->form([Textarea::make('notes')->label('Rejection Reason')->required()->rows(3)])
                    ->action(function (GoodsReceipt $r, array $data) {
                        $user = auth()->user();
                        if (\App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('goods_receipt') && $r->status === 'Pending Approval') {
                            $pending = \App\Services\Procurement\ProcurementApprovalService::pendingRecordFor($user, $r, 'goods_receipt');
                            if ($pending) {
                                \App\Services\Procurement\ProcurementApprovalService::reject($r, 'goods_receipt', $pending->stage_order, $user, $data['notes'] ?? null);
                            }
                        }
                        $r->update(['status' => 'Rejected']);
                        Notification::make()->title('Delivery rejected')->danger()->send();
                    }),

                EditAction::make(),
                DeleteAction::make()->visible(fn (GoodsReceipt $r) => $r->status === 'Draft'),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListGoodsReceipts::route('/'),
            'create' => CreateGoodsReceipt::route('/create'),
            'edit'   => EditGoodsReceipt::route('/{record}/edit'),
        ];
    }
}
