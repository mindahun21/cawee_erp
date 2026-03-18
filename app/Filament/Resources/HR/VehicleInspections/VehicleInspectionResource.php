<?php

namespace App\Filament\Resources\HR\VehicleInspections;

use App\Models\Asset;
use App\Models\VehicleInspection;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VehicleInspectionResource extends Resource
{
    protected static ?string $model = VehicleInspection::class;

    protected static ?string $cluster = \App\Filament\Clusters\CarRentManagement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Vehicle Inspections';

    protected static ?int $navigationSort = 36;

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

            DatePicker::make('inspection_date')->nullable(),
            DatePicker::make('inspection_expiry_date')->required(),

            Select::make('status')
                ->options([
                    'Valid' => 'Valid',
                    'Expiring' => 'Expiring',
                    'Expired' => 'Expired',
                ])
                ->default('Valid')
                ->required(),

            FileUpload::make('certificate_path')
                ->label('Inspection Certificate')
                ->disk('local')
                ->directory('hr/vehicle-inspections')
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
                TextColumn::make('inspection_date')->date()->placeholder('-'),
                TextColumn::make('inspection_expiry_date')->date()->sortable(),
                TextColumn::make('days_until_expiry')
                    ->label('Days Left')
                    ->badge()
                    ->colors([
                        'danger' => static fn ($state) => $state < 0 || $state <= 7,
                        'warning' => static fn ($state) => $state <= 30,
                        'info' => static fn ($state) => $state <= 60,
                        'success' => static fn ($state) => $state > 60,
                    ]),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'Valid',
                        'warning' => 'Expiring',
                        'danger' => 'Expired',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'Valid' => 'Valid',
                    'Expiring' => 'Expiring',
                    'Expired' => 'Expired',
                ]),
            ])
            ->defaultSort('inspection_expiry_date')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVehicleInspections::route('/'),
        ];
    }
}
