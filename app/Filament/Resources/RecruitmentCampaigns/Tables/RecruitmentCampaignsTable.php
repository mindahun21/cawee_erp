<?php

namespace App\Filament\Resources\RecruitmentCampaigns\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\NumberColumn;
use Filament\Tables\Columns\DateColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\TrashedFilter;
class RecruitmentCampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('campaign_code')->label('Campaign Code')->sortable()->searchable(),
                TextColumn::make('campaign_name')->label('Campaign Name')->sortable()->searchable(),
                TextColumn::make('plan_name')->label('Recruitment Plan')->sortable()->searchable(),
                TextColumn::make('position')->label('Position')->sortable(),
                // NumberColumn::make('quantity')->label('Quantity')->sortable(),
                TextColumn::make('department')->label('Department')->sortable(),
                TextColumn::make('workplace')->label('Workplace')->sortable(),
                TextColumn::make('manager.name')->label('Manager')->sortable()->searchable(),
                TextColumn::make('follower.name')->label('Follower')->sortable()->searchable(),
                // DateColumn::make('from_date')->label('From Date')->sortable(),
                // DateColumn::make('to_date')->label('To Date')->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
