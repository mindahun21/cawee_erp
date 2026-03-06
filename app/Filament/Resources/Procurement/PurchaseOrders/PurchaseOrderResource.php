<?php

namespace App\Filament\Resources\Procurement\PurchaseOrders;

use App\Filament\Resources\Procurement\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Resources\Procurement\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Resources\Procurement\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Models\Procurement\PurchaseOrder;
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
                    ->searchable()->preload()->nullable(),

                Select::make('tender_id')
                    ->label('Linked Tender (optional)')
                    ->relationship('tender', 'tender_number')
                    ->searchable()->preload()->nullable(),

                DatePicker::make('order_date')->required()->default(now()->toDateString()),
                DatePicker::make('delivery_date')->required(),
                DatePicker::make('supplier_acknowledged_at')->label('Supplier Acknowledged')->nullable(),

                TextInput::make('delivery_location')->maxLength(200)->nullable(),
                TextInput::make('payment_terms')->maxLength(100)->nullable()->placeholder('e.g., Net 30'),
                TextInput::make('currency')->default('ETB')->maxLength(10),
                TextInput::make('tax_rate')->numeric()->suffix('%')->default(15)->label('VAT Rate (%)'),

                Textarea::make('notes')->rows(2)->columnSpanFull()->nullable(),

                FileUpload::make('attachments')
                    ->multiple()->disk('local')->directory('procurement/pos')
                    ->nullable()->columnSpanFull(),
            ]),

            // ── Line Items ──────────────────────────────────────────
            Section::make('Order Items')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        TextInput::make('description')->required()->maxLength(300)->columnSpan(3),
                        TextInput::make('unit')->maxLength(50)->placeholder('pcs, kg, hr…'),
                        TextInput::make('quantity')->numeric()->minValue(0.0001)->default(1)->required(),
                        TextInput::make('unit_price')->label('Unit Price (ETB)')->numeric()->prefix('ETB')->default(0),
                        TextInput::make('specifications')->maxLength(500)->columnSpan(3)->nullable(),
                    ])
                    ->columns(6)
                    ->addActionLabel('+ Add Line Item')
                    ->defaultItems(1)
                    ->collapsible()
                    ->itemLabel(fn (array $state) => $state['description'] ?? 'New Line Item'),
            ]),

            // ── Approval Trail ──────────────────────────────────────
            Section::make('Approval Trail')
                ->description('Approvals are triggered via the ⚡ action buttons on the list view.')
                ->columns(3)
                ->schema([
                    Select::make('procurement_officer_status')
                        ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'])
                        ->default('Pending')->disabled()->dehydrated()->label('Procurement Officer'),

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
                TextColumn::make('po_number')
                    ->label('PO #')
                    ->searchable()->sortable()->weight('semibold')->copyable()->copyMessage('Copied!'),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()->sortable(),

                TextColumn::make('order_date')->date()->sortable(),
                TextColumn::make('delivery_date')->date()->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total (ETB)')
                    ->numeric(2)->prefix('ETB ')->sortable(),

                // 3-stage approval badges
                TextColumn::make('procurement_officer_status')
                    ->label('Proc. Officer')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved' => 'success', 'Rejected' => 'danger', default => 'warning',
                    }),

                TextColumn::make('finance_status')
                    ->label('Finance')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved' => 'success', 'Rejected' => 'danger', default => 'warning',
                    }),

                TextColumn::make('director_status')
                    ->label('Director')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved' => 'success', 'Rejected' => 'danger', default => 'warning',
                    }),

                TextColumn::make('current_stage')
                    ->label('Stage')
                    ->badge()
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
                SelectFilter::make('director_status')
                    ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected']),
            ])
            ->recordActions([
                // Submit for approval
                Action::make('submit')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (PurchaseOrder $r) =>
                        $r->overall_status === PurchaseOrder::STATUS_DRAFT
                        && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->action(fn (PurchaseOrder $r) =>
                        $r->update(['overall_status' => PurchaseOrder::STATUS_PENDING])
                        && Notification::make()->title('PO submitted — awaiting Procurement Officer review')->info()->send()
                    ),

                // Stage 1: Procurement Officer
                Action::make('officer_approve')
                    ->label('Approve (Proc. Officer)')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PurchaseOrder $r) =>
                        $r->canProcurementOfficerApprove() && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Procurement Officer Approval')
                    ->modalDescription('Confirm PO review is complete. It will advance to Finance for budget sign-off.')
                    ->action(function (PurchaseOrder $r) {
                        $r->update([
                            'procurement_officer_status'      => 'Approved',
                            'procurement_officer_approved_by' => auth()->id(),
                            'procurement_officer_approved_at' => now(),
                        ]);
                        Notification::make()->title('✓ Proc. Officer approved — forwarded to Finance')->success()->send();
                    }),

                // Stage 2: Finance
                Action::make('finance_approve')
                    ->label('Approve (Finance)')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->visible(fn (PurchaseOrder $r) =>
                        $r->canFinanceApprove() && auth()->user()->isProcurementFinance()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Finance Approval')
                    ->modalDescription('Confirm budget availability and financial sign-off. PO advances to the Director for final authorization.')
                    ->action(function (PurchaseOrder $r) {
                        $r->update([
                            'finance_status'      => 'Approved',
                            'finance_approved_by' => auth()->id(),
                            'finance_approved_at' => now(),
                        ]);
                        Notification::make()->title('✓ Finance approved — forwarded to Director')->success()->send();
                    }),

                // Stage 3: Director (Final)
                Action::make('director_approve')
                    ->label('Authorize (Director)')
                    ->icon('heroicon-o-shield-check')
                    ->color('primary')
                    ->visible(fn (PurchaseOrder $r) =>
                        $r->canDirectorApprove() && auth()->user()->isProcurementDirector()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Director Authorization')
                    ->modalDescription('Final authorization. Once signed, the PO will be marked Approved and can be sent to the supplier.')
                    ->modalSubmitActionLabel('Authorize')
                    ->action(function (PurchaseOrder $r) {
                        $r->update([
                            'director_status'      => 'Approved',
                            'director_approved_by' => auth()->id(),
                            'director_approved_at' => now(),
                            'overall_status'       => PurchaseOrder::STATUS_APPROVED,
                        ]);
                        Notification::make()->title('  PO fully authorized — ready to send to supplier')->success()->send();
                    }),

                // Send to Supplier
                Action::make('send_to_supplier')
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
}
