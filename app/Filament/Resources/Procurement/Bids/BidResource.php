<?php

namespace App\Filament\Resources\Procurement\Bids;

use App\Filament\Resources\Procurement\Bids\Pages\CreateBid;
use App\Filament\Resources\Procurement\Bids\Pages\EditBid;
use App\Filament\Resources\Procurement\Bids\Pages\ListBids;
use App\Mail\BidAwardedMail;
use App\Models\Currency;
use App\Models\Procurement\Bid;
use App\Models\Procurement\BidCriterionScore;
use Illuminate\Support\Facades\Mail;
use BackedEnum;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class BidResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = Bid::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Bid Submissions';

    protected static ?int $navigationSort = 3;

    // ── Form ──────────────────────────────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Bid Details')->columns(2)->schema([
                Select::make('tender_id')
                    ->label('Tender / RFQ')
                    ->relationship('tender', 'tender_number',
                        fn ($query) => $query->whereIn('status', ['Published', 'Closed', 'Evaluation'])
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->tender_number} — {$record->title}")
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name',
                        fn ($query) => $query->where('status', 'Active')
                    )
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('reference_number')
                    ->label("Supplier's Reference #")
                    ->maxLength(100)
                    ->nullable(),

                DatePicker::make('submission_date')
                    ->default(now()->toDateString())
                    ->required(),

                TextInput::make('bid_amount')
                    ->numeric()
                    ->prefix(fn (Get $get) => Currency::symbolFor($get('currency')))
                    ->required(),

                Select::make('currency')
                    ->label('Currency')
                    ->options(fn () => Currency::procurementOptions())
                    ->default(fn () => Currency::procurementDefault())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),

                TextInput::make('delivery_days')
                    ->numeric()
                    ->label('Delivery Period (days)')
                    ->required(),

                DatePicker::make('validity_date')
                    ->label('Bid Validity Until')
                    ->required(),

                Select::make('bid_security')
                    ->label('Bid Security Provided')
                    ->options(fn () => \App\Models\Procurement\ProcurementBidSecurity::where('is_active', true)->pluck('name', 'name')->toArray())
                    ->searchable()
                    ->preload()
                    ->required(),

                Checkbox::make('conflict_of_interest_declared')
                    ->label('Conflict of Interest Declared')
                    ->helperText('Check if any evaluation committee member has declared a conflict of interest with this supplier.'),

                Select::make('status')
                    ->options([
                        'Submitted'        => 'Submitted',
                        'Under Review'     => 'Under Review',
                        'Pending Approval' => 'Pending Approval',
                        'Shortlisted'      => 'Shortlisted',
                        'Awarded'          => 'Awarded',
                        'Rejected'         => 'Rejected',
                    ])
                    ->default('Submitted')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Status is managed automatically via the approval workflow.'),

                Textarea::make('notes')->rows(3)->columnSpanFull()->nullable(),

                FileUpload::make('attachments')
                    ->label('Bid Documents')
                    ->multiple()
                    ->disk('local')
                    ->directory('procurement/bids')
                    ->required()
                    ->columnSpanFull(),
            ]),

            // ── Evaluation Scores ──────────────────────────────────────
            Section::make('Evaluation Scores')
                ->visibleOn('edit')
                ->description('Scores are entered during the evaluation phase by committee members.')
                ->columns(3)
                ->schema([
                    TextInput::make('technical_score')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('/100')
                        ->label('Technical Score')
                        ->hidden(fn (?\App\Models\Procurement\Bid $record) => $record?->tender?->evaluationCriteria()->exists())
                        ->nullable(),

                    TextInput::make('financial_score')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('/100')
                        ->label('Financial Score')
                        ->hidden(fn (?\App\Models\Procurement\Bid $record) => $record?->tender?->evaluationCriteria()->exists())
                        ->nullable(),

                    TextInput::make('composite_score')
                        ->numeric()
                        ->suffix('/100')
                        ->label('Composite Score')
                        ->readOnly()
                        ->helperText('Auto-calculated by scoring action or saved automatically from the Criteria responses below.')
                        ->afterStateHydrated(function ($set, ?Bid $record) {
                            if (! $record) return;
                            $tender = $record->tender;
                            if (! $tender || ! $tender->evaluationCriteria()->exists()) return;

                            $criteria      = $tender->evaluationCriteria()->get();
                            $criteriaWeights = $criteria->pluck('weight', 'id');
                            $scores        = $record->criterionScores()->get()->keyBy('criterion_id');
                            $weightedSum   = 0;
                            $hasScores     = false;

                            foreach ($criteria as $crit) {
                                $scoreRow = $scores->get($crit->id);
                                if ($scoreRow && $scoreRow->score !== null) {
                                    $weightedSum += (float)$scoreRow->score * ((float)$criteriaWeights[$crit->id] / 100);
                                    $hasScores = true;
                                }
                            }

                            if ($hasScores) {
                                $set('composite_score', round($weightedSum, 2));
                            }
                        }),
                ])
                ->collapsible(),

            Section::make('Evaluation Criteria & Responses')
                ->description('Supplier responses and corresponding scores for each criterion defined on the Tender.')
                ->hidden(fn (?\App\Models\Procurement\Bid $record) => !($record?->tender?->evaluationCriteria()->exists()))
                ->schema([
                    \Filament\Forms\Components\Repeater::make('criterionScores')
                        ->relationship()
                        ->schema([
                            \Filament\Forms\Components\Select::make('criterion_id')
                                ->relationship(
                                    name: 'criterion',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query, $get) => 
                                        $get('../../tender_id') 
                                            ? $query->where('tender_id', $get('../../tender_id')) 
                                            : $query->where('id', -1)
                                )
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->weight}%)")
                                ->required()
                                ->label('Criterion')
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                            TextInput::make('score')
                                ->label('Score')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('/ 100')
                                ->nullable()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($get, $set, $state) {
                                    $tenderId = $get('../../tender_id');
                                    if (!$tenderId || $state === null) return;
                                    
                                    $tender = \App\Models\Procurement\Tender::find($tenderId);
                                    if (!$tender || !$tender->evaluationCriteria()->exists()) return;
                                    
                                    $criteriaWeights = $tender->evaluationCriteria()->pluck('weight', 'id');
                                    $allScores = $get('../../criterionScores') ?? [];
                                    $weightedSum = 0;
                                    $hasScores = false;
                                    
                                    foreach ($allScores as $rowKey => $row) {
                                        $cId = $row['criterion_id'] ?? null;
                                        $sVal = $row['score'] ?? null;
                                        if ($cId && $sVal !== null && isset($criteriaWeights[$cId])) {
                                            $weightedSum += ((float)$sVal * ((float)$criteriaWeights[$cId] / 100));
                                            $hasScores = true;
                                        }
                                    }
                                    
                                    if ($hasScores) {
                                        $set('../../composite_score', round($weightedSum, 2));
                                    }
                                }),

                            Textarea::make('notes')
                                ->label('Supplier Response')
                                ->rows(3)
                                ->columnSpanFull()
                                ->required(),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $record) {
                            $data['bid_id'] = $record->id;
                            if (isset($data['score']) && $data['score'] !== null) {
                                $data['scored_by'] = auth()->id();
                                $data['scored_at'] = now();
                            }
                            return $data;
                        })
                        ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $record) {
                            if (isset($data['score']) && $data['score'] !== null) {
                                $data['scored_by'] = $data['scored_by'] ?? auth()->id();
                                $data['scored_at'] = $data['scored_at'] ?? now();
                            }
                            return $data;
                        })
                ])
                ->collapsible(),

            Section::make('Award Approval Trail')
                ->description('Live approval trail — configured under Procurement → Settings → Approval Workflows.')
                ->collapsible()
                ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('bid'))
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('_approval_trail')
                        ->label('')
                        ->columnSpanFull()
                        ->content(fn (?Bid $record) =>
                            \App\Services\Procurement\ProcurementApprovalService::renderApprovalTrailHtml($record, 'bid')
                        ),
                ]),
        ]);
    }

    // ── Table ──────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tender.tender_number')
                    ->label('Tender #')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('tender.title')
                    ->label('Tender Title')
                    ->limit(45)
                    ->toggleable(),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bid_amount')->label('Bid Amount')->numeric(2)->sortable()
                    ->formatStateUsing(fn ($state, Bid $record) => ($record->currency ?? 'ETB') . ' ' . number_format((float)$state, 2)),

                TextColumn::make('submission_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('validity_date')
                    ->label('Valid Until')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('bid_security')
                    ->label('Security')
                    ->searchable()
                    ->toggleable(),

                // Show first criterion score (or flat technical_score if no criteria)
                TextColumn::make('technical_score')
                    ->label('Tech. / Criterion 1 Score')
                    ->badge()
                    ->getStateUsing(function (?Bid $record): mixed {
                        if (! $record) return null;
                        if ($record->tender?->evaluationCriteria()->exists()) {
                            $firstCrit = $record->tender->evaluationCriteria()->first();
                            return $firstCrit
                                ? $record->criterionScores()->where('criterion_id', $firstCrit->id)->value('score')
                                : null;
                        }
                        return $record->technical_score;
                    })
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state >= 80    => 'success',
                        $state >= 60    => 'warning',
                        default         => 'danger',
                    })
                    ->suffix('/100')
                    ->placeholder('—'),

                // Show second criterion score (or flat financial_score if no criteria)
                TextColumn::make('financial_score')
                    ->label('Fin. / Criterion 2 Score')
                    ->badge()
                    ->getStateUsing(function (?Bid $record): mixed {
                        if (! $record) return null;
                        if ($record->tender?->evaluationCriteria()->exists()) {
                            $secondCrit = $record->tender->evaluationCriteria()->skip(1)->first();
                            return $secondCrit
                                ? $record->criterionScores()->where('criterion_id', $secondCrit->id)->value('score')
                                : null;
                        }
                        return $record->financial_score;
                    })
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state >= 80    => 'success',
                        $state >= 60    => 'warning',
                        default         => 'danger',
                    })
                    ->suffix('/100')
                    ->placeholder('—'),

                TextColumn::make('composite_score')
                    ->label('Composite')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state >= 80   => 'success',
                        $state >= 60   => 'warning',
                        default        => 'danger',
                    })
                    ->suffix('/100')
                    ->sortable()
                    ->placeholder('—'),

                IconColumn::make('conflict_of_interest_declared')
                    ->label('COI')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                TextColumn::make('approval_stage')
                    ->label('Approval')
                    ->badge()
                    ->hidden(fn () => ! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('bid'))
                    ->getStateUsing(fn (Bid $r) => \App\Services\Procurement\ProcurementApprovalService::currentStageLabel($r, 'bid'))
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
                        'Awarded'          => 'success',
                        'Pending Approval' => 'warning',
                        'Shortlisted'      => 'info',
                        'Under Review'     => 'warning',
                        'Rejected'         => 'danger',
                        default            => 'gray',
                    }),
            ])
            ->defaultSort('submission_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Submitted'        => 'Submitted',
                        'Under Review'     => 'Under Review',
                        'Pending Approval' => 'Pending Approval',
                        'Shortlisted'      => 'Shortlisted',
                        'Awarded'          => 'Awarded',
                        'Rejected'         => 'Rejected',
                    ]),

                SelectFilter::make('tender_id')
                    ->label('Tender')
                    ->relationship('tender', 'tender_number'),
            ])
            ->recordActions([
                // Shortlist bid
                Action::make('shortlist')
                    ->label('Shortlist')
                    ->icon('heroicon-o-star')
                    ->color('info')
                    ->visible(fn (Bid $r) =>
                        in_array($r->status, ['Submitted', 'Under Review'])
                        && auth()->user()->isProcurementEvaluator()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Shortlist Bid')
                    ->modalDescription('Mark this bid as shortlisted for the next evaluation stage.')
                    ->action(fn (Bid $r) =>
                        $r->update(['status' => 'Shortlisted'])
                        && Notification::make()->title('Bid shortlisted ⭐')->info()->send()
                    ),

                // ── Score Bid: dynamic per-criterion OR fallback to 3-field ──
                Action::make('score')
                    ->label(fn (Bid $r) =>
                        $r->tender->evaluationCriteria()->exists()
                            ? 'Score per Criteria'
                            : 'Enter Scores'
                    )
                    ->icon('heroicon-o-calculator')
                    ->color('primary')
                    ->visible(fn (Bid $r) =>
                        in_array($r->status, ['Submitted', 'Under Review', 'Shortlisted'])
                        && auth()->user()->isProcurementEvaluator()
                    )
                    // form() closure runs before mounting — Livewire properly binds the dynamic fields
                    ->form(function (Bid $record): array {
                        $criteria = $record->tender->evaluationCriteria()->get();

                        if ($criteria->isEmpty()) {
                            return [
                                TextInput::make('technical_score')
                                    ->label('Technical Score (0–100)')
                                    ->numeric()->minValue(0)->maxValue(100)->required(),
                                TextInput::make('financial_score')
                                    ->label('Financial Score (0–100)')
                                    ->numeric()->minValue(0)->maxValue(100)->required(),
                                TextInput::make('composite_score')
                                    ->label('Composite / Weighted Score')
                                    ->numeric()->minValue(0)->maxValue(100)->nullable()
                                    ->helperText('Leave blank to auto-calculate as average of tech + financial.'),
                                Textarea::make('evaluation_notes')->label('Evaluation Notes')->rows(3)->nullable(),
                            ];
                        }

                        // Per-criterion fields — named crit_{id} so action handler can reference them
                        $fields = $criteria->map(function ($crit) use ($record): TextInput {
                            $existingScore = BidCriterionScore::where('bid_id', $record->id)
                                ->where('criterion_id', $crit->id)
                                ->first();

                            $helperText = (string) ($crit->description ?? '');
                            if ($existingScore?->notes) {
                                $helperText .= "\n\nSupplier Response: \"{$existingScore->notes}\"";
                            }

                            return TextInput::make("crit_{$crit->id}")
                                ->label("{$crit->name} ({$crit->weight}% weight)")
                                ->helperText($helperText ?: null)
                                ->numeric()->minValue(0)->maxValue(100)
                                ->suffix('/ 100')
                                ->required();
                        })->all();

                        $fields[] = Textarea::make('evaluation_notes')
                            ->label('Overall Evaluation Notes')
                            ->rows(3)->nullable();

                        return $fields;
                    })
                    // fillForm() pre-populates existing scores into the action form
                    ->fillForm(function (Bid $record): array {
                        $criteria = $record->tender->evaluationCriteria()->get();
                        if ($criteria->isEmpty()) {
                            return [
                                'technical_score' => $record->technical_score,
                                'financial_score' => $record->financial_score,
                                'composite_score' => $record->composite_score,
                            ];
                        }

                        $filled = [];
                        foreach ($criteria as $crit) {
                            $existing = BidCriterionScore::where('bid_id', $record->id)
                                ->where('criterion_id', $crit->id)
                                ->value('score');
                            $filled["crit_{$crit->id}"] = $existing ?? 0;
                        }
                        $filled['evaluation_notes'] = $record->notes;
                        return $filled;
                    })
                    ->action(function (Bid $r, array $data) {
                        $criteria = $r->tender->evaluationCriteria()->get();

                        if ($criteria->isEmpty()) {
                            // Fallback: 3-field scoring
                            $composite = $data['composite_score'] !== null && $data['composite_score'] !== ''
                                ? (float) $data['composite_score']
                                : round(((float)$data['technical_score'] + (float)$data['financial_score']) / 2, 2);
                            $r->update([
                                'technical_score' => $data['technical_score'],
                                'financial_score' => $data['financial_score'],
                                'composite_score' => $composite,
                                'status'          => 'Under Review',
                                'notes'           => $data['evaluation_notes'] ?? $r->notes,
                            ]);
                            Notification::make()->title('Evaluation scores saved')->success()->send();
                            return;
                        }

                        // Per-criterion scoring
                        $weightedSum = 0;
                        $totalWeight = 0;
                        foreach ($criteria as $crit) {
                            $score = (float) ($data["crit_{$crit->id}"] ?? 0);
                            BidCriterionScore::updateOrCreate(
                                ['bid_id' => $r->id, 'criterion_id' => $crit->id],
                                [
                                    'score'     => $score,
                                    'scored_by' => auth()->id(),
                                    'scored_at' => now(),
                                ]
                            );
                            $weightedSum += $score * ((float)$crit->weight / 100);
                            $totalWeight += (float)$crit->weight;
                        }

                        $composite = round($weightedSum, 2);
                        $r->update([
                            'composite_score' => $composite,
                            'status'          => 'Under Review',
                            'notes'           => $data['evaluation_notes'] ?? $r->notes,
                        ]);

                        $weightWarning = $totalWeight != 100
                            ? " ⚠️ Criteria weights sum to {$totalWeight}%, not 100% — scores may be misleading."
                            : '';

                        Notification::make()
                            ->title("✅ Scores saved — composite: {$composite}/100{$weightWarning}")
                            ->success()
                            ->send();
                    }),

                // Submit for Award Approval
                Action::make('submit_for_award')
                    ->label(fn () => \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('bid') ? 'Submit for Award' : 'Award Bid')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Bid $r) =>
                        in_array($r->status, ['Shortlisted', 'Under Review'])
                        && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Submit Bid for Award')
                    ->modalDescription('Submitting will start the approval workflow for awarding this bid.')
                    ->action(function (Bid $r) {
                        if (! \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('bid')) {
                            // Direct Award
                            $r->update(['status' => 'Awarded']);
                            // Reject all other bids on the same tender
                            Bid::where('tender_id', $r->tender_id)
                                ->where('id', '!=', $r->id)
                                ->whereNotIn('status', ['Rejected'])
                                ->update(['status' => 'Rejected']);
                            // Mark the tender as awarded
                            $r->tender?->update(['status' => 'Awarded', 'award_date' => now()->toDateString()]);
                            Notification::make()->title('🏆 Bid awarded directly (no workflow) — generate PO to proceed')->success()->send();
                        } else {
                            $r->update(['status' => 'Pending Approval']);
                            \App\Services\Procurement\ProcurementApprovalService::initialise($r, 'bid');
                            Notification::make()->title('Bid submitted for award — approval workflow started')->info()->send();
                        }
                    }),

                // ── Notify winner by email ──
                Action::make('send_award_email')
                    ->label(fn (Bid $r) => $r->award_email_sent_at ? 'Resend Award Email' : 'Send Award Email')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->visible(fn (Bid $r) =>
                        $r->status === 'Awarded'
                        && !empty($r->supplier?->email)
                        && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading(fn (Bid $r) => $r->award_email_sent_at ? 'Resend Award Notification Email' : 'Send Award Notification Email')
                    ->modalDescription(fn (Bid $r) => $r->award_email_sent_at
                        ? "This award email was already sent on {$r->award_email_sent_at->format('d M Y H:i')}. You can resend it to {$r->supplier->name} ({$r->supplier->email}) if they did not receive it."
                        : "Send the official award email to {$r->supplier->name} ({$r->supplier->email}). This will inform them of the contract award and next steps."
                    )
                    ->action(function (Bid $r) {
                        try {
                            Mail::to($r->supplier->email)
                                ->send(new BidAwardedMail($r));

                            $r->update(['award_email_sent_at' => now()]);

                            Notification::make()
                                ->title(($r->award_email_sent_at ? 'Award email resent to ' : 'Award email sent to ') . $r->supplier->email)
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Bid award email failed: ' . $e->getMessage());
                            Notification::make()
                                ->title('Failed to send award email')
                                ->body('There was a problem connecting to the email server. Please check your mail configuration. ' . str($e->getMessage())->limit(100))
                                ->danger()
                                ->send();
                        }
                    }),


                Action::make('workflow_approve')
                    ->label(fn (Bid $r) =>
                        '✓ Approve — ' .
                        (\App\Services\Procurement\ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'bid')?->stage_name ?? 'Approve')
                    )
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Bid $r) =>
                        $r->status === 'Pending Approval'
                        && \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('bid')
                        && \App\Services\Procurement\ProcurementApprovalService::canApprove(auth()->user(), $r, 'bid')
                    )
                    ->requiresConfirmation()
                    ->form([\Filament\Forms\Components\Textarea::make('notes')->label('Approval Remarks (optional)')->rows(3)->nullable()])
                    ->action(function (Bid $r, array $data) {
                        $user    = auth()->user();
                        $pending = \App\Services\Procurement\ProcurementApprovalService::pendingRecordFor($user, $r, 'bid');
                        if (! $pending) return;

                        \App\Services\Procurement\ProcurementApprovalService::approve($r, 'bid', $pending->stage_order, $user, $data['notes'] ?? null);

                        if (\App\Services\Procurement\ProcurementApprovalService::isFullyApproved($r, 'bid')) {
                            // Award this bid
                            $r->update(['status' => 'Awarded']);
                            // Reject all other bids on the same tender
                            Bid::where('tender_id', $r->tender_id)
                                ->where('id', '!=', $r->id)
                                ->whereNotIn('status', ['Rejected'])
                                ->update(['status' => 'Rejected']);
                            // Mark the tender as awarded
                            $r->tender?->update(['status' => 'Awarded', 'award_date' => now()->toDateString()]);
                            Notification::make()->title('✓ Bid fully approved and Awarded!')->success()->send();
                        } else {
                            Notification::make()->title("✓ Approved: {$pending->stage_name} — advancing to next stage")->success()->send();
                        }
                    }),

                Action::make('workflow_reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (Bid $r) {
                        if ($r->status === 'Pending Approval' && \App\Models\Procurement\ProcurementApprovalWorkflow::activeFor('bid')) {
                            $pending = \App\Services\Procurement\ProcurementApprovalService::pendingRecordFor(auth()->user(), $r, 'bid');
                            return $pending && ($pending->stage?->can_reject ?? true);
                        }
                        return false;
                    })
                    ->requiresConfirmation()
                    ->form([\Filament\Forms\Components\Textarea::make('notes')->label('Rejection Reason')->required()->rows(3)])
                    ->action(function (Bid $r, array $data) {
                        $user    = auth()->user();
                        $pending = \App\Services\Procurement\ProcurementApprovalService::pendingRecordFor($user, $r, 'bid');
                        if (! $pending) return;

                        \App\Services\Procurement\ProcurementApprovalService::reject($r, 'bid', $pending->stage_order, $user, $data['notes'] ?? null);
                        $r->update(['status' => 'Rejected']);
                        Notification::make()->title("Bid award rejected at {$pending->stage_name}")->danger()->send();
                    }),

                // Standard Reject bid (when not in workflow)
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Bid $r) =>
                        ! in_array($r->status, ['Awarded', 'Rejected', 'Pending Approval'])
                        && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->action(fn (Bid $r) =>
                        $r->update(['status' => 'Rejected'])
                        && Notification::make()->title('Bid rejected')->danger()->send()
                    ),

                EditAction::make(),
                DeleteAction::make()->visible(fn (Bid $r) => $r->status === 'Submitted'),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBids::route('/'),
            'create' => CreateBid::route('/create'),
            'edit'   => EditBid::route('/{record}/edit'),
        ];
    }
}
