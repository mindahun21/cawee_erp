<?php

namespace App\Filament\Resources\FileSharing;

use App\Filament\Resources\FileSharing\FileSharingSettingResource\Pages\ManageFileSharingSettings;
use App\Models\FileSharingSetting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class FileSharingSettingResource extends Resource
{
    protected static ?string $model = FileSharingSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'File Sharing';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'label';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')
                ->disabled()
                ->dehydrated(false),
            TextInput::make('key')
                ->disabled()
                ->dehydrated(false),
            TextInput::make('data_type')
                ->label('Data Type')
                ->disabled()
                ->dehydrated(false),
            Textarea::make('value')
                ->label('Value')
                ->rows(3)
                ->required()
                ->helperText('Use JSON array for json fields (example: ["pdf","docx"]). Use true/false for boolean fields.'),
            Textarea::make('description')
                ->disabled()
                ->dehydrated(false)
                ->rows(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => FileSharingSetting::groups()[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('label')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('key')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('value')
                    ->fontFamily('mono')
                    ->limit(70)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options(FileSharingSetting::groups()),
            ])
            ->recordActions([
                EditAction::make()->label('Edit Value'),
            ])
            ->defaultSort('group')
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFileSharingSettings::route('/'),
        ];
    }
}
