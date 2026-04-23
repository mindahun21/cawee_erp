<?php

namespace App\Filament\Resources\FileSharing;

use App\Filament\Resources\FileSharing\FileShareResource\Pages\ManageFileShares;
use App\Filament\Resources\FileSharing\FileShareResource\Schemas\FileShareForm;
use App\Filament\Resources\FileSharing\FileShareResource\Tables\FileSharesTable;
use App\Models\FileShare;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FileShareResource extends Resource
{
    protected static ?string $model = FileShare::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;

    protected static string|UnitEnum|null $navigationGroup = 'File Sharing';

    protected static ?string $navigationLabel = 'Shares';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'share_token';

    public static function form(Schema $schema): Schema
    {
        return FileShareForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FileSharesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFileShares::route('/'),
        ];
    }
}
