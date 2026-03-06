<?php

namespace App\Filament\Resources\Procurement\Bids;

use App\Filament\Resources\Procurement\Bids\Pages\CreateBid;
use App\Filament\Resources\Procurement\Bids\Pages\EditBid;
use App\Filament\Resources\Procurement\Bids\Pages\ListBids;
use App\Models\Procurement\Bid;
use BackedEnum;
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

class BidResource extends Resource
{
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
                    ->default(now()->toDateString()),

                TextInput::make('bid_amount')
                    ->numeric()
                    ->prefix('ETB')
                    ->nullable(),

                TextInput::make('currency')
                    ->default('ETB')
                    ->maxLength(10),

                TextInput::make('delivery_days')
                    ->numeric()
                    ->label('Delivery Period (days)')
                    ->nullable(),

                DatePicker::make('validity_date')
                    ->label('Bid Validity Until')
                    ->nullable(),

                Checkbox::make('conflict_of_interest_declared')
                    ->label('Conflict of Interest Declared')
                    ->helperText('Check if any evaluation committee member has declared a conflict of interest with this supplier.'),

                Select::make('status')
                    ->options([
                        'Submitted'    => 'Submitted',
                        'Under Review' => 'Under Review',
                        'Shortlisted'  => 'Shortlisted',
                        'Awarded'      => 'Awarded',
                        'Rejected'     => 'Rejected',
                    ])
                    ->default('Submitted'),

                Textarea::make('notes')->rows(3)->columnSpanFull()->nullable(),

                FileUpload::make('attachments')
                    ->label('Bid Documents')
                    ->multiple()
                    ->disk('local')
                    ->directory('procurement/bids')
                    ->nullable()
                    ->columnSpanFull(),
            ]),

            // ── Evaluation Scores ──────────────────────────────────────
            Section::make('Evaluation Scores')
                ->description('Scores are entered during the evaluation phase by committee members.')
                ->columns(3)
                ->schema([
                    TextInput::make('technical_score')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('/100')
                        ->label('Technical Score')
                        ->nullable(),

                    TextInput::make('financial_score')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('/100')
                        ->label('Financial Score')
                        ->nullable(),

                    TextInput::make('composite_score')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('/100')
                        ->label('Composite Score')
                        ->nullable()
                        ->helperText('Auto-calculated or manually set weighted score.'),
                ])
                ->collapsible(),
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

                TextColumn::make('bid_amount')
                    ->label('Bid Amount (ETB)')
                    ->numeric(2)
                    ->prefix('ETB ')
                    ->sortable(),

                TextColumn::make('submission_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('validity_date')
                    ->label('Valid Until')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('technical_score')
                    ->label('Tech. Score')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null     => 'gray',
                        $state >= 80        => 'success',
                        $state >= 60        => 'warning',
                        default             => 'danger',
                    })
                    ->suffix('/100')
                    ->placeholder('—'),

                TextColumn::make('financial_score')
                    ->label('Fin. Score')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state >= 80   => 'success',
                        $state >= 60   => 'warning',
                        default        => 'danger',
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

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Awarded'      => 'success',
                        'Shortlisted'  => 'info',
                        'Under Review' => 'warning',
                        'Rejected'     => 'danger',
                        default        => 'gray',
                    }),
            ])
            ->defaultSort('submission_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Submitted'    => 'Submitted',
                        'Under Review' => 'Under Review',
                        'Shortlisted'  => 'Shortlisted',
                        'Awarded'      => 'Awarded',
                        'Rejected'     => 'Rejected',
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

                // Update evaluation scores
                Action::make('score')
                    ->label('Enter Scores')
                    ->icon('heroicon-o-calculator')
                    ->color('primary')
                    ->visible(fn (Bid $r) =>
                        in_array($r->status, ['Submitted', 'Under Review', 'Shortlisted'])
                        && auth()->user()->isProcurementEvaluator()
                    )
                    ->form([
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
                        Textarea::make('evaluation_notes')
                            ->label('Evaluation Notes')
                            ->rows(3)->nullable(),
                    ])
                    ->action(function (Bid $r, array $data) {
                        $composite = $data['composite_score']
                            ?? round(((float)$data['technical_score'] + (float)$data['financial_score']) / 2, 2);

                        $r->update([
                            'technical_score'  => $data['technical_score'],
                            'financial_score'  => $data['financial_score'],
                            'composite_score'  => $composite,
                            'status'           => 'Under Review',
                            'notes'            => $data['evaluation_notes'] ?? $r->notes,
                        ]);
                        Notification::make()->title('Evaluation scores saved')->success()->send();
                    }),

                // Award this bid
                Action::make('award')
                    ->label('Award Bid')
                    ->icon('heroicon-o-trophy')
                    ->color('success')
                    ->visible(fn (Bid $r) =>
                        in_array($r->status, ['Shortlisted', 'Under Review'])
                        && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Award Bid to Supplier')
                    ->modalDescription('This will mark this bid as awarded and the supplier as the selected vendor. All other bids on this tender will be marked rejected.')
                    ->modalSubmitActionLabel('Award')
                    ->action(function (Bid $r) {
                        // Award this bid
                        $r->update(['status' => 'Awarded']);
                        // Reject all other bids on the same tender
                        Bid::where('tender_id', $r->tender_id)
                            ->where('id', '!=', $r->id)
                            ->whereNotIn('status', ['Rejected'])
                            ->update(['status' => 'Rejected']);
                        // Mark the tender as awarded
                        $r->tender?->update(['status' => 'Awarded', 'award_date' => now()->toDateString()]);
                        Notification::make()->title('🏆 Bid awarded — generate PO to proceed')->success()->send();
                    }),

                // Reject bid
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Bid $r) =>
                        ! in_array($r->status, ['Awarded', 'Rejected'])
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
