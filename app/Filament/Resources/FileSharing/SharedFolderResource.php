<?php

namespace App\Filament\Resources\FileSharing;

use App\Filament\Resources\FileSharing\SharedFolderResource\Pages\ManageSharedFolders;
use App\Filament\Resources\FileSharing\SharedFolderResource\Schemas\SharedFolderForm;
use App\Filament\Resources\FileSharing\SharedFolderResource\Tables\SharedFoldersTable;
use App\Models\SharedFolder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use App\Traits\BelongsToModule;

class SharedFolderResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = SharedFolder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'File Sharing';

    protected static ?string $navigationLabel = 'Folders';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SharedFolderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SharedFoldersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSharedFolders::route('/'),
        ];
    }
}
