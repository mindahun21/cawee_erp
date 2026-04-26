<?php

namespace App\Filament\Widgets\Recruitment;

use App\Traits\BelongsToModuleWidget;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Recruitment\RecruitmentPlan;

class LatestRecruitmentPlansWidget extends BaseWidget
{
    use BelongsToModuleWidget;

    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Latest Recruitment Plans';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RecruitmentPlan::query()
                    ->with(['department', 'jobPosition'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('jobPosition.title')
                    ->label('Job Position')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('vacancies_needed')
                    ->label('Vacancies')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => RecruitmentPlan::statusColor($state))
                    ->formatStateUsing(fn (string $state): string => RecruitmentPlan::statusLabel($state)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created On')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated(false)
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn (RecruitmentPlan $record): string => \App\Filament\Resources\Recruitment\RecruitmentPlans\RecruitmentPlanResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
