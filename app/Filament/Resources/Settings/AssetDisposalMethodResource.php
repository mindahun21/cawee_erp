<?php

namespace App\Filament\Resources\Settings;

use App\Models\AssetDisposalMethod;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;

class AssetDisposalMethodResource extends Resource
{
    protected static string|null $cluster = \App\Filament\Clusters\Settings::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-trash';

    protected static ?string $navigationLabel = 'Disposal Methods';

    protected static ?int $navigationSort = 120;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Settings\AssetDisposalMethodResource\Pages\ManageAssetDisposalMethods::route('/'),
        ];
    }
}
