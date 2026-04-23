<?php

namespace App\Filament\Resources\Recruitment\RecruitmentOffers\Tables;

use App\Models\Recruitment\RecruitmentOffer;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Forms\Components\DatePicker;
use App\Filament\Helpers\ExportHelper;
use Filament\Tables\Table;

class RecruitmentOffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('application.candidate.first_name')
                    ->label('Candidate')
                    ->formatStateUsing(fn ($record) =>
                        ($record->application?->candidate?->first_name ?? '') . ' ' .
                        ($record->application?->candidate?->last_name ?? '')
                    )
                    ->searchable(query: fn ($query, $search) =>
                        $query->whereHas('application.candidate', fn ($q) =>
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                        )
                    )
                    ->weight('bold'),

                TextColumn::make('application.campaign.title')
                    ->label('Campaign')
                    ->limit(30),

                TextColumn::make('offered_salary')
                    ->label('Offered Salary')
                    ->formatStateUsing(fn ($state) => $state ? 'ETB ' . number_format($state, 2) : '—'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'     => 'gray',
                        'submitted' => 'warning',
                        'approved'  => 'success',
                        'accepted'  => 'success',
                        'declined'  => 'danger',
                        'expired'   => 'danger',
                        'withdrawn' => 'gray',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                TextColumn::make('offer_date')->label('Offer Date')->date()->sortable(),

                TextColumn::make('offer_expiry_date')
                    ->label('Expiry Date')
                    ->date()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null)
                    ->placeholder('—'),

                TextColumn::make('issuer.name')->label('Issued By')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'submitted' => 'Pending Approval',
                        'approved'  => 'Approved',
                        'accepted'  => 'Accepted',
                        'declined'  => 'Declined',
                        'expired'   => 'Expired',
                        'withdrawn' => 'Withdrawn',
                    ]),
                SelectFilter::make('campaign_id')
                    ->label('Campaign')
                    ->relationship('application.campaign', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('issuer_id')
                    ->label('Issued By')
                    ->relationship('issuer', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('offer_date')
                    ->form([
                        DatePicker::make('offer_from')
                            ->label('Offer Date From'),
                        DatePicker::make('offer_until')
                            ->label('Offer Date Until'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when($data['offer_from'], fn ($q, $date) => $q->whereDate('offer_date', '>=', $date))
                            ->when($data['offer_until'], fn ($q, $date) => $q->whereDate('offer_date', '<=', $date));
                    }),
            ])
            ->filtersFormColumns(2)
            ->defaultSort('created_at', 'desc')
            ->bulkActions([
                ExportHelper::makeBulkAction('export'),
                \Filament\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('Delete:RecruitmentOffer'))
                    ->requiresConfirmation()
                    ->modalHeading('Delete Selected Offers')
                    ->modalDescription('Are you sure you want to delete the selected offers?')
                    ->modalSubmitActionLabel('Yes, delete them')
                    ->deselectRecordsAfterCompletion(),
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
