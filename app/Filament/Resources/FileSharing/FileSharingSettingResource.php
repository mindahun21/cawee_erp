<?php

namespace App\Filament\Resources\FileSharing;

use App\Filament\Resources\FileSharing\FileSharingSettingResource\Pages\ManageFileSharingSettings;
use App\Models\FileSharingSetting;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
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
use Illuminate\Support\Str;
use UnitEnum;
use App\Traits\BelongsToModule;

class FileSharingSettingResource extends Resource
{
    use BelongsToModule;
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
                ->maxLength(140)
                ->placeholder('Password expiry days'),
            Select::make('group')
                ->options(FileSharingSetting::groups())
                ->default('general')
                ->required(),
            TextInput::make('key')
                ->required()
                ->maxLength(100)
                ->alphaDash()
                ->unique(ignoreRecord: true)
                ->formatStateUsing(fn ($state) => $state)
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::of($state)->snake()->replace(' ', '_')->toString() : null)
                ->helperText('Use a stable machine-friendly key, for example: password_expiry_days'),
            Select::make('data_type')
                ->label('Data Type')
                ->options(FileSharingSetting::dataTypes())
                ->default('string')
                ->required()
                ->live(),
            Placeholder::make('value_guidance')
                ->label('Value Guidance')
                ->content(function (Get $get): string {
                    return match ($get('data_type')) {
                        'integer' => 'Use whole numbers only, for example: 7',
                        'boolean' => 'Use the toggle to store a true/false value.',
                        'json' => 'Add a list of values, for example: pdf, docx, jpg',
                        default => 'Use plain text for custom settings, for example: employee_portal_notice',
                    };
                }),
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
                ->columnSpanFull()
                ->placeholder('Explain what this setting controls and where it is used.'),
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
                TextColumn::make('scope')
                    ->label('Scope')
                    ->badge()
                    ->state(fn (FileSharingSetting $record): string => $record->isCore() ? 'Core' : 'Custom')
                    ->color(fn (string $state): string => $state === 'Core' ? 'info' : 'gray'),
                TextColumn::make('label')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('key')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('data_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => FileSharingSetting::dataTypes()[$state] ?? $state),
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
                TextColumn::make('description')
                    ->limit(70)
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options(FileSharingSetting::groups()),
                SelectFilter::make('scope')
                    ->options([
                        'core' => 'Core settings',
                        'custom' => 'Custom settings',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'core' => $query->whereIn('key', FileSharingSetting::CORE_KEYS),
                            'custom' => $query->whereNotIn('key', FileSharingSetting::CORE_KEYS),
                            default => $query,
                        };
                    }),
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
