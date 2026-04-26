<?php

namespace App\Filament\Resources\Finance\Receivables;

use App\Filament\Resources\Finance\Receivables\ReferencePadBookResource\Pages\CreateReferencePadBook;
use App\Filament\Resources\Finance\Receivables\ReferencePadBookResource\Pages\EditReferencePadBook;
use App\Filament\Resources\Finance\Receivables\ReferencePadBookResource\Pages\ListReferencePadBooks;
use App\Filament\Resources\Finance\Receivables\ReferencePadBookResource\Pages\ViewReferencePadBook;
use App\Models\Finance\ReferencePadBook;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;
use App\Traits\BelongsToModule;

class ReferencePadBookResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = ReferencePadBook::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static string|UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Reference Pad Books';
    protected static ?int $navigationSort = 42;
    protected static ?string $recordTitleAttribute = 'pad_number';
    protected static bool $shouldSkipAuthorization = true;

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        if (! $u) {
            return true;
        }

        return $u->isFinanceOfficer() || $u->isFinanceManager() || $u->isSuperAdmin();
    }

    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($r): bool   { return static::canViewAny(); }
    public static function canDelete($r): bool { return static::canViewAny(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pad Book')->icon('heroicon-o-clipboard-document-list')->columns(3)->schema([
                TextInput::make('pad_number')
                    ->label('Pad Number')
                    ->required()
                    ->maxLength(50),

                Select::make('book_type')
                    ->label('Book Type')
                    ->required()
                    ->native(false)
                    ->options([
                        'crv' => 'CRV',
                        'pv' => 'PV',
                        'cheque' => 'Cheque',
                        'receipt' => 'Receipt',
                    ]),

                TextInput::make('prefix')
                    ->label('Prefix')
                    ->maxLength(10)
                    ->nullable(),

                TextInput::make('start_sequence')
                    ->label('Start Seq')
                    ->numeric()
                    ->required(),

                TextInput::make('end_sequence')
                    ->label('End Seq')
                    ->numeric()
                    ->required(),

                TextInput::make('current_sequence')
                    ->label('Current Seq')
                    ->numeric()
                    ->required(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pad_number')->label('Pad #')->badge()->color('primary')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('book_type')->label('Type')->badge()->color('gray'),
                TextColumn::make('prefix')->label('Prefix')->placeholder('—')->fontFamily('mono'),
                TextColumn::make('start_sequence')->label('Start')->alignEnd()->fontFamily('mono'),
                TextColumn::make('end_sequence')->label('End')->alignEnd()->fontFamily('mono'),
                TextColumn::make('current_sequence')->label('Current')->alignEnd()->fontFamily('mono')->weight('semibold'),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('book_type')->label('Type')->options([
                    'crv' => 'CRV',
                    'pv' => 'PV',
                    'cheque' => 'Cheque',
                    'receipt' => 'Receipt',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('pad_number');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListReferencePadBooks::route('/'),
            'create' => CreateReferencePadBook::route('/create'),
            'view'   => ViewReferencePadBook::route('/{record}'),
            'edit'   => EditReferencePadBook::route('/{record}/edit'),
        ];
    }
}
