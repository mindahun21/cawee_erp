<?php

namespace App\Filament\Resources\Procurement\Payments;

use App\Filament\Resources\Procurement\Payments\Pages\CreatePayment;
use App\Filament\Resources\Procurement\Payments\Pages\EditPayment;
use App\Filament\Resources\Procurement\Payments\Pages\ListPayments;
use App\Models\Currency;
use App\Models\Procurement\Invoice;
use App\Models\Procurement\Payment;
use App\Services\Procurement\ProcurementApprovalService;
use BackedEnum;
use Filament\Schemas\Components\Utilities\Get;
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

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';
    protected static ?string $navigationLabel = 'Payment Scheduling';
    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment Details')->columns(2)->schema([
                TextInput::make('payment_reference')->label('Reference #')->disabled()->dehydrated()->placeholder('Auto-generated'),

                Select::make('invoice_id')
                    ->label('Invoice')
                    ->relationship('invoice', 'invoice_number',
                        fn ($query) => $query->whereIn('status', [Invoice::STATUS_APPROVED])
                    )
                    ->searchable()->preload()->required()
                    ->helperText('Only fully approved invoices are eligible for payment.'),

                Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()->preload()->required(),

                TextInput::make('amount')->numeric()->prefix(fn (Get $get) => Currency::symbolFor($get('currency')))->required(),
                Select::make('currency')
                    ->label('Currency')
                    ->options(fn () => Currency::procurementOptions())
                    ->default(fn () => Currency::procurementDefault())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),

                Select::make('payment_method')
                    ->options([
                        'Bank Transfer' => 'Bank Transfer',
                        'Cheque'        => 'Cheque',
                        'Cash'          => 'Cash',
                        'Other'         => 'Other',
                    ])
                    ->default('Bank Transfer')->required(),

                TextInput::make('bank_name')->maxLength(100)->nullable(),
                TextInput::make('bank_reference')->maxLength(150)->nullable()->label('Bank Reference / Cheque #'),

                DatePicker::make('scheduled_date')->label('Scheduled Payment Date')->nullable(),
                DatePicker::make('payment_date')->label('Actual Payment Date')->nullable(),

                Textarea::make('notes')->rows(2)->columnSpanFull()->nullable(),

                FileUpload::make('attachments')
                    ->label('Payment Vouchers / Receipts')
                    ->multiple()->disk('local')->directory('procurement/payments')
                    ->nullable()->columnSpanFull(),
            ]),

            Section::make('Authorization Trail')
                ->description('Live authorization trail — updates instantly when approvers act on the list view.')
                ->collapsible()
                ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('payment'))
                ->schema([
                    Placeholder::make('_approval_trail')
                        ->label('')
                        ->columnSpanFull()
                        ->content(fn (?Payment $record) =>
                            ProcurementApprovalService::renderApprovalTrailHtml($record, 'payment')
                        ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_reference')->label('Ref #')->searchable()->sortable()->weight('semibold')->copyable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable()->sortable(),
                TextColumn::make('invoice.invoice_number')->label('Invoice #')->searchable(),
                TextColumn::make('amount')->label('Amount')->numeric(2)->sortable()
                    ->formatStateUsing(fn ($state, Payment $record) => ($record->currency ?? 'ETB') . ' ' . number_format((float)$state, 2)),
                TextColumn::make('payment_method')->badge()->color('gray'),
                TextColumn::make('scheduled_date')->label('Scheduled')->date()->sortable(),
                TextColumn::make('payment_date')->label('Paid On')->date()->sortable()->toggleable(),
                TextColumn::make('approval_stage')
                    ->label('Authorization')
                    ->badge()
                    ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('payment'))
                    ->getStateUsing(fn (Payment $r) => ProcurementApprovalService::currentStageLabel($r, 'payment'))
                    ->color(fn ($state) => match (true) {
                        str_contains($state, 'Fully Approved') => 'success',
                        str_contains($state, 'Rejected')       => 'danger',
                        str_contains($state, 'Awaiting')       => 'warning',
                        $state === 'Not Started'               => 'gray',
                        default                                => 'info',
                    }),
                TextColumn::make('current_stage')->label('Stage')->badge()
                    ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('payment'))
                    ->color(fn ($state) => match (true) {
                        str_contains($state, 'Processed ✓') || str_contains($state, 'Ready') => 'success',
                        str_contains($state, 'Cancelled')   => 'danger',
                        str_contains($state, 'Awaiting')    => 'warning',
                        default                              => 'info',
                    }),
                TextColumn::make('status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'Processed' => 'success', 'Approved' => 'success',
                        'Failed'    => 'danger', 'Cancelled' => 'danger',
                        'Pending Approval' => 'warning',
                        default     => 'gray',
                    }),
            ])
            ->defaultSort('scheduled_date', 'asc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Scheduled' => 'Scheduled', 'Pending Approval' => 'Pending Approval',
                        'Approved' => 'Approved', 'Processed' => 'Processed',
                        'Failed' => 'Failed', 'Cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('payment_method')
                    ->options(['Bank Transfer' => 'Bank Transfer', 'Cheque' => 'Cheque', 'Cash' => 'Cash', 'Other' => 'Other']),
            ])
            ->recordActions([
                // Submit for approval
                Action::make('submit')
                    ->label(fn () => \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('payment') ? 'Submit for Authorization' : 'Authorize Payment')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Payment $r) => $r->status === 'Scheduled' && auth()->user()->isProcurementFinance())
                    ->requiresConfirmation()
                    ->action(function (Payment $r) {
                        if (! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('payment')) {
                            $r->update(['status' => 'Approved']);
                            Notification::make()->title('Payment authorized directly (no workflow active)')->success()->send();
                        } else {
                            $r->update(['status' => 'Pending Approval']);
                            ProcurementApprovalService::initialise($r, 'payment');
                            Notification::make()->title('Payment submitted — authorization workflow started')->info()->send();
                        }
                    }),

                // ── Dynamic Approval via workflow config ──────────────────────────────────
                Action::make('approve')
                    ->label(fn (Payment $r) =>
                        '✓ Authorize — ' .
                        (ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'payment')?->stage_name ?? 'Authorize')
                    )
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Payment $r) =>
                        !in_array($r->status, ['Processed', 'Cancelled', 'Approved'])
                        && \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('payment')
                        && ProcurementApprovalService::canApprove(auth()->user(), $r, 'payment')
                    )
                    ->requiresConfirmation()
                    ->form([Textarea::make('notes')->label('Remarks (optional)')->rows(3)->nullable()])
                    ->action(function (Payment $r, array $data) {
                        $user    = auth()->user();
                        $pending = ProcurementApprovalService::pendingRecordFor($user, $r, 'payment');
                        if (! $pending) return;

                        ProcurementApprovalService::approve($r, 'payment', $pending->stage_order, $user, $data['notes'] ?? null);

                        if (ProcurementApprovalService::isFullyApproved($r, 'payment')) {
                            $r->update(['status' => 'Approved']);
                            Notification::make()->title('✓ Payment fully authorized — ready for bank processing')->success()->send();
                        } else {
                            Notification::make()->title("✓ Authorized: {$pending->stage_name} — advancing to next stage")->success()->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (Payment $r) {
                        if (in_array($r->status, ['Processed', 'Cancelled'])) return false;
                        if (! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('payment')) return false;
                        $pending = ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'payment');
                        return $pending && ($pending->stage?->can_reject ?? true);
                    })
                    ->requiresConfirmation()
                    ->form([Textarea::make('notes')->label('Rejection Reason')->required()->rows(3)])
                    ->action(function (Payment $r, array $data) {
                        $user    = auth()->user();
                        $pending = ProcurementApprovalService::pendingRecordFor($user, $r, 'payment');
                        if (! $pending) return;

                        ProcurementApprovalService::reject($r, 'payment', $pending->stage_order, $user, $data['notes'] ?? null);
                        $r->update(['status' => 'Cancelled']);
                        Notification::make()->title("Payment rejected at {$pending->stage_name}")->danger()->send();
                    }),

                // Process (mark as paid)
                Action::make('process')
                    ->label('Mark Processed')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Payment $r) => $r->status === 'Approved' && auth()->user()->isProcurementFinance())
                    ->form([
                        DatePicker::make('payment_date')->label('Actual Payment Date')->required()->default(now()->toDateString()),
                        TextInput::make('bank_reference')->label('Bank Reference / Transaction ID')->nullable(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Record Payment Processing')
                    ->modalDescription('Enter the actual date and bank reference number to confirm the payment was processed.')
                    ->action(function (Payment $r, array $data) {
                        $r->update([
                            'status'         => 'Processed',
                            'payment_date'   => $data['payment_date'],
                            'bank_reference' => $data['bank_reference'] ?? $r->bank_reference,
                        ]);
                        // Mark the invoice as paid
                        $r->invoice?->update(['status' => Invoice::STATUS_PAID]);
                        Notification::make()->title('  Payment processed — invoice marked Paid')->success()->send();
                    }),

                // Cancel
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Payment $r) =>
                        ! in_array($r->status, ['Processed', 'Cancelled'])
                        && auth()->user()->isProcurementDirector()
                    )
                    ->requiresConfirmation()
                    ->action(fn (Payment $r) =>
                        $r->update(['status' => 'Cancelled'])
                        && Notification::make()->title('Payment cancelled')->danger()->send()
                    ),

                EditAction::make(),
                DeleteAction::make()->visible(fn (Payment $r) => $r->status === 'Scheduled'),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'edit'   => EditPayment::route('/{record}/edit'),
        ];
    }
}
