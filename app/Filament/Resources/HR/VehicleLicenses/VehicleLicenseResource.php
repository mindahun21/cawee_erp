<?php

namespace App\Filament\Resources\HR\VehicleLicenses;

use App\Models\Asset;
use App\Models\VehicleLicense;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VehicleLicenseResource extends Resource
{
    protected static ?string $model = VehicleLicense::class;

    protected static ?string $cluster = \App\Filament\Clusters\CarRentManagement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Vehicle Bolo Licenses';

    protected static ?int $navigationSort = 35;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('asset_id')
                ->label('Vehicle')
                ->relationship(
                    name: 'vehicle',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn ($query) => $query->whereHas('vehicleDetail')
                )
                ->getOptionLabelFromRecordUsing(
                    fn (Asset $record) => $record->name . ' - ' . ($record->vehicleDetail?->plate_number ?? 'No Plate')
                )
                ->searchable()
                ->required(),

            TextInput::make('license_number')->maxLength(100),
            DatePicker::make('bolo_issue_date')->nullable(),
            DatePicker::make('bolo_expiry_date')->required(),

            Select::make('status')
                ->options([
                    'Valid' => 'Valid',
                    'Expiring' => 'Expiring',
                    'Expired' => 'Expired',
                ])
                ->default('Valid')
                ->required(),

            FileUpload::make('receipt_path')
                ->label('Bolo Receipt')
                ->disk('local')
                ->directory('hr/vehicle-licenses')
                ->nullable(),

            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vehicle.name')->label('Vehicle')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('vehicle.vehicleDetail.plate_number')->label('Plate')->searchable(),
                TextColumn::make('license_number')->label('License #')->searchable(),
                TextColumn::make('bolo_expiry_date')->date()->sortable(),
                TextColumn::make('days_until_expiry')
                    ->label('Days Left')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state < 0 => 'danger',
                        $state <= 7 => 'danger',
                        $state <= 30 => 'warning',
                        $state <= 60 => 'info',
                        default => 'success',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Valid' => 'success',
                        'Expiring' => 'warning',
                        default => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'Valid' => 'Valid',
                    'Expiring' => 'Expiring',
                    'Expired' => 'Expired',
                ]),
            ])
            ->defaultSort('bolo_expiry_date')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVehicleLicenses::route('/'),
        ];
    }
}
