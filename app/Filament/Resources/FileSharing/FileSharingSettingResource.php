<?php

namespace App\Filament\Resources\FileSharing;

use App\Filament\Resources\FileSharing\FileSharingSettingResource\Pages\ManageFileSharingSettings;
use App\Models\FileSharingSetting;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
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
        return true;
    }

    public static function canDelete($record): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')
                ->required()
                ->maxLength(140),
            Select::make('group')
                ->options(FileSharingSetting::groups())
                ->default('general')
                ->required(),
            TextInput::make('key')
                ->required()
                ->maxLength(100)
                ->alphaDash()
                ->unique(ignoreRecord: true)
                ->helperText('Use a stable machine-friendly key, for example: password_expiry_days'),
            Select::make('data_type')
                ->label('Data Type')
                ->options([
                    'string' => 'String',
                    'integer' => 'Integer',
                    'boolean' => 'Boolean',
                    'json' => 'List / JSON',
                ])
                ->default('string')
                ->required()
                ->live(),
            TextInput::make('value')
                ->label('Value')
                ->required()
                ->numeric()
                ->integer()
                ->minValue(0)
                ->visible(fn (Get $get): bool => $get('data_type') === 'integer')
                ->helperText('Enter a numeric value only.'),
            TextInput::make('value')
                ->label('Value')
                ->required()
                ->visible(fn (Get $get): bool => $get('data_type') === 'string')
                ->helperText('Use plain text for custom string settings.'),
            Toggle::make('value')
                ->label('Enabled')
                ->visible(fn (Get $get): bool => $get('data_type') === 'boolean')
                ->afterStateHydrated(function (Toggle $component, $state): void {
                    $component->state(filter_var($state, FILTER_VALIDATE_BOOLEAN));
                })
                ->dehydrateStateUsing(fn (bool $state): string => $state ? 'true' : 'false')
                ->helperText('Switch this policy on or off.'),
            TagsInput::make('value')
                ->label('Allowed File Types')
                ->visible(fn (Get $get): bool => $get('data_type') === 'json')
                ->afterStateHydrated(function (TagsInput $component, $state): void {
                    $decoded = json_decode((string) $state, true);

                    $component->state(is_array($decoded) ? $decoded : []);
                })
                ->dehydrateStateUsing(fn (array $state): string => json_encode(
                    collect($state)
                        ->map(fn ($ext) => strtolower(ltrim(trim((string) $ext), '.')))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all()
                ))
                ->helperText('Add extensions only, for example: pdf, docx, jpg.'),
            TextInput::make('description')
                ->columnSpanFull(),
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
                    ->formatStateUsing(function ($state, FileSharingSetting $record): string {
                        return match ($record->data_type) {
                            'boolean' => filter_var($state, FILTER_VALIDATE_BOOLEAN) ? 'Enabled' : 'Disabled',
                            'json' => implode(', ', array_filter(json_decode((string) $state, true) ?: [])),
                            default => (string) $state,
                        };
                    })
                    ->limit(70)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options(FileSharingSetting::groups()),
            ])
            ->recordActions([
                EditAction::make()->label('Edit Value'),
                DeleteAction::make(),
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
