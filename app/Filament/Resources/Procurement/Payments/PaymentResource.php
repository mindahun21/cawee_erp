<?php

namespace App\Filament\Resources\Procurement\Payments;

use App\Filament\Resources\Procurement\Payments\Pages\CreatePayment;
use App\Filament\Resources\Procurement\Payments\Pages\EditPayment;
use App\Filament\Resources\Procurement\Payments\Pages\ListPayments;
use App\Models\Procurement\Invoice;
use App\Models\Procurement\Payment;
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

                TextInput::make('amount')->numeric()->prefix('ETB')->required(),
                TextInput::make('currency')->default('ETB')->maxLength(10),

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
                ->description('Authorizations are performed via action buttons on the list view.')
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
                TextColumn::make('payment_reference')->label('Ref #')->searchable()->sortable()->weight('semibold')->copyable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable()->sortable(),
                TextColumn::make('invoice.invoice_number')->label('Invoice #')->searchable(),
                TextColumn::make('amount')->label('Amount (ETB)')->numeric(2)->prefix('ETB ')->sortable(),
                TextColumn::make('payment_method')->badge()->color('gray'),
                TextColumn::make('scheduled_date')->label('Scheduled')->date()->sortable(),
                TextColumn::make('payment_date')->label('Paid On')->date()->sortable()->toggleable(),
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
                    ->label('Submit for Authorization')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Payment $r) => $r->status === 'Scheduled' && auth()->user()->isProcurementFinance())
                    ->requiresConfirmation()
                    ->action(fn (Payment $r) =>
                        $r->update(['status' => 'Pending Approval'])
                        && Notification::make()->title('Payment submitted for authorization')->info()->send()
                    ),

                // Finance approve
                Action::make('finance_approve')
                    ->label('Authorize (Finance)')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Payment $r) => $r->canFinanceApprove() && auth()->user()->isProcurementFinance())
                    ->requiresConfirmation()
                    ->modalHeading('Finance Payment Authorization')
                    ->modalDescription('Authorize the release of funds. Payment advances to Director for final sign-off.')
                    ->action(function (Payment $r) {
                        $r->update([
                            'finance_status'      => 'Approved',
                            'finance_approved_by' => auth()->id(),
                            'finance_approved_at' => now(),
                        ]);
                        Notification::make()->title('✓ Finance authorized — forwarded to Director')->success()->send();
                    }),

                // Director authorize (Final)
                Action::make('director_approve')
                    ->label('Authorize (Director)')
                    ->icon('heroicon-o-shield-check')
                    ->color('primary')
                    ->visible(fn (Payment $r) => $r->canDirectorApprove() && auth()->user()->isProcurementDirector())
                    ->requiresConfirmation()
                    ->modalHeading('Director Payment Authorization')
                    ->modalDescription('Final authorization to release payment. This will mark the payment ready for bank processing.')
                    ->modalSubmitActionLabel('Authorize')
                    ->action(function (Payment $r) {
                        $r->update([
                            'director_status'      => 'Approved',
                            'director_approved_by' => auth()->id(),
                            'director_approved_at' => now(),
                            'status'               => 'Approved',
                        ]);
                        Notification::make()->title('  Payment fully authorized — ready for bank processing')->success()->send();
                    }),

                // Process (mark as paid)
                Action::make('process')
                    ->label('Mark Processed')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Payment $r) => $r->isFullyApproved() && $r->status === 'Approved' && auth()->user()->isProcurementFinance())
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
