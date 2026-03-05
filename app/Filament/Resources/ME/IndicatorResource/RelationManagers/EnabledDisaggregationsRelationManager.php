<?php

namespace App\Filament\Resources\ME\IndicatorResource\RelationManagers;

use App\Models\ME\MeDisaggregationCategory;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EnabledDisaggregationsRelationManager extends RelationManager
{
    protected static string $relationship = 'disaggregationCategories';

    protected static ?string $title = 'Enabled Disaggregation Categories';

    public function table(Table $table): Table
    {
        return $table
            ->inverseRelationship('indicators')
            ->columns([
                TextColumn::make('key')
                    ->badge(),
                TextColumn::make('name')
                    ->searchable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn ($query) => $query->orderBy('name')),
                Action::make('syncDefaults')
                    ->label('Attach Default Categories')
                    ->action(function (): void {
                        $defaultCategoryIds = MeDisaggregationCategory::query()
                            ->whereIn('key', ['gender', 'age', 'location', 'disability'])
                            ->pluck('id')
                            ->all();

                        $this->ownerRecord->disaggregationCategories()->syncWithoutDetaching($defaultCategoryIds);
                    }),
            ])
            ->recordActions([
                DetachAction::make(),
            ]);
    }
}
