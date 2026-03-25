<?php

namespace App\Filament\Resources\Recruitment\RecruitmentApplications\Schemas;

use App\Models\Recruitment\RecruitmentApplication;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class RecruitmentApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Select::make('candidate_id')
                        ->relationship('candidate', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                        ->searchable(['first_name', 'last_name'])
                        ->preload()
                        ->required(),
                    Select::make('campaign_id')
                        ->relationship('campaign', 'title')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('channel_id')
                        ->relationship('channel', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('status')
                        ->options([
                            RecruitmentApplication::STATUS_APPLIED             => 'Applied',
                            RecruitmentApplication::STATUS_UNDER_REVIEW        => 'Under Review',
                            RecruitmentApplication::STATUS_SHORTLISTED         => 'Shortlisted',
                            RecruitmentApplication::STATUS_INTERVIEW_SCHEDULED => 'Interview Scheduled',
                            RecruitmentApplication::STATUS_OFFER_PENDING       => 'Offer Pending',
                            RecruitmentApplication::STATUS_OFFER_ACCEPTED      => 'Offer Accepted',
                            RecruitmentApplication::STATUS_OFFER_DECLINED      => 'Offer Declined',
                            RecruitmentApplication::STATUS_HIRED               => 'Hired',
                            RecruitmentApplication::STATUS_REJECTED            => 'Rejected',
                            RecruitmentApplication::STATUS_WITHDRAWN           => 'Withdrawn',
                        ])
                        ->required()
                        ->default(RecruitmentApplication::STATUS_APPLIED),
                ]),

                RichEditor::make('cover_letter')
                    ->columnSpanFull(),

                Hidden::make('applied_at')
                    ->default(fn () => now()),
            ]);
    }
}
