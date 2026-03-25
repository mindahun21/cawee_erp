<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentApprovalWorkflows\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use App\Models\Recruitment\RecruitmentApprovalWorkflow;

class RecruitmentApprovalWorkflowsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_type')
                    ->formatStateUsing(fn ($state) => RecruitmentApprovalWorkflow::documentTypes()[$state] ?? $state)
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('stages_count')
                    ->counts('stages')
                    ->label('Stages'),
                ToggleColumn::make('is_active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
