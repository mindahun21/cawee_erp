<?php

namespace App\Filament\Resources\Procurement\Contracts;

use App\Filament\Resources\Procurement\Contracts\Pages\CreateContract;
use App\Filament\Resources\Procurement\Contracts\Pages\EditContract;
use App\Filament\Resources\Procurement\Contracts\Pages\ListContracts;
use App\Models\Procurement\Contract;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContractResource extends Resource
{
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
                    ->options([
                        'Goods Supply' => 'Goods Supply',
                        'Services'     => 'Services',
                        'Works'        => 'Works',
                        'Consultancy'  => 'Consultancy',
                        'Framework'    => 'Framework Agreement',
                        'Other'        => 'Other',
                    ])
                    ->required(),

                TextInput::make('title')->required()->maxLength(300)->columnSpanFull(),
                Textarea::make('description')->rows(3)->columnSpanFull()->nullable(),

                Select::make('supplier_id')
                    ->label('Supplier / Contractor')
                    ->relationship('supplier', 'name')
                    ->searchable()->preload()->required(),

                TextInput::make('contract_value')->numeric()->prefix('ETB')->required(),
                TextInput::make('currency')->default('ETB')->maxLength(10),
                TextInput::make('advance_payment_percentage')
                    ->numeric()->suffix('%')->default(0)->label('Advance Payment (%)'),
                TextInput::make('payment_terms')->maxLength(200)->nullable()->placeholder('e.g., Net 30 from GRN'),

                Select::make('status')
                    ->options([
                        'Draft'             => 'Draft',
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
                    ->relationship('bid', 'reference_number')
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

            Section::make('Amendment History')
                ->description('Use the "Add Amendment" action button on the list view to record contract amendments.')
                ->schema([
                    Repeater::make('versions')
                        ->relationship()
                        ->schema([
                            TextInput::make('version_number')->numeric()->label('Version #')->disabled(),
                            DatePicker::make('amendment_date')->required()->label('Amendment Date'),
                            TextInput::make('change_summary')->required()->maxLength(500)->label('Summary of Changes')->columnSpan(3),
                            TextInput::make('amended_value')->numeric()->prefix('ETB')->nullable()->label('New Value'),
                            FileUpload::make('document')->disk('local')->directory('procurement/contracts/amendments')->nullable()->label('Amendment Doc'),
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

                TextColumn::make('contract_value')
                    ->label('Value (ETB)')
                    ->numeric(2)->prefix('ETB ')->sortable(),

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

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Active'            => 'success',
                        'Pending Signature' => 'warning',
                        'Suspended'         => 'warning',
                        'Expired'           => 'danger',
                        'Terminated'        => 'danger',
                        'Completed'         => 'info',
                        default             => 'gray',
                    }),
            ])
            ->defaultSort('effective_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Draft' => 'Draft', 'Pending Signature' => 'Pending Signature',
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
                        && Notification::make()->title('✅ Contract activated')->success()->send()
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
                            ->label('Amendment Document')->disk('local')
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
