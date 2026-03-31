<?php

namespace App\Filament\Resources\FileSharing;

use App\Filament\Resources\FileSharing\SharedFileResource\Pages\ManageSharedFiles;
use App\Filament\Resources\FileSharing\SharedFileResource\Schemas\SharedFileForm;
use App\Filament\Resources\FileSharing\SharedFileResource\Tables\SharedFilesTable;
use App\Models\SharedFile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SharedFileResource extends Resource
{
    protected static ?string $model = SharedFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'File Sharing';

    protected static ?string $navigationLabel = 'Files';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Schema $schema): Schema
    {
        return SharedFileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SharedFilesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSharedFiles::route('/'),
        ];
    }
}
