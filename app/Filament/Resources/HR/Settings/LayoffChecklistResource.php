<?php

namespace App\Filament\Resources\HR\Settings;

use App\Models\LayoffChecklistItem;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LayoffChecklistResource extends Resource
{
    protected static ?string $model = LayoffChecklistItem::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationParentItem = 'HR Settings';

    protected static ?string $navigationLabel = 'Layoff Checklist';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                Select::make('responsible_party')
                    ->options([
                        'HR'          => 'HR Department',
                        'Finance'     => 'Finance',
                        'IT'          => 'IT Department',
                        'Manager'     => 'Direct Manager',
                        'Employee'    => 'Employee',
                        'Legal'       => 'Legal',
                    ])
                    ->nullable(),

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->label('Sort Order'),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable()->alignCenter(),
                TextColumn::make('title')->searchable()->weight('semibold'),
                TextColumn::make('responsible_party')->badge()->color('info'),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageLayoffChecklist::route('/')];
    }
}
