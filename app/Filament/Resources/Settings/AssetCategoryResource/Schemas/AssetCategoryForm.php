<?php

namespace App\Filament\Resources\Settings\AssetCategoryResource\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AssetCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1])
            ->components([
                \Filament\Schemas\Components\Section::make('Category Details')
                    ->columns(['default' => 2])
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('useful_life')
                            ->numeric(),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
