<?php

namespace App\Filament\Resources\FileSharing;

use App\Filament\Resources\FileSharing\FileAccessLogResource\Pages\ManageFileAccessLogs;
use App\Filament\Resources\FileSharing\FileAccessLogResource\Tables\FileAccessLogsTable;
use App\Models\FileAccessLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use App\Traits\BelongsToModule;

class FileAccessLogResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = FileAccessLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'File Sharing';

    protected static ?string $navigationLabel = 'Access Logs';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return FileAccessLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFileAccessLogs::route('/'),
        ];
    }
}
