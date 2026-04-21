<?php

namespace App\Filament\Resources\Finance\Perdiem;

use App\Models\Finance\PerdiemRequestExtension;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PerdiemRequestExtensionResource extends Resource
{
    protected static ?string $model = PerdiemRequestExtension::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string|\UnitEnum|null $navigationGroup = 'Finance / Per Diem';
    protected static ?string $navigationLabel = 'Request Extensions';
    protected static ?string $slug = 'finance/perdiem/extensions';
    protected static bool $shouldSkipAuthorization = true;

    public static function canViewAny(): bool { return true; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Extension Details')->columns(2)->schema([
                Select::make('perdiem_request_id')->relationship('perdiemRequest', 'reference')->searchable()->required()->native(false),
                DatePicker::make('extension_date')->required()->default(now()),
                TextInput::make('additional_days')->numeric()->required()->minValue(1),
                DatePicker::make('new_end_date')->required(),
                TextInput::make('additional_amount')->numeric()->required()->default(0),
                Textarea::make('reason')->required()->columnSpanFull(),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('perdiemRequest.reference')->label('Request Ref')->searchable()->sortable(),
                TextColumn::make('extension_date')->date()->sortable(),
                TextColumn::make('additional_days')->numeric(),
                TextColumn::make('new_end_date')->date(),
                TextColumn::make('additional_amount')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextColumn::make('status')->badge()
                    ->color(fn ($state) => match($state) { 'draft' => 'gray', 'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', default => 'gray' }),
            ])
            ->defaultSort('extension_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Finance\Perdiem\PerdiemRequestExtensionResource\Pages\ManagePerdiemRequestExtensions::route('/'),
        ];
    }
}
