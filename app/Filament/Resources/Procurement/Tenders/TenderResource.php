<?php

namespace App\Filament\Resources\Procurement\Tenders;

use App\Filament\Resources\Procurement\Tenders\Pages\CreateTender;
use App\Filament\Resources\Procurement\Tenders\Pages\EditTender;
use App\Filament\Resources\Procurement\Tenders\Pages\ListTenders;
use App\Models\Currency;
use App\Models\Procurement\Tender;
use BackedEnum;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\Procurement\Requisition;
use Illuminate\Support\HtmlString;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Services\Procurement\ProcurementApprovalService;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TenderResource extends Resource
{
    protected static ?string $model = Tender::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Tenders & RFQs';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tender Details')->columns(2)->schema([
                TextInput::make('tender_number')
                    ->label('Tender No.')
                    ->disabled()->dehydrated()
                    ->placeholder('Auto-generated'),

                Select::make('requisition_id')
                    ->label('Linked Requisition')
                    ->relationship('requisition', 'requisition_number')
                    ->searchable()->preload()->nullable()
                    ->live()
                    ->helperText('Selecting a requisition will load its approved item list below — no changes allowed.')
                    ->afterStateUpdated(fn () => null), // triggers live re-render of Placeholder below

                TextInput::make('title')->required()->maxLength(300)->columnSpanFull(),

                Select::make('method')
                    ->label('Procurement Method')
                    ->options(fn () => \App\Models\Procurement\ProcurementMethod::where('is_active', true)->pluck('name', 'name')->toArray())
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('estimated_value')->numeric()->prefix(fn (Get $get) => Currency::symbolFor($get('currency')))->required(),
                Select::make('currency')
                    ->label('Currency')
                    ->options(fn () => Currency::procurementOptions())
                    ->default(fn () => Currency::procurementDefault())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),

                DatePicker::make('issue_date')->required(),
                DatePicker::make('submission_deadline')->required(),
                DatePicker::make('opening_date')->required(),
                DatePicker::make('award_date')->required(),

                Textarea::make('description')->rows(3)->columnSpanFull()->required(),
                Textarea::make('terms_and_conditions')->rows(3)->columnSpanFull()->required(),

                FileUpload::make('attachments')
                    ->label('Tender Documents')
                    ->multiple()->disk('local')->directory('procurement/tenders')
                    ->required()->columnSpanFull(),
            ]),

            // ── Evaluation Criteria ────────────────────────────────────────
            Section::make('Evaluation Criteria')
                ->description('Define the scoring criteria for evaluating supplier bids. Weights must sum to 100%.')
                ->collapsible()
                ->schema([
                    Repeater::make('evaluationCriteria')
                        ->relationship()
                        ->schema([
                            TextInput::make('name')
                                ->label('Criterion Name')
                                ->placeholder('e.g., Technical Compliance')
                                ->required()->maxLength(200)
                                ->columnSpan(2),

                            TextInput::make('weight')
                                ->label('Weight (%)')
                                ->numeric()->minValue(0)->maxValue(100)
                                ->suffix('%')->default(0)->required(),

                            TextInput::make('sort_order')
                                ->label('Order')
                                ->numeric()->default(0)->minValue(0),

                            Textarea::make('description')
                                ->label('Description / Scoring Guide')
                                ->helperText('Optional: describe what a full score looks like for this criterion')
                                ->rows(2)->nullable()->columnSpanFull(),
                        ])
                        ->columns(4)
                        ->addActionLabel('+ Add Criterion')
                        ->defaultItems(0)
                        ->collapsible()
                        ->reorderable('sort_order')
                        ->itemLabel(fn (array $state) =>
                            ($state['name'] ?? 'Criterion') .
                            (isset($state['weight']) ? '  ·  ' . $state['weight'] . '%' : '')
                        ),
                ]),


            Section::make('Requisition Items')
                ->description('Items from the linked requisition — read-only. Quantities and descriptions are fixed by the approved requisition.')
                ->hidden(fn (Get $get) => blank($get('requisition_id')))
                ->schema([
                    Placeholder::make('_requisition_items_table')
                        ->label('')
                        ->columnSpanFull()
                        ->content(function (Get $get) {
                            $reqId = $get('requisition_id');
                            if (! $reqId) return new HtmlString('<p class="text-sm text-gray-400">No requisition selected.</p>');

                            $items = Requisition::find($reqId)?->items ?? collect();
                            if ($items->isEmpty()) {
                                return new HtmlString('<p class="text-sm text-gray-400">This requisition has no line items.</p>');
                            }

                            $rows = $items->map(fn ($item) =>
                                '<tr class="fi-ta-row">' .
                                '<td class="fi-ta-cell px-3 py-4 text-sm text-gray-950 dark:text-white font-medium">' . e($item->description) . '</td>' .
                                '<td class="fi-ta-cell px-3 py-4 text-sm text-gray-700 dark:text-gray-300 text-right tabular-nums">' . rtrim(rtrim(number_format((float)$item->quantity, 4, '.', ''), '0'), '.') . '</td>' .
                                '<td class="fi-ta-cell px-3 py-4 text-sm text-gray-700 dark:text-gray-300">' . e($item->unit ?? '—') . '</td>' .
                                '<td class="fi-ta-cell px-3 py-4 text-sm text-gray-700 dark:text-gray-300 text-right tabular-nums">' . number_format((float)$item->estimated_unit_price, 2) . '</td>' .
                                '<td class="fi-ta-cell px-3 py-4 text-sm font-semibold text-gray-950 dark:text-white text-right tabular-nums">' . number_format((float)$item->estimated_total, 2) . '</td>' .
                                '<td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400">' . e($item->specifications ?? '—') . '</td>' .
                                '</tr>'
                            )->implode('');

                            return new HtmlString(
                                '<div class="fi-ta-ctn overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">' .
                                '<table class="fi-ta-table w-full table-auto divide-y divide-gray-200 dark:divide-white/5 text-left">' .
                                '<thead class="bg-gray-50 dark:bg-white/5">' .
                                '<tr>' .
                                '<th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Description</th>' .
                                '<th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Qty</th>' .
                                '<th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Unit</th>' .
                                '<th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Est. Unit Price</th>' .
                                '<th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Est. Total</th>' .
                                '<th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Specifications</th>' .
                                '</tr>' .
                                '</thead>' .
                                '<tbody class="divide-y divide-gray-200 dark:divide-white/5">' . $rows . '</tbody>' .
                                '</table>' .
                                '</div>'
                            );
                        }),
                ]),

            Section::make('Approval Trail')
                ->description('Live approval trail — configured under Procurement → Settings → Approval Workflows.')
                ->collapsible()
                ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('tender'))
                ->schema([
                    Placeholder::make('_approval_trail')
                        ->label('')
                        ->columnSpanFull()
                        ->content(fn (?Tender $record) =>
                            ProcurementApprovalService::renderApprovalTrailHtml($record, 'tender')
                        ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tender_number')
                    ->label('Tender #')
                    ->searchable()->sortable()->weight('semibold')->copyable()->copyMessage('Copied!'),

                TextColumn::make('title')->searchable()->wrap()->limit(60),

                TextColumn::make('method')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Open Tender'        => 'info',
                        'Restricted Tender'  => 'primary',
                        'Two-Stage Tender'   => 'purple',
                        'RFP'                => 'warning',
                        'RFQ'                => 'gray',
                        'Direct Procurement' => 'success',
                        default              => 'gray',
                    }),

                TextColumn::make('submission_deadline')->date()->sortable(),
                TextColumn::make('award_date')->date()->sortable()->toggleable(),

                TextColumn::make('estimated_value')->label('Est. Value')->numeric(2)->toggleable()
                    ->formatStateUsing(fn ($state, Tender $record) => ($record->currency ?? 'ETB') . ' ' . number_format((float)$state, 2)),

                TextColumn::make('bids_count')
                    ->label('Bids')
                    ->counts('bids')
                    ->badge()->color('info'),

                TextColumn::make('approval_stage')
                    ->label('Approval')
                    ->badge()
                    ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('tender'))
                    ->getStateUsing(fn (Tender $r) => ProcurementApprovalService::currentStageLabel($r, 'tender'))
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
                        'Published'         => 'info',
                        'Evaluation'        => 'warning',
                        'Awarded'           => 'success',
                        'Cancelled'         => 'danger',
                        'Pending Approval'  => 'warning',
                        'Approved'          => 'success',
                        'Rejected'          => 'danger',
                        'Closed'            => 'gray',
                        default             => 'gray',
                    }),
            ])
            ->defaultSort('submission_deadline', 'desc')
            ->filters([
                SelectFilter::make('method')
                    ->options([
                        'Open Tender' => 'Open Tender', 'Restricted Tender' => 'Restricted Tender',
                        'Two-Stage Tender' => 'Two-Stage Tender', 'RFP' => 'RFP',
                        'RFQ' => 'RFQ', 'Direct Procurement' => 'Direct Procurement',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'Draft' => 'Draft', 'Published' => 'Published', 'Closed' => 'Closed',
                        'Evaluation' => 'Evaluation', 'Awarded' => 'Awarded', 'Cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                // Submit for approval
                Action::make('submit')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Tender $r) => 
                        $r->status === 'Draft' 
                        && auth()->user()->isProcurementOfficer()
                        && \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('tender')
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Submit Tender for Approval')
                    ->modalDescription('Submitting will start the approval workflow for this Tender/RFQ. Are you sure?')
                    ->action(function (Tender $r) {
                        $r->update(['status' => 'Pending Approval']);
                        ProcurementApprovalService::initialise($r, 'tender');
                        Notification::make()->title('Tender submitted — approval workflow started')->info()->send();
                    }),

                // ── Dynamic Approval via workflow config ──────────────────────────────────
                Action::make('approve')
                    ->label(fn (Tender $r) =>
                        '✓ Approve — ' .
                        (ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'tender')?->stage_name ?? 'Approve')
                    )
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Tender $r) =>
                        $r->status === 'Pending Approval'
                        && \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('tender')
                        && ProcurementApprovalService::canApprove(auth()->user(), $r, 'tender')
                    )
                    ->requiresConfirmation()
                    ->form([Textarea::make('notes')->label('Approval Remarks (optional)')->rows(3)->nullable()])
                    ->action(function (Tender $r, array $data) {
                        $user    = auth()->user();
                        $pending = ProcurementApprovalService::pendingRecordFor($user, $r, 'tender');
                        if (! $pending) return;

                        ProcurementApprovalService::approve($r, 'tender', $pending->stage_order, $user, $data['notes'] ?? null);

                        if (ProcurementApprovalService::isFullyApproved($r, 'tender')) {
                            $r->update(['status' => 'Approved']);
                            Notification::make()->title('✓ Tender fully approved — ready to publish!')->success()->send();
                        } else {
                            Notification::make()->title("✓ Approved: {$pending->stage_name} — advancing to next stage")->success()->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (Tender $r) {
                        if ($r->status !== 'Pending Approval') return false;
                        if (! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('tender')) return false;
                        $pending = ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'tender');
                        return $pending && ($pending->stage?->can_reject ?? true);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Reject Tender')
                    ->modalDescription('Please provide a reason for rejection. This will return it to draft or mark it as rejected.')
                    ->form([Textarea::make('notes')->label('Rejection Reason')->required()->rows(3)])
                    ->action(function (Tender $r, array $data) {
                        $user    = auth()->user();
                        $pending = ProcurementApprovalService::pendingRecordFor($user, $r, 'tender');
                        if (! $pending) return;

                        ProcurementApprovalService::reject($r, 'tender', $pending->stage_order, $user, $data['notes'] ?? null);
                        $r->update(['status' => 'Rejected']);
                        Notification::make()->title("Tender rejected at {$pending->stage_name}")->danger()->send();
                    }),

                // Publish tender
                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-megaphone')
                    ->color('primary')
                    ->visible(fn (Tender $r) =>
                        (
                            $r->status === 'Approved' 
                            || (in_array($r->status, ['Draft', 'Pending Approval']) && ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('tender'))
                        )
                        && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Publish Tender')
                    ->modalDescription('Publishing will make this tender visible to invited suppliers for bid submission.')
                    ->action(fn (Tender $r) =>
                        $r->update(['status' => 'Published', 'issue_date' => now()->toDateString()])
                        && Notification::make()->title('Tender published — open for bids')->success()->send()
                    ),

                // Close submissions
                Action::make('close_submissions')
                    ->label('Close Submissions')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(fn (Tender $r) =>
                        $r->status === 'Published' && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->action(fn (Tender $r) =>
                        $r->update(['status' => 'Closed'])
                        && Notification::make()->title('Submissions closed — proceed to evaluation')->warning()->send()
                    ),

                // Move to Evaluation
                Action::make('start_evaluation')
                    ->label('Start Evaluation')
                    ->icon('heroicon-o-magnifying-glass-circle')
                    ->color('primary')
                    ->visible(fn (Tender $r) =>
                        $r->status === 'Closed' && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->action(fn (Tender $r) =>
                        $r->update(['status' => 'Evaluation'])
                        && Notification::make()->title('Evaluation phase started')->info()->send()
                    ),

                // Award
                Action::make('award')
                    ->label('Mark Awarded')
                    ->icon('heroicon-o-trophy')
                    ->color('success')
                    ->visible(fn (Tender $r) =>
                        $r->status === 'Evaluation' && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Mark Tender as Awarded')
                    ->modalDescription('Confirm that the evaluation is complete and a supplier has been selected. You can then create a Purchase Order from the awarded bid.')
                    ->action(fn (Tender $r) =>
                        $r->update(['status' => 'Awarded', 'award_date' => now()->toDateString()])
                        && Notification::make()->title('Tender awarded   — generate PO from awarded bid')->success()->send()
                    ),

                // Cancel
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Tender $r) =>
                        ! in_array($r->status, ['Awarded', 'Cancelled'])
                        && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->action(fn (Tender $r) =>
                        $r->update(['status' => 'Cancelled'])
                        && Notification::make()->title('Tender cancelled')->danger()->send()
                    ),

                EditAction::make(),
                DeleteAction::make()->visible(fn (Tender $r) => $r->status === 'Draft'),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTenders::route('/'),
            'create' => CreateTender::route('/create'),
            'edit'   => EditTender::route('/{record}/edit'),
        ];
    }
}
