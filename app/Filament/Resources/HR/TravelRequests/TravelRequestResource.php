<?php

namespace App\Filament\Resources\HR\TravelRequests;

use App\Filament\Resources\HR\TravelRequests\Pages\ManageTravelRequests;
use App\Models\TravelRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TravelRequestResource extends Resource
{
    protected static ?string $model = TravelRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'Travel Requests';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Travel Request Details')->columns(2)->schema([
                Select::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable()
                    ->required(),

                Select::make('travel_type')
                    ->options([
                        'Field'      => 'Field',
                        'Conference' => 'Conference',
                        'Training'   => 'Training',
                        'Other'      => 'Other',
                    ])
                    ->required(),

                DatePicker::make('start_date')->required(),
                DatePicker::make('end_date')->required()->afterOrEqual('start_date'),

                TextInput::make('per_diem_amount')
                    ->numeric()
                    ->prefix('ETB')
                    ->minValue(0),

                Toggle::make('vehicle_required')->inline(false),
                Toggle::make('report_submitted')->inline(false),

                Select::make('approval_status')
                    ->options([
                        'Pending'  => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                    ])
                    ->default('Pending')
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Use the action buttons in the list view to approve or reject.')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('travel_type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Field'      => 'success',
                        'Conference' => 'info',
                        'Training'   => 'warning',
                        default      => 'gray',
                    }),

                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->sortable(),

                TextColumn::make('per_diem_amount')
                    ->prefix('ETB ')
                    ->numeric(decimalPlaces: 2),

                TextColumn::make('approval_status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default    => 'warning',
                    }),

                IconColumn::make('vehicle_required')->boolean()->toggleable(),
                IconColumn::make('report_submitted')->boolean()->toggleable(),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                SelectFilter::make('approval_status')
                    ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected']),
                SelectFilter::make('travel_type')
                    ->options(['Field' => 'Field', 'Conference' => 'Conference', 'Training' => 'Training', 'Other' => 'Other']),
            ])
            ->recordActions([
                // ── Approve ────────────────────────────────────────────
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TravelRequest $record) =>
                        $record->approval_status === 'Pending' && auth()->user()->isHrOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Approve Travel Request')
                    ->modalDescription('Confirm approval of this travel request.')
                    ->modalSubmitActionLabel('Approve')
                    ->action(fn (TravelRequest $record) => $record->update(['approval_status' => 'Approved'])
                        && Notification::make()->title('Travel request approved ✓')->success()->send()),

                // ── Reject ─────────────────────────────────────────────
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (TravelRequest $record) =>
                        $record->approval_status === 'Pending' && auth()->user()->isHrSupervisor()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Reject Travel Request')
                    ->modalSubmitActionLabel('Reject')
                    ->action(fn (TravelRequest $record) => $record->update(['approval_status' => 'Rejected'])
                        && Notification::make()->title('Travel request rejected')->danger()->send()),

                // ── Mark Report Submitted ──────────────────────────────
                Action::make('mark_report')
                    ->label('Report Submitted')
                    ->icon('heroicon-o-document-check')
                    ->color('info')
                    ->visible(fn (TravelRequest $record) =>
                        $record->approval_status === 'Approved' && ! $record->report_submitted
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Mark Trip Report as Submitted')
                    ->modalSubmitActionLabel('Confirm')
                    ->action(fn (TravelRequest $record) => $record->update(['report_submitted' => true])
                        && Notification::make()->title('Trip report marked as submitted')->success()->send()),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTravelRequests::route('/'),
        ];
    }
}
