<?php

namespace App\Filament\Resources\Finance\Payables;

use App\Filament\Resources\Finance\Payables\Pages\ListApprovalHistories;
use App\Models\Finance\ApprovalHistory;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * ApprovalHistoryResource
 *
 * Read-only aggregated view of ALL finance document approval actions.
 * Polymorphic — can link to PaymentRequisitions, PaymentVouchers, CRVs, etc.
 * Acts as a compliance audit timeline across the entire Finance module.
 */
class ApprovalHistoryResource extends Resource
{
    protected static ?string $model                           = ApprovalHistory::class;
    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-clipboard-document-check';
    protected static string|UnitEnum|null $navigationGroup   = 'Finance';
    protected static ?string $navigationLabel                 = 'Approval History';
    protected static ?int    $navigationSort                  = 61;
    protected static ?string $slug                            = 'finance/approval-histories';
    protected static bool $shouldSkipAuthorization            = true;

    // Read-only — Finance staff can view, nobody can create/edit/delete
    public static function canViewAny(): bool   { $u = auth()->user(); return $u && ($u->isFinanceOfficer() || $u->isFinanceManager() || $u->isSuperAdmin()); }
    public static function canView($r): bool    { return static::canViewAny(); }
    public static function canCreate(): bool    { return false; }
    public static function canEdit($r): bool    { return false; }
    public static function canDelete($r): bool  { return false; }

    // ── Table ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('actioned_at')
                    ->label('Date & Time')
                    ->dateTime('d M Y  H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('actor.name')
                    ->label('By')
                    ->searchable()
                    ->icon('heroicon-o-user-circle')
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state) => match($state) {
                        'approved'  => 'success',
                        'rejected'  => 'danger',
                        'returned'  => 'warning',
                        'noted'     => 'info',
                        'forwarded' => 'gray',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn ($s) => ucfirst($s)),

                TextColumn::make('stage_name')
                    ->label('Stage')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('approvable_type')
                    ->label('Document Type')
                    ->formatStateUsing(fn ($s) => class_basename($s))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('approvable_id')
                    ->label('Doc #')
                    // Resolve reference number from polymorphic relation dynamically
                    ->state(function (ApprovalHistory $record): string {
                        $doc = $record->approvable;
                        if (! $doc) return "#{$record->approvable_id}";
                        return $doc->pr_number
                            ?? $doc->pv_number
                            ?? $doc->crv_number
                            ?? $doc->reference_number
                            ?? $doc->reference
                            ?? "#{$record->approvable_id}";
                    })
                    ->badge()
                    ->color('primary')
                    ->fontFamily('mono'),

                TextColumn::make('previous_status')
                    ->label('From')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($s) => ucfirst(str_replace('_', ' ', $s ?? '—'))),

                TextColumn::make('new_status')
                    ->label('To')
                    ->badge()
                    ->color(fn (string $state) => match($state) {
                        'approved'         => 'success',
                        'rejected'         => 'danger',
                        'pending_approval' => 'warning',
                        'draft'            => 'gray',
                        'posted'           => 'info',
                        default            => 'gray',
                    })
                    ->formatStateUsing(fn ($s) => ucfirst(str_replace('_', ' ', $s ?? '—'))),

                TextColumn::make('comments')
                    ->label('Comments')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options(ApprovalHistory::actions()),

                SelectFilter::make('approvable_type')
                    ->label('Document Type')
                    ->options([
                        'App\Models\Finance\PaymentRequisition' => 'Payment Requisition',
                        'App\Models\Finance\PaymentVoucher'     => 'Payment Voucher',
                        'App\Models\Finance\CashReceiptVoucher' => 'Cash Receipt Voucher',
                        'App\Models\Finance\IncomeRegister'     => 'Income Register',
                        'App\Models\Finance\Loan'               => 'Loan',
                    ]),

                Filter::make('today')
                    ->label('Today Only')
                    ->query(fn (Builder $q) => $q->whereDate('actioned_at', today())),

                Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $q) => $q->whereBetween('actioned_at', [
                        now()->startOfWeek(), now()->endOfWeek(),
                    ])),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('actioned_at', 'desc')
            ->striped()
            ->poll('60s'); // Auto-refresh every minute for real-time approval monitoring
    }

    // ── Infolist ──────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Approval Action')
                ->icon('heroicon-o-clipboard-document-check')
                ->columns(3)
                ->schema([
                    TextEntry::make('action')
                        ->label('Action')
                        ->badge()
                        ->color(fn ($s) => match($s) {
                            'approved' => 'success', 'rejected' => 'danger',
                            'returned' => 'warning', default => 'gray',
                        }),
                    TextEntry::make('stage_name')->label('Approval Stage'),
                    TextEntry::make('stage_number')->label('Stage #')->badge()->color('gray'),
                    TextEntry::make('actor.name')->label('Actioned By')
                        ->icon('heroicon-o-user-circle'),
                    TextEntry::make('actioned_at')->label('Date & Time')->dateTime(),
                    TextEntry::make('previous_status')->label('Status Before')
                        ->badge()->color('gray')
                        ->formatStateUsing(fn ($s) => ucfirst(str_replace('_', ' ', $s ?? '—'))),
                    TextEntry::make('new_status')->label('Status After')
                        ->badge()
                        ->color(fn ($s) => match($s) {
                            'approved' => 'success', 'rejected' => 'danger',
                            'pending_approval' => 'warning', default => 'info',
                        })
                        ->formatStateUsing(fn ($s) => ucfirst(str_replace('_', ' ', $s ?? '—'))),
                ]),

            Section::make('Document Reference')
                ->icon('heroicon-o-document-text')
                ->columns(2)
                ->schema([
                    TextEntry::make('approvable_type')
                        ->label('Document Type')
                        ->formatStateUsing(fn ($s) => class_basename($s)),
                    TextEntry::make('approvable_id')
                        ->label('Document Ref')
                        ->state(function (ApprovalHistory $record): string {
                            $doc = $record->approvable;
                            if (! $doc) return "#{$record->approvable_id}";
                            return $doc->pr_number
                                ?? $doc->pv_number
                                ?? $doc->crv_number
                                ?? $doc->reference_number
                                ?? $doc->reference
                                ?? "#{$record->approvable_id}";
                        })
                        ->badge()->color('primary')->fontFamily('mono'),
                ]),

            Section::make('Comments')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->schema([
                    TextEntry::make('comments')->hiddenLabel()->placeholder('No comments provided.'),
                ])
                ->collapsible()
                ->compact(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApprovalHistories::route('/'),
        ];
    }
}
