<?php

namespace App\Filament\Resources\VehicleManagement\VehicleMaintenanceRecords;

use App\Models\Asset;
use App\Models\VehicleSetting;
use App\Models\VehicleMaintenanceRecord;
use BackedEnum;
use UnitEnum;
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
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Tables\Table;

class VehicleMaintenanceRecordResource extends Resource
{
    protected static ?string $model = VehicleMaintenanceRecord::class;

    protected static string|UnitEnum|null $navigationGroup = 'Vehicle Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Vehicle Maintenance';

    protected static ?int $navigationSort = 34;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('vehicle_id')
                ->label('Vehicle')
                ->relationship('vehicle', 'plate_number')
                ->getOptionLabelFromRecordUsing(
                    fn (\App\Models\Vehicle $record) => $record->plate_number . ' — ' . trim("{$record->manufacturer} {$record->model}")
                )
                ->searchable()
                ->preload()
                ->live()
                ->disabled(fn (Get $get) => filled($get('service_request_id')))
                ->dehydrated()
                ->required(),

            Select::make('service_request_id')
                ->label('Service Request')
                ->relationship(
                    name: 'serviceRequest',
                    titleAttribute: 'id',
                    modifyQueryUsing: fn (Builder $query, Get $get) => $query->when(
                        $get('vehicle_id'),
                        fn ($q, $vId) => $q->where('vehicle_id', $vId)
                    )
                )
                ->getOptionLabelFromRecordUsing(fn (\App\Models\VehicleServiceRequest $record) =>
                    ($record->vehicle?->plate_number ?? 'Unknown Vehicle') . ' — ' . \Illuminate\Support\Str::limit($record->problem_description, 40) . ' (' . $record->status . ')'
                )
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function ($state, Set $set) {
                    if (!$state) return;
                    $request = \App\Models\VehicleServiceRequest::find($state);
                    if ($request && $request->vehicle_id) {
                        $set('vehicle_id', $request->vehicle_id);
                    }
                })
                ->nullable(),

            Select::make('service_type_option_id')
                ->label('Service Type')
                ->relationship(
                    name: 'serviceType',
                    titleAttribute: 'label',
                    modifyQueryUsing: fn ($query) => $query->where('category', 'vehicle_service_type')->where('is_active', true)
                )
                ->createOptionForm([
                    TextInput::make('label')->required(),
                    TextInput::make('code')->required()->default(fn() => Str::random(8)),
                    Hidden::make('category')->default('vehicle_service_type'),
                ])
                ->nullable(),

            Select::make('provider_option_id')
                ->label('Service Provider')
                ->relationship(
                    name: 'provider',
                    titleAttribute: 'label',
                    modifyQueryUsing: fn ($query) => $query->where('category', 'service_provider')->where('is_active', true)
                )
                ->createOptionForm([
                    TextInput::make('label')->required(),
                    TextInput::make('code')->required()->default(fn() => Str::random(8)),
                    Hidden::make('category')->default('service_provider'),
                ])
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
                TextColumn::make('vehicle.plate_number')->label('Plate')->searchable()->sortable(),
                TextColumn::make('vehicle.model')->label('Model'),
                TextColumn::make('serviceType.label')->label('Service Type'),
                TextColumn::make('provider.label')->label('Provider')->toggleable(),
                TextColumn::make('service_date')->date()->sortable(),
                TextColumn::make('cost')->money('ETB', true),
                TextColumn::make('next_service_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state && now()->diffInDays($state, false) <= 30 ? 'warning' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('service_type_option_id')
                    ->label('Service Type')
                    ->options(VehicleSetting::optionsFor('vehicle_service_type')),
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
