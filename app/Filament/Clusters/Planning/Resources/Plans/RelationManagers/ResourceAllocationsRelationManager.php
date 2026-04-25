<?php

namespace App\Filament\Clusters\Planning\Resources\Plans\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class ResourceAllocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'resourceAllocations';

    protected static ?string $title = 'Allocated Resources';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('resourceable_type')
                    ->label('Resource Type')
                    ->options([
                        'App\Models\Employee' => 'Staff / Personnel',
                        'App\Models\Item' => 'Material / Equipment',
                        'App\Models\Procurement\ProcurementBudget' => 'Financing / Budget',
                    ])
                    ->required()
                    ->reactive(),
                Forms\Components\Select::make('resourceable_id')
                    ->label('Resource')
                    ->options(function ($get) {
                        $type = $get('resourceable_type');
                        if (!$type) return [];
                        
                        return $type::all()->pluck('name', 'id')->toArray();
                        // Note: Some models use 'title' or 'first_name', I should be careful.
                    })
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->default(1),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('notes')
            ->columns([
                Tables\Columns\TextColumn::make('resourceable_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'App\Models\Employee' => 'Personnel',
                        'App\Models\Item' => 'Material',
                        'App\Models\Procurement\ProcurementBudget' => 'Budget',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric(),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(30),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
