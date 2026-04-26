<?php

namespace App\Filament\Resources\HR\VehicleServiceRequests;

use App\Models\Asset;
use App\Models\HrSettingOption;
use App\Models\VehicleMaintenanceRecord;
use App\Models\VehicleServiceRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use App\Traits\BelongsToModule;

class VehicleServiceRequestResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = VehicleServiceRequest::class;

    protected static ?string $cluster = \App\Filament\Clusters\CarRentManagement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Vehicle Service Requests';

    protected static ?int $navigationSort = 33;

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
                ->required(),

            Select::make('urgency_option_id')
                ->label('Urgency')
                ->relationship(
                    name: 'urgencyLevel',
                    titleAttribute: 'label',
                    modifyQueryUsing: fn ($query) => $query->where('category', 'vehicle_urgency')->where('is_active', true)
                )
                ->createOptionForm([
                    TextInput::make('label')->required(),
                    TextInput::make('code')->required()->default(fn() => Str::random(8)),
                    Hidden::make('category')->default('vehicle_urgency'),
                ])
                ->required(),

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

            Textarea::make('problem_description')->required()->rows(3)->columnSpanFull(),

            Select::make('status')
                ->options([
                    'Pending' => 'Pending',
                    'Approved' => 'Approved',
                    'In Service' => 'In Service',
                    'Completed' => 'Completed',
                    'Rejected' => 'Rejected',
                ])
                ->default('Pending')
                ->disabled()
                ->dehydrated(),

            FileUpload::make('service_report_path')
                ->label('Service Report')
                ->disk('local')
                ->directory('hr/vehicle-services')
                ->nullable(),

            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vehicle.name')->label('Vehicle')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('vehicle.plate_number')->label('Plate')->searchable()->sortable(),
                TextColumn::make('vehicle.model')->label('Model'),
                TextColumn::make('serviceType.label')->label('Service'),
                TextColumn::make('urgencyLevel.label')->label('Urgency')->badge(),
                TextColumn::make('provider.label')->label('Provider')->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'info' => 'Approved',
                        'warning' => 'In Service',
                        'success' => 'Completed',
                        'danger' => 'Rejected',
                        'gray' => 'Pending',
                    ]),
                TextColumn::make('requested_at')->since()->label('Requested'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'Pending' => 'Pending',
                    'Approved' => 'Approved',
                    'In Service' => 'In Service',
                    'Completed' => 'Completed',
                    'Rejected' => 'Rejected',
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (VehicleServiceRequest $record) => $record->status === 'Pending')
                    ->requiresConfirmation()
                    ->action(function (VehicleServiceRequest $record): void {
                        $record->update([
                            'status' => 'Approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                        Notification::make()->title('Service request approved')->success()->send();
                    }),

                Action::make('start_service')
                    ->label('Start Service')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn (VehicleServiceRequest $record) => $record->status === 'Approved')
                    ->requiresConfirmation()
                    ->action(function (VehicleServiceRequest $record): void {
                        $record->update(['status' => 'In Service']);
                        Notification::make()->title('Vehicle moved to in-service')->info()->send();
                    }),

                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (VehicleServiceRequest $record) => in_array($record->status, ['Approved', 'In Service']))
                    ->requiresConfirmation()
                    ->action(function (VehicleServiceRequest $record): void {
                        $record->update([
                            'status' => 'Completed',
                            'completed_at' => now(),
                        ]);

                        if (! $record->maintenanceRecord()->exists()) {
                            VehicleMaintenanceRecord::create([
                                'vehicle_id' => $record->vehicle_id,
                                'service_request_id' => $record->id,
                                'service_type_option_id' => $record->service_type_option_id,
                                'provider_option_id' => $record->provider_option_id,
                                'service_date' => now()->toDateString(),
                                'notes' => $record->problem_description,
                            ]);
                        }

                        Notification::make()->title('Service completed and maintenance history updated')->success()->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (VehicleServiceRequest $record) => in_array($record->status, ['Pending', 'Approved']))
                    ->requiresConfirmation()
                    ->action(function (VehicleServiceRequest $record): void {
                        $record->update(['status' => 'Rejected']);
                        Notification::make()->title('Service request rejected')->danger()->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVehicleServiceRequests::route('/'),
        ];
    }
}
