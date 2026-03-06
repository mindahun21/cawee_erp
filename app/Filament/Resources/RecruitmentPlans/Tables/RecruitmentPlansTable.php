<?php

namespace App\Filament\Resources\RecruitmentPlans\Tables;


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

class RecruitmentPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan_name')->label('Plan Name')->sortable()->searchable(),
                TextColumn::make('position')->label('Position')->sortable(),
                TextColumn::make('working_from')->label('Working From')->sortable(),
                TextColumn::make('department')->label('Department')->sortable(),
                TextColumn::make('quantity')->label('Quantity to be recruited')->sortable()->searchable(),
                TextColumn::make('status')->label('Status')->sortable(),
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
