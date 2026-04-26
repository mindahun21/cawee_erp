<?php

namespace App\Filament\Resources\Procurement\Requisitions;

use App\Filament\Resources\Procurement\Requisitions\Pages\CreateRequisition;
use App\Filament\Resources\Procurement\Requisitions\Pages\EditRequisition;
use App\Filament\Resources\Procurement\Requisitions\Pages\ListRequisitions;
use App\Filament\Resources\Procurement\Tenders\TenderResource;
use App\Models\Currency;
use App\Models\Procurement\ProcurementBudget;
use App\Models\Procurement\Requisition;
use App\Models\Procurement\Supplier;
use App\Models\Procurement\Tender;
use App\Services\Procurement\ProcurementApprovalService;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Utilities\Get;
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

class RequisitionResource extends Resource
{
    use BelongsToModule;
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
                    ->options(fn () => \App\Models\Procurement\ProcurementCategory::where('is_active', true)->pluck('name', 'name')->toArray())
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('budget_id')
                    ->label('Budget Line')
                    ->relationship('budget', 'code')
                    ->getOptionLabelFromRecordUsing(function (ProcurementBudget $r) {
                        return "{$r->code} — {$r->title} (Committed: ETB " . number_format($r->committed_amount, 2) . ")";
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Select the applicable budget line for this requisition.'),

                TextInput::make('budget_code')
                    ->label('Budget Code (manual)')
                    ->maxLength(50)
                    ->nullable(),

                Select::make('department')
                    ->options(fn () => \App\Models\Department::pluck('name', 'name')->toArray())
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('cost_center')->maxLength(100)->nullable(),

                Select::make('procurement_method')
                    ->label('Procurement Method')
                    ->options(fn () => \App\Models\Procurement\ProcurementMethod::where('is_active', true)->pluck('name', 'name')->toArray())
                    ->searchable()
                    ->preload()
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
                        \Filament\Forms\Components\Select::make('unit')
                            ->options(fn () => \App\Models\Procurement\ProcurementUnit::where('is_active', true)->pluck('name', 'abbreviation')->toArray())
                            ->searchable()
                            ->preload()
                            ->placeholder('pcs, kg, hr…'),
                        TextInput::make('quantity')->numeric()->minValue(0.0001)->default(1)->required(),
                        TextInput::make('estimated_unit_price')
                            ->label('Unit Price (ETB)')
                            ->numeric()
                            ->prefix('ETB')
                            ->default(0),
                        \Filament\Forms\Components\Textarea::make('specifications')->rows(2)->columnSpanFull()->nullable(),
                    ])
                    ->columns(6)
                    ->addActionLabel('+ Add Item')
                    ->defaultItems(1)
                    ->collapsible()
                    ->itemLabel(fn (array $state) => $state['description'] ?? 'New Item'),
            ]),

            Section::make('Approval Trail')
                ->description('Live approval trail — configured under Procurement → Settings → Approval Workflows.')
                ->collapsible()
                ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('requisition'))
                ->schema([
                    Placeholder::make('_approval_trail')
                        ->label('')
                        ->columnSpanFull()
                        ->content(fn (?Requisition $record) =>
                            ProcurementApprovalService::renderApprovalTrailHtml($record, 'requisition')
                        ),
                ]),
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

                TextColumn::make('approval_stage')
                    ->label('Approval')
                    ->badge()
                    ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('requisition'))
                    ->getStateUsing(fn (Requisition $r) => ProcurementApprovalService::currentStageLabel($r, 'requisition'))
                    ->color(fn ($state) => match (true) {
                        str_contains($state, 'Fully Approved') => 'success',
                        str_contains($state, 'Rejected')       => 'danger',
                        str_contains($state, 'Awaiting')       => 'warning',
                        $state === 'Not Started'               => 'gray',
                        default                                => 'info',
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
                    ->options(fn () => \App\Models\Procurement\ProcurementCategory::pluck('name', 'name')->toArray()),
            ])
            ->recordActions([
                // ── Submit (Draft → Submitted) ──────────────────────
                Action::make('submit')
                    ->label(fn () => \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('requisition') ? 'Submit for Approval' : 'Approve Requisition')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Requisition $r) =>
                        $r->overall_status === Requisition::STATUS_DRAFT
                        && auth()->user()->isProcurementRequester()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Submit Requisition')
                    ->modalDescription('This will submit the requisition. Ensure all items and details are complete before proceeding.')
                    ->modalSubmitActionLabel('Submit')
                    ->action(function (Requisition $r) {
                        if (! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('requisition')) {
                            $r->update(['overall_status' => Requisition::STATUS_APPROVED]);
                            Notification::make()->title('Requisition approved directly (no workflow active)')->success()->send();
                        } else {
                            $r->update(['overall_status' => Requisition::STATUS_SUBMITTED]);
                            ProcurementApprovalService::initialise($r, 'requisition');
                            Notification::make()->title('Requisition submitted — approval workflow started')->info()->send();
                        }
                    }),

                // ── Stage 1: Supervisor Approve ─────────────────────
                // ── Dynamic Approval via workflow config ──────────────────────────────────
                Action::make('approve')
                    ->label(fn (Requisition $r) =>
                        '✓ Approve — ' .
                        (ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'requisition')?->stage_name ?? 'Approve')
                    )
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Requisition $r) =>
                        !in_array($r->overall_status, [Requisition::STATUS_APPROVED, Requisition::STATUS_REJECTED,
                                                       Requisition::STATUS_DRAFT])
                        && \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('requisition')
                        && ProcurementApprovalService::canApprove(auth()->user(), $r, 'requisition')
                    )
                    ->requiresConfirmation()
                    ->form([Textarea::make('notes')->label('Approval Remarks (optional)')->rows(3)->nullable()])
                    ->action(function (Requisition $r, array $data) {
                        $user    = auth()->user();
                        $pending = ProcurementApprovalService::pendingRecordFor($user, $r, 'requisition');
                        if (! $pending) return;

                        ProcurementApprovalService::approve($r, 'requisition', $pending->stage_order, $user, $data['notes'] ?? null);

                        if (ProcurementApprovalService::isFullyApproved($r, 'requisition')) {
                            $r->update(['overall_status' => Requisition::STATUS_APPROVED]);
                            Notification::make()->title('✓ Requisition fully approved — ready for procurement!')->success()->send();
                        } else {
                            Notification::make()->title("✓ Approved: {$pending->stage_name} — advancing to next stage")->success()->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (Requisition $r) {
                        if ($r->isRejected() || $r->isFullyApproved() || $r->overall_status === Requisition::STATUS_DRAFT) return false;
                        if (! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('requisition')) return false;
                        $pending = ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'requisition');
                        return $pending && ($pending->stage?->can_reject ?? true);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Reject Requisition')
                    ->modalDescription('Please provide a reason for rejection. The requester will be notified.')
                    ->form([Textarea::make('notes')->label('Rejection Reason')->required()->rows(3)])
                    ->action(function (Requisition $r, array $data) {
                        $user    = auth()->user();
                        $pending = ProcurementApprovalService::pendingRecordFor($user, $r, 'requisition');
                        if (! $pending) return;

                        ProcurementApprovalService::reject($r, 'requisition', $pending->stage_order, $user, $data['notes'] ?? null);
                        $r->update(['overall_status' => Requisition::STATUS_REJECTED]);
                        Notification::make()->title("Requisition rejected at {$pending->stage_name}")->danger()->send();
                    }),

                Action::make('share_to_vendors')
                    ->label(fn (Requisition $r) => $r->tender ? 'View Tender' : 'Share to Vendors')
                    ->icon('heroicon-o-megaphone')
                    ->color('primary')
                    ->visible(fn (Requisition $r) =>
                        $r->isFullyApproved()
                        && auth()->user()->isProcurementOfficer()
                    )
                    ->modalHeading('Share Requisition to Vendors')
                    ->modalDescription('Create a Tender/RFQ linked to the approved requisition and control supplier access.')
                    ->form(fn (Requisition $r) => $r->tender ? [] : [
                        TextInput::make('title')
                            ->required()
                            ->maxLength(300)
                            ->default(fn (Requisition $record) => "Tender for {$record->requisition_number}"),

                        Select::make('method')
                            ->label('Procurement Method')
                            ->options(fn () => \App\Models\Procurement\ProcurementMethod::where('is_active', true)->pluck('name', 'name')->toArray())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn (Requisition $record) => $record->procurement_method),

                        Select::make('visibility')
                            ->label('Visibility')
                            ->options([
                                'public' => 'Public (all suppliers)',
                                'invite_only' => 'Invite-only',
                            ])
                            ->default('invite_only')
                            ->required()
                            ->live(),

                        Select::make('invited_suppliers')
                            ->label('Invited Suppliers')
                            ->options(fn () => Supplier::where('status', 'Active')->pluck('name', 'id')->toArray())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get) => $get('visibility') === 'invite_only')
                            ->visible(fn (Get $get) => $get('visibility') === 'invite_only')
                            ->helperText('Only invited suppliers will see this tender in the supplier portal.')
                            ->columnSpanFull(),

                        TextInput::make('estimated_value')
                            ->label('Estimated Value')
                            ->numeric()
                            ->prefix(fn (Get $get) => Currency::symbolFor($get('currency')))
                            ->required()
                            ->default(function (Requisition $record) {
                                $sum = (float) $record->items()->sum('estimated_total');
                                return $sum > 0 ? $sum : null;
                            }),

                        Select::make('currency')
                            ->label('Currency')
                            ->options(fn () => Currency::procurementOptions())
                            ->default(fn () => Currency::procurementDefault())
                            ->searchable()
                            ->preload()
                            ->required(),

                        DatePicker::make('issue_date')
                            ->required()
                            ->default(fn () => now()->toDateString()),

                        DatePicker::make('submission_deadline')
                            ->required()
                            ->default(fn () => now()->addDays(7)->toDateString()),

                        DatePicker::make('opening_date')
                            ->required()
                            ->default(fn () => now()->addDays(8)->toDateString()),

                        DatePicker::make('award_date')
                            ->required()
                            ->default(fn () => now()->addDays(14)->toDateString()),

                        Textarea::make('description')
                            ->rows(3)
                            ->required()
                            ->default(fn (Requisition $record) => $record->justification),

                        Textarea::make('terms_and_conditions')
                            ->rows(3)
                            ->required(),

                        FileUpload::make('attachments')
                            ->label('Tender Documents')
                            ->multiple()
                            ->disk('local')
                            ->directory('procurement/tenders')
                            ->nullable(),
                    ])
                    ->action(function (Requisition $r, array $data) {
                        if ($r->tender) {
                            return redirect(TenderResource::getUrl('edit', ['record' => $r->tender]));
                        }

                        $workflowActive = \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('tender');
                        $status = $workflowActive ? 'Draft' : 'Published';
                        $visibility = $data['visibility'] ?? 'public';

                        $tender = Tender::create([
                            'requisition_id'       => $r->id,
                            'title'                => $data['title'],
                            'description'          => $data['description'],
                            'method'               => $data['method'],
                            'status'               => $status,
                            'visibility'           => $visibility,
                            'issue_date'           => $status === 'Published' ? now()->toDateString() : $data['issue_date'],
                            'submission_deadline'  => $data['submission_deadline'],
                            'opening_date'         => $data['opening_date'],
                            'award_date'           => $data['award_date'],
                            'estimated_value'      => $data['estimated_value'],
                            'currency'             => $data['currency'],
                            'terms_and_conditions' => $data['terms_and_conditions'],
                            'attachments'          => $data['attachments'] ?? [],
                        ]);

                        if ($visibility === 'invite_only' && ! empty($data['invited_suppliers'])) {
                            $tender->invitedSuppliers()->sync($data['invited_suppliers']);
                        }

                        if ($status === 'Published') {
                            Notification::make()->title('Tender published — visible to suppliers.')->success()->send();
                        } else {
                            Notification::make()->title('Tender created in Draft — submit for tender approval, then publish.')->info()->send();
                        }

                        return redirect(TenderResource::getUrl('edit', ['record' => $tender]));
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
