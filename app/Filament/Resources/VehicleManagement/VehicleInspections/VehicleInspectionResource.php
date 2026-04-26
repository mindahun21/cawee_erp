<?php

namespace App\Filament\Resources\VehicleManagement\VehicleInspections;

use App\Models\Asset;
use App\Models\VehicleInspection;
use BackedEnum;
use UnitEnum;
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
use App\Traits\BelongsToModule;

class VehicleInspectionResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = VehicleInspection::class;

    protected static string|UnitEnum|null $navigationGroup = 'Vehicle Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Vehicle Inspections';

    protected static ?int $navigationSort = 36;

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
                TextColumn::make('vehicle.plate_number')->label('Plate')->searchable()->sortable(),
                TextColumn::make('vehicle.model')->label('Model'),
                TextColumn::make('inspection_date')->date()->placeholder('-'),
                TextColumn::make('inspection_expiry_date')->date()->sortable(),
                TextColumn::make('days_until_expiry')
                    ->label('Days Left')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state < 0 || $state <= 7 => 'danger',
                        $state <= 30 => 'warning',
                        $state <= 60 => 'info',
                        $state > 60 => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'Valid' => 'success',
                        'Expiring' => 'warning',
                        'Expired' => 'danger',
                        default => 'gray',
                    }),
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
