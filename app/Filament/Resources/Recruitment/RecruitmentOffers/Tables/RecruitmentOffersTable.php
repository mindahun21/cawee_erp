<?php

namespace App\Filament\Resources\Recruitment\RecruitmentOffers\Tables;

use App\Models\Recruitment\RecruitmentOffer;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
            ])
            ->defaultSort('created_at', 'desc');
    }
}
