<?php

namespace App\Filament\Resources\Procurement\Contracts;

use App\Filament\Resources\Procurement\Contracts\Pages\CreateContract;
use App\Filament\Resources\Procurement\Contracts\Pages\EditContract;
use App\Filament\Resources\Procurement\Contracts\Pages\ListContracts;
use App\Models\Currency;
use App\Models\Procurement\Contract;
use BackedEnum;
use Filament\Schemas\Components\Utilities\Get;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class ContractResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = Contract::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Contracts';

    protected static ?int $navigationSort = 5;

    // ── Form ──────────────────────────────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contract Details')->columns(2)->schema([
                TextInput::make('contract_number')
                    ->label('Contract #')
                    ->disabled()->dehydrated()
                    ->placeholder('Auto-generated on save'),

                Select::make('contract_type')
                    ->options(fn () => \App\Models\Procurement\ProcurementContractType::where('is_active', true)->pluck('name', 'name')->toArray())
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('title')->required()->maxLength(300)->columnSpanFull(),
                Textarea::make('description')->rows(3)->columnSpanFull()->nullable(),

                Select::make('supplier_id')
                    ->label('Supplier / Contractor')
                    ->relationship('supplier', 'name')
                    ->searchable()->preload()->required(),

                TextInput::make('contract_value')->numeric()->prefix(fn (Get $get) => Currency::symbolFor($get('currency')))->required(),
                Select::make('currency')
                    ->label('Currency')
                    ->options(fn () => Currency::procurementOptions())
                    ->default(fn () => Currency::procurementDefault())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
                TextInput::make('advance_payment_percentage')
                    ->numeric()->suffix('%')->default(0)->label('Advance Payment (%)'),
                TextInput::make('payment_terms')->maxLength(200)->nullable()->placeholder('e.g., Net 30 from GRN'),

                Select::make('status')
                    ->options([
                        'Draft'             => 'Draft',
                        'Pending Approval'  => 'Pending Approval',
                        'Approved'          => 'Approved',
                        'Rejected'          => 'Rejected',
                        'Pending Signature' => 'Pending Signature',
                        'Active'            => 'Active',
                        'Suspended'         => 'Suspended',
                        'Expired'           => 'Expired',
                        'Terminated'        => 'Terminated',
                        'Completed'         => 'Completed',
                    ])
                    ->default('Draft')
                    ->required(),
            ]),

            Section::make('Contract Period & Signatories')->columns(2)->schema([
                DatePicker::make('effective_date')->label('Effective / Start Date'),
                DatePicker::make('expiry_date')->label('Expiry / End Date'),
                DatePicker::make('supplier_signed_at')->label('Supplier Signed On'),
                DatePicker::make('org_signed_at')->label('Organisation Signed On'),

                TextInput::make('org_signatory_name')->maxLength(150)->nullable()->label('Org. Signatory Name'),
                TextInput::make('org_signatory_title')->maxLength(100)->nullable()->label('Org. Signatory Title'),
                TextInput::make('supplier_contact_person')->maxLength(150)->nullable()->label('Supplier Contact Person'),
            ]),

            Section::make('Linked Procurement Documents')->columns(3)->schema([
                Select::make('tender_id')
                    ->label('Tender (optional)')
                    ->relationship('tender', 'tender_number')
                    ->searchable()->preload()->nullable(),

                Select::make('bid_id')
                    ->label('Awarded Bid (optional)')
                    ->relationship('bid', 'id')
                    ->getOptionLabelFromRecordUsing(fn (\App\Models\Procurement\Bid $record) => $record->reference_number ?? ("Bid #" . $record->id))
                    ->searchable()->preload()->nullable(),

                Select::make('purchase_order_id')
                    ->label('Purchase Order (optional)')
                    ->relationship('purchaseOrder', 'po_number')
                    ->searchable()->preload()->nullable(),
            ])->collapsible(),

            Section::make('Special Conditions & Attachments')->schema([
                Textarea::make('special_conditions')
                    ->label('Special Conditions / Terms')
                    ->rows(4)->nullable()->columnSpanFull(),

                FileUpload::make('attachments')
                    ->label('Contract Documents (signed PDFs, ToR, schedules)')
                    ->multiple()->disk('local')->directory('procurement/contracts')
                    ->nullable()->columnSpanFull(),
            ]),

            Section::make('Approval Trail')
                ->description('Live approval trail — configured under Procurement → Settings → Approval Workflows.')
                ->collapsible()
                ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('contract'))
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('_approval_trail')
                        ->label('')
                        ->columnSpanFull()
                        ->content(fn (?Contract $record) =>
                            \App\Services\Procurement\ProcurementApprovalService::renderApprovalTrailHtml($record, 'contract')
                        ),
                ]),

            Section::make('Amendment History')
                ->description('Use the "Add Amendment" action button on the list view to record contract amendments.')
                ->schema([
                    Repeater::make('versions')
                        ->relationship()
                        ->schema([
                            TextInput::make('version_number')->numeric()->label('Version #')->disabled(),
                            DatePicker::make('amendment_date')->required()->label('Amendment Date'),
                            TextInput::make('change_summary')->required()->maxLength(500)->label('Summary of Changes')->columnSpan(3),
                            TextInput::make('amended_value')->numeric()->prefix(fn (Get $get) => Currency::symbolFor($get('../../currency')))->nullable()->label('New Value'),
                            FileUpload::make('document')->multiple()->disk('local')->directory('procurement/contracts/amendments')->nullable()->label('Amendment Docs'),
                        ])
                        ->columns(4)
                        ->addable(false)
                        ->deletable(false)
                        ->itemLabel(fn (array $state) => 'v' . ($state['version_number'] ?? '?') . ' — ' . ($state['amendment_date'] ?? ''))
                        ->collapsible(),
                ])
                ->collapsible(),
        ]);
    }

    // ── Table ──────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contract_number')
                    ->label('Contract #')
                    ->searchable()->sortable()->weight('semibold')
                    ->copyable()->copyMessage('Copied!'),

                TextColumn::make('title')
                    ->searchable()->limit(50)->wrap(),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()->sortable(),

                TextColumn::make('contract_type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Goods Supply' => 'info',
                        'Services'     => 'primary',
                        'Works'        => 'warning',
                        'Consultancy'  => 'purple',
                        'Framework'    => 'gray',
                        default        => 'gray',
                    }),

                TextColumn::make('contract_value')->label('Value')->numeric(2)->sortable()
                    ->formatStateUsing(fn ($state, Contract $record) => ($record->currency ?? 'ETB') . ' ' . number_format((float)$state, 2)),

                TextColumn::make('effective_date')
                    ->label('Start')->date()->sortable(),

                TextColumn::make('expiry_date')
                    ->label('Expiry')
                    ->date()->sortable()
                    ->color(fn (Contract $record) => match (true) {
                        $record->expiry_date === null                    => null,
                        $record->expiry_date->isPast()                   => 'danger',
                        $record->days_until_expiry !== null
                            && $record->days_until_expiry <= 30          => 'warning',
                        default                                          => 'success',
                    }),

                TextColumn::make('days_until_expiry')
                    ->label('Days Left')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null  => 'gray',
                        $state === 0     => 'danger',
                        $state <= 30     => 'warning',
                        $state <= 90     => 'info',
                        default          => 'success',
                    })
                    ->suffix(fn ($state) => $state !== null ? ' days' : '')
                    ->placeholder('No Expiry'),

                TextColumn::make('versions_count')
                    ->counts('versions')
                    ->label('Amendments')
                    ->badge()->color('gray'),

                IconColumn::make('org_signed_at')
                    ->label('Org Signed')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                TextColumn::make('approval_stage')
                    ->label('Approval')
                    ->badge()
                    ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('contract'))
                    ->getStateUsing(fn (Contract $r) => \App\Services\Procurement\ProcurementApprovalService::currentStageLabel($r, 'contract'))
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
                        'Active'            => 'success',
                        'Pending Signature' => 'warning',
                        'Pending Approval'  => 'warning',
                        'Approved'          => 'success',
                        'Suspended'         => 'warning',
                        'Expired'           => 'danger',
                        'Terminated'        => 'danger',
                        'Completed'         => 'info',
                        'Rejected'          => 'danger',
                        default             => 'gray',
                    }),
            ])
            ->defaultSort('effective_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Draft' => 'Draft', 'Pending Approval' => 'Pending Approval', 'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Pending Signature' => 'Pending Signature',
                        'Active' => 'Active', 'Suspended' => 'Suspended',
                        'Expired' => 'Expired', 'Terminated' => 'Terminated', 'Completed' => 'Completed',
                    ]),
                SelectFilter::make('contract_type')
                    ->options([
                        'Goods Supply' => 'Goods Supply', 'Services' => 'Services',
                        'Works' => 'Works', 'Consultancy' => 'Consultancy', 'Framework' => 'Framework',
                    ]),
            ])
            ->recordActions([
                // Submit for approval
                Action::make('submit')
                    ->label(fn () => \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('contract') ? 'Submit for Approval' : 'Make Pending Signature')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Contract $r) => $r->status === 'Draft' && auth()->user()->isProcurementOfficer())
                    ->requiresConfirmation()
                    ->action(function (Contract $r) {
                        if (! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('contract')) {
                            $r->update(['status' => 'Pending Signature']);
                            Notification::make()->title('Contract moved to Pending Signature (no workflow active)')->success()->send();
                        } else {
                            $r->update(['status' => 'Pending Approval']);
                            \App\Services\Procurement\ProcurementApprovalService::initialise($r, 'contract');
                            Notification::make()->title('Contract submitted — approval workflow started')->info()->send();
                        }
                    }),

                // Approval actions
                Action::make('approve')
                    ->label(fn (Contract $r) =>
                        '✓ Approve — ' .
                        (\App\Services\Procurement\ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'contract')?->stage_name ?? 'Approve')
                    )
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Contract $r) =>
                        $r->status === 'Pending Approval'
                        && \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('contract')
                        && \App\Services\Procurement\ProcurementApprovalService::canApprove(auth()->user(), $r, 'contract')
                    )
                    ->requiresConfirmation()
                    ->form([\Filament\Forms\Components\Textarea::make('notes')->label('Remarks (optional)')->rows(3)->nullable()])
                    ->action(function (Contract $r, array $data) {
                        $user    = auth()->user();
                        $pending = \App\Services\Procurement\ProcurementApprovalService::pendingRecordFor($user, $r, 'contract');
                        if (! $pending) return;

                        \App\Services\Procurement\ProcurementApprovalService::approve($r, 'contract', $pending->stage_order, $user, $data['notes'] ?? null);

                        if (\App\Services\Procurement\ProcurementApprovalService::isFullyApproved($r, 'contract')) {
                            $r->update(['status' => 'Pending Signature']);
                            Notification::make()->title('✓ Contract fully approved — ready for signatures!')->success()->send();
                        } else {
                            Notification::make()->title("✓ Approved: {$pending->stage_name} — advancing to next stage")->success()->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (Contract $r) {
                        if ($r->status !== 'Pending Approval') return false;
                        if (! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('contract')) return false;
                        $pending = \App\Services\Procurement\ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'contract');
                        return $pending && ($pending->stage?->can_reject ?? true);
                    })
                    ->requiresConfirmation()
                    ->form([\Filament\Forms\Components\Textarea::make('notes')->label('Rejection Reason')->required()->rows(3)])
                    ->action(function (Contract $r, array $data) {
                        $user    = auth()->user();
                        $pending = \App\Services\Procurement\ProcurementApprovalService::pendingRecordFor($user, $r, 'contract');
                        if (! $pending) return;

                        \App\Services\Procurement\ProcurementApprovalService::reject($r, 'contract', $pending->stage_order, $user, $data['notes'] ?? null);
                        $r->update(['status' => 'Rejected']);
                        Notification::make()->title("Contract rejected at {$pending->stage_name}")->danger()->send();
                    }),

                // Activate contract (once both parties signed)
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Contract $r) =>
                        $r->status === 'Pending Signature'
                        && $r->org_signed_at && $r->supplier_signed_at
                        && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Activate Contract')
                    ->modalDescription('Both parties have signed. Activate this contract to make it operational.')
                    ->action(fn (Contract $r) =>
                        $r->update(['status' => 'Active'])
                        && Notification::make()->title('Contract activated')->success()->send()
                    ),

                // Record amendment
                Action::make('amend')
                    ->label('Add Amendment')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (Contract $r) =>
                        $r->status === 'Active' && auth()->user()->isProcurementOfficer()
                    )
                    ->form([
                        TextInput::make('change_summary')
                            ->label('Summary of Changes')
                            ->required()->maxLength(500),
                        TextInput::make('amended_value')
                            ->label('New Contract Value (ETB) — leave blank if unchanged')
                            ->numeric()->prefix('ETB')->nullable(),
                        DatePicker::make('amendment_date')
                            ->label('Amendment Date')->required()->default(now()->toDateString()),
                        FileUpload::make('document')
                            ->label('Amendment Documents')->multiple()->disk('local')
                            ->directory('procurement/contracts/amendments')->nullable(),
                    ])
                    ->action(function (Contract $r, array $data) {
                        $versionNumber = $r->versions()->max('version_number') + 1;
                        $r->versions()->create([
                            'version_number' => $versionNumber,
                            'change_summary' => $data['change_summary'],
                            'amended_value'  => $data['amended_value'] ?? null,
                            'amendment_date' => $data['amendment_date'],
                            'amended_by'     => auth()->id(),
                            'document'       => $data['document'] ?? null,
                        ]);
                        if (!empty($data['amended_value'])) {
                            $r->update(['contract_value' => $data['amended_value']]);
                        }
                        Notification::make()->title("Amendment v{$versionNumber} recorded")->success()->send();
                    }),

                // Suspend contract
                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->visible(fn (Contract $r) =>
                        $r->status === 'Active' && auth()->user()->isProcurementDirector()
                    )
                    ->requiresConfirmation()
                    ->action(fn (Contract $r) =>
                        $r->update(['status' => 'Suspended'])
                        && Notification::make()->title('Contract suspended')->warning()->send()
                    ),

                // Terminate contract
                Action::make('terminate')
                    ->label('Terminate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Contract $r) =>
                        in_array($r->status, ['Active', 'Suspended'])
                        && auth()->user()->isProcurementDirector()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Terminate Contract')
                    ->modalDescription('⚠️ This action is irreversible. The contract will be marked as terminated.')
                    ->form([
                        Textarea::make('termination_reason')->label('Termination Reason')->required()->rows(3),
                    ])
                    ->action(function (Contract $r, array $data) {
                        $r->update([
                            'status'            => 'Terminated',
                            'special_conditions' => ($r->special_conditions ?? '') . "\n\nTERMINATION REASON: " . $data['termination_reason'],
                        ]);
                        Notification::make()->title('Contract terminated')->danger()->send();
                    }),

                // Mark completed
                Action::make('complete')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-flag')
                    ->color('info')
                    ->visible(fn (Contract $r) =>
                        $r->status === 'Active' && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Mark Contract as Completed')
                    ->modalDescription('Confirm all obligations under this contract have been fulfilled.')
                    ->action(fn (Contract $r) =>
                        $r->update(['status' => 'Completed'])
                        && Notification::make()->title('Contract marked completed ✓')->success()->send()
                    ),

                EditAction::make(),
                DeleteAction::make()->visible(fn (Contract $r) => $r->status === 'Draft'),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListContracts::route('/'),
            'create' => CreateContract::route('/create'),
            'edit'   => EditContract::route('/{record}/edit'),
        ];
    }
}
