<?php

namespace App\Filament\Resources\HR\VehicleMaintenanceRecords;

use App\Models\Asset;
use App\Models\HrSettingOption;
use App\Models\VehicleMaintenanceRecord;
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

class VehicleMaintenanceRecordResource extends Resource
{
    protected static ?string $model = VehicleMaintenanceRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationParentItem = 'Car & Rent Management';

    protected static ?string $navigationLabel = 'Vehicle Maintenance';

    protected static ?int $navigationSort = 34;

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

            Select::make('service_request_id')
                ->label('Service Request')
                ->relationship('serviceRequest', 'id')
                ->searchable()
                ->nullable(),

            Select::make('service_type_option_id')
                ->label('Service Type')
                ->options(fn () => HrSettingOption::optionsFor('vehicle_service_type'))
                ->nullable(),

            Select::make('provider_option_id')
                ->label('Service Provider')
                ->options(fn () => HrSettingOption::optionsFor('service_provider'))
                ->nullable(),

            DatePicker::make('service_date')->required(),
            TextInput::make('odometer_km')->numeric()->nullable(),
            TextInput::make('cost')->numeric()->prefix('ETB')->default(0),
            TextInput::make('next_service_odometer')->numeric()->nullable(),
            DatePicker::make('next_service_date')->nullable(),

            FileUpload::make('report_path')
                ->disk('local')
                ->directory('hr/vehicle-maintenance')
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
                TextColumn::make('serviceType.label')->label('Service Type'),
                TextColumn::make('provider.label')->label('Provider')->toggleable(),
                TextColumn::make('service_date')->date()->sortable(),
                TextColumn::make('cost')->money('ETB', true),
                TextColumn::make('next_service_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state && now()->diffInDays($state, false) <= 30 ? 'warning' : null),
            ])
            ->filters([
                SelectFilter::make('service_type_option_id')
                    ->label('Service Type')
                    ->options(fn () => HrSettingOption::optionsFor('vehicle_service_type')),
            ])
            ->defaultSort('service_date', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVehicleMaintenanceRecords::route('/'),
        ];
    }
}
