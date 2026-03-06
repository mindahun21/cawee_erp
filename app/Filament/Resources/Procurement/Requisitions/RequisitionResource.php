<?php

namespace App\Filament\Resources\Procurement\Requisitions;

use App\Filament\Resources\Procurement\Requisitions\Pages\CreateRequisition;
use App\Filament\Resources\Procurement\Requisitions\Pages\EditRequisition;
use App\Filament\Resources\Procurement\Requisitions\Pages\ListRequisitions;
use App\Models\Procurement\ProcurementBudget;
use App\Models\Procurement\Requisition;
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

class RequisitionResource extends Resource
{
    protected static ?string $model = Requisition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Purchase Requisitions';

    protected static ?int $navigationSort = 1;

    // ── Form ──────────────────────────────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Requisition Details')->columns(2)->schema([
                TextInput::make('requisition_number')
                    ->label('Requisition No.')
                    ->disabled()
                    ->dehydrated()
                    ->placeholder('Auto-generated on save'),

                Select::make('category')
                    ->options([
                        'Goods'       => 'Goods',
                        'Services'    => 'Services',
                        'Works'       => 'Works',
                        'Consultancy' => 'Consultancy',
                    ])
                    ->required(),

                Select::make('budget_id')
                    ->label('Budget Line')
                    ->relationship('budget', 'code')
                    ->getOptionLabelFromRecordUsing(fn (ProcurementBudget $r) =>
                        "{$r->code} — {$r->title}"
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Select the applicable budget line for this requisition.'),

                TextInput::make('budget_code')
                    ->label('Budget Code (manual)')
                    ->maxLength(50)
                    ->nullable(),

                TextInput::make('department')->maxLength(150)->nullable(),
                TextInput::make('cost_center')->maxLength(100)->nullable(),

                Select::make('procurement_method')
                    ->label('Procurement Method')
                    ->options([
                        'Open Tender'       => 'Open Tender',
                        'Restricted Tender' => 'Restricted Tender',
                        'Two-Stage Tender'  => 'Two-Stage Tender',
                        'RFP'               => 'Request for Proposal (RFP)',
                        'RFQ'               => 'Request for Quotation (RFQ)',
                        'Direct Procurement'=> 'Direct Procurement',
                    ])
                    ->nullable(),

                DatePicker::make('required_by_date')->required(),
                TextInput::make('delivery_location')->maxLength(200)->nullable(),

                Textarea::make('justification')->rows(3)->columnSpanFull(),

                FileUpload::make('attachments')
                    ->label('Attachments (specs, ToR, quotes)')
                    ->multiple()
                    ->disk('local')
                    ->directory('procurement/requisitions')
                    ->nullable()
                    ->columnSpanFull(),
            ]),

            // ── Line Items ───────────────────────────────────────────
            Section::make('Items / Services Requested')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        TextInput::make('description')->required()->maxLength(300)->columnSpan(3),
                        TextInput::make('unit')->maxLength(50)->placeholder('pcs, kg, hr…'),
                        TextInput::make('quantity')->numeric()->minValue(0.0001)->default(1)->required(),
                        TextInput::make('estimated_unit_price')
                            ->label('Unit Price (ETB)')
                            ->numeric()
                            ->prefix('ETB')
                            ->default(0),
                        TextInput::make('specifications')->maxLength(500)->columnSpan(3)->nullable(),
                    ])
                    ->columns(6)
                    ->addActionLabel('+ Add Item')
                    ->defaultItems(1)
                    ->collapsible()
                    ->itemLabel(fn (array $state) => $state['description'] ?? 'New Item'),
            ]),

            // ── Approval Trail ─────────────────────────────────────────
            Section::make('Approval Trail')
                ->description('Approvals are performed via the ⚡ action buttons on the list view — not editable here.')
                ->columns(4)
                ->schema([
                    Select::make('supervisor_status')
                        ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'])
                        ->default('Pending')->disabled()->dehydrated()->label('Supervisor'),

                    Select::make('dept_head_status')
                        ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'])
                        ->default('Pending')->disabled()->dehydrated()->label('Dept Head'),

                    Select::make('finance_status')
                        ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'])
                        ->default('Pending')->disabled()->dehydrated()->label('Finance'),

                    Select::make('procurement_status')
                        ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'])
                        ->default('Pending')->disabled()->dehydrated()->label('Procurement'),
                ])
                ->collapsible(),
        ]);
    }

    // ── Table ──────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('requisition_number')
                    ->label('REQ #')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->copyable()
                    ->copyMessage('Copied!'),

                TextColumn::make('requester.name')
                    ->label('Requested By')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Goods'       => 'info',
                        'Services'    => 'primary',
                        'Works'       => 'warning',
                        'Consultancy' => 'purple',
                        default       => 'gray',
                    }),

                TextColumn::make('procurement_method')
                    ->label('Method')
                    ->toggleable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('estimated_total')
                    ->label('Est. Total (ETB)')
                    ->numeric(2)
                    ->prefix('ETB ')
                    ->sortable(),

                TextColumn::make('required_by_date')
                    ->label('Required By')
                    ->date()
                    ->sortable(),

                // ── 4-stage approval badges ─────────────────────────
                TextColumn::make('supervisor_status')
                    ->label('Supervisor')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved' => 'success', 'Rejected' => 'danger', default => 'warning',
                    }),

                TextColumn::make('dept_head_status')
                    ->label('Dept Head')
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

                TextColumn::make('procurement_status')
                    ->label('Procurement')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved' => 'success', 'Rejected' => 'danger', default => 'warning',
                    }),

                TextColumn::make('current_stage')
                    ->label('Stage')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        str_contains($state, 'Approved') || str_contains($state, 'Fully')  => 'success',
                        str_contains($state, 'Rejected')   => 'danger',
                        str_contains($state, 'Awaiting')   => 'warning',
                        $state === 'Draft'                  => 'gray',
                        default                             => 'info',
                    }),

                TextColumn::make('overall_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved', 'Converted to PO' => 'success',
                        'Rejected'                     => 'danger',
                        'Submitted'                    => 'warning',
                        default                        => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('overall_status')
                    ->label('Status')
                    ->options([
                        'Draft'          => 'Draft',
                        'Submitted'      => 'Submitted',
                        'Approved'       => 'Approved',
                        'Rejected'       => 'Rejected',
                        'Converted to PO' => 'Converted to PO',
                    ]),

                SelectFilter::make('category')
                    ->options([
                        'Goods' => 'Goods', 'Services' => 'Services',
                        'Works' => 'Works', 'Consultancy' => 'Consultancy',
                    ]),

                SelectFilter::make('supervisor_status')
                    ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected']),

                SelectFilter::make('finance_status')
                    ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected']),
            ])
            ->recordActions([
                // ── Submit (Draft → Submitted) ──────────────────────
                Action::make('submit')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Requisition $r) =>
                        $r->overall_status === Requisition::STATUS_DRAFT
                        && auth()->user()->isProcurementRequester()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Submit Requisition')
                    ->modalDescription('This will submit the requisition for the approval chain. Ensure all items and details are complete before proceeding.')
                    ->modalSubmitActionLabel('Submit')
                    ->action(fn (Requisition $r) =>
                        $r->update(['overall_status' => Requisition::STATUS_SUBMITTED])
                        && Notification::make()->title('Requisition submitted — forwarded to Supervisor')->info()->send()
                    ),

                // ── Stage 1: Supervisor Approve ─────────────────────
                Action::make('supervisor_approve')
                    ->label('Approve (Supervisor)')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Requisition $r) =>
                        $r->canSupervisorApprove() && auth()->user()->isProcurementSupervisor()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Supervisor Approval')
                    ->modalDescription('Confirm approval at the Supervisor level. The requisition will advance to the Department Head.')
                    ->form([
                        Textarea::make('supervisor_remarks')->label('Remarks (optional)')->rows(3)->nullable(),
                    ])
                    ->action(function (Requisition $r, array $data) {
                        $r->update([
                            'supervisor_status'      => 'Approved',
                            'supervisor_approved_by' => auth()->id(),
                            'supervisor_approved_at' => now(),
                            'supervisor_remarks'     => $data['supervisor_remarks'] ?? null,
                        ]);
                        Notification::make()->title('✓ Supervisor approved — forwarded to Dept Head')->success()->send();
                    }),

                // ── Stage 2: Department Head Approve ────────────────
                Action::make('dept_head_approve')
                    ->label('Approve (Dept Head)')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->visible(fn (Requisition $r) =>
                        $r->canDeptHeadApprove() && auth()->user()->isProcurementDeptHead()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Department Head Approval')
                    ->modalDescription('Confirm departmental approval. The requisition will advance to Finance for budget confirmation.')
                    ->form([
                        Textarea::make('dept_head_remarks')->label('Remarks (optional)')->rows(3)->nullable(),
                    ])
                    ->action(function (Requisition $r, array $data) {
                        $r->update([
                            'dept_head_status'      => 'Approved',
                            'dept_head_approved_by' => auth()->id(),
                            'dept_head_approved_at' => now(),
                            'dept_head_remarks'     => $data['dept_head_remarks'] ?? null,
                        ]);
                        Notification::make()->title('✓ Dept Head approved — forwarded to Finance')->success()->send();
                    }),

                // ── Stage 3: Finance Approve ─────────────────────────
                Action::make('finance_approve')
                    ->label('Approve (Finance)')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->visible(fn (Requisition $r) =>
                        $r->canFinanceApprove() && auth()->user()->isProcurementFinance()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Finance Approval — Budget Confirmation')
                    ->modalDescription('Confirm that the budget is available and authorized for this requisition. It will proceed to Procurement for final approval.')
                    ->form([
                        Textarea::make('finance_remarks')->label('Finance Remarks (optional)')->rows(3)->nullable(),
                    ])
                    ->action(function (Requisition $r, array $data) {
                        $r->update([
                            'finance_status'      => 'Approved',
                            'finance_approved_by' => auth()->id(),
                            'finance_approved_at' => now(),
                            'finance_remarks'     => $data['finance_remarks'] ?? null,
                        ]);
                        Notification::make()->title('✓ Finance approved — forwarded to Procurement')->success()->send();
                    }),

                // ── Stage 4: Procurement Approve (Final) ─────────────
                Action::make('procurement_approve')
                    ->label('Authorize (Procurement)')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->visible(fn (Requisition $r) =>
                        $r->canProcurementApprove() && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Final Procurement Authorization')
                    ->modalDescription('By authorizing, this requisition will be fully approved and ready for tendering or direct procurement.')
                    ->modalSubmitActionLabel('Authorize')
                    ->form([
                        Textarea::make('procurement_remarks')->label('Procurement Remarks (optional)')->rows(3)->nullable(),
                    ])
                    ->action(function (Requisition $r, array $data) {
                        $r->update([
                            'procurement_status'      => 'Approved',
                            'procurement_approved_by' => auth()->id(),
                            'procurement_approved_at' => now(),
                            'procurement_remarks'     => $data['procurement_remarks'] ?? null,
                            'overall_status'          => Requisition::STATUS_APPROVED,
                        ]);
                        Notification::make()->title('✅ Requisition fully authorized — ready for procurement!')->success()->send();
                    }),

                // ── Reject (any stage, with reason) ──────────────────
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Requisition $r) =>
                        ! $r->isRejected()
                        && ! $r->isFullyApproved()
                        && $r->overall_status !== Requisition::STATUS_DRAFT
                        && (
                            auth()->user()->isProcurementSupervisor()
                            || auth()->user()->isProcurementDeptHead()
                            || auth()->user()->isProcurementFinance()
                            || auth()->user()->isProcurementOfficer()
                        )
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Reject Requisition')
                    ->modalDescription('Please provide a reason for rejection. The requester will be notified.')
                    ->form([
                        Textarea::make('rejection_reason')->label('Rejection Reason')->required()->rows(3),
                    ])
                    ->action(function (Requisition $r, array $data) {
                        // Reject at whichever stage is currently active
                        $updates = ['overall_status' => Requisition::STATUS_REJECTED];
                        if ($r->supervisor_status === 'Pending') {
                            $updates['supervisor_status'] = 'Rejected';
                            $updates['supervisor_remarks'] = $data['rejection_reason'];
                        } elseif ($r->dept_head_status === 'Pending') {
                            $updates['dept_head_status'] = 'Rejected';
                            $updates['dept_head_remarks'] = $data['rejection_reason'];
                        } elseif ($r->finance_status === 'Pending') {
                            $updates['finance_status'] = 'Rejected';
                            $updates['finance_remarks'] = $data['rejection_reason'];
                        } else {
                            $updates['procurement_status'] = 'Rejected';
                            $updates['procurement_remarks'] = $data['rejection_reason'];
                        }
                        $r->update($updates);
                        Notification::make()->title('Requisition rejected')->danger()->send();
                    }),

                EditAction::make(),
                DeleteAction::make()->visible(fn (Requisition $r) =>
                    $r->overall_status === Requisition::STATUS_DRAFT
                ),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRequisitions::route('/'),
            'create' => CreateRequisition::route('/create'),
            'edit'   => EditRequisition::route('/{record}/edit'),
        ];
    }
}
