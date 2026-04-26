<?php

namespace App\Filament\Resources\VehicleManagement\AgreementRenewals;

use App\Models\AgreementRenewal;
use App\Models\VehicleSetting;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class AgreementRenewalResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = AgreementRenewal::class;

    protected static string|UnitEnum|null $navigationGroup = 'Vehicle Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static ?string $navigationLabel = 'Agreement Renewals';

    protected static ?int $navigationSort = 32;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('office_rent_agreement_id')
                ->label('Agreement')
                ->relationship('agreement', 'agreement_code')
                ->searchable()
                ->required(),

            Select::make('decision_option_id')
                ->label('Decision')
                ->options(VehicleSetting::optionsFor('renewal_decision'))
                ->required(),

            TextInput::make('new_monthly_rent')->numeric()->prefix('ETB')->nullable(),
            DatePicker::make('new_start_date')->nullable(),
            DatePicker::make('new_end_date')->nullable()->afterOrEqual('new_start_date'),

            Select::make('status')
                ->options([
                    'Pending' => 'Pending',
                    'Approved' => 'Approved',
                    'Rejected' => 'Rejected',
                    'Applied' => 'Applied',
                ])
                ->default('Pending')
                ->disabled()
                ->dehydrated(),

            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('agreement.agreement_code')->label('Agreement')->searchable()->sortable(),
                TextColumn::make('decision.label')->label('Decision')->badge(),
                TextColumn::make('new_monthly_rent')->label('New Rent')->money('ETB', true)->placeholder('-'),
                TextColumn::make('new_start_date')->date()->placeholder('-'),
                TextColumn::make('new_end_date')->date()->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'Approved' => 'success',
                        'Applied' => 'info',
                        'Rejected' => 'danger',
                        'Pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('approved_at')->since()->label('Approved')->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'Pending' => 'Pending',
                    'Approved' => 'Approved',
                    'Rejected' => 'Rejected',
                    'Applied' => 'Applied',
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (AgreementRenewal $record) => $record->status === 'Pending')
                    ->requiresConfirmation()
                    ->action(function (AgreementRenewal $record): void {
                        $record->update([
                            'status' => 'Approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                        Notification::make()->title('Renewal approved')->success()->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (AgreementRenewal $record) => $record->status === 'Pending')
                    ->requiresConfirmation()
                    ->action(function (AgreementRenewal $record): void {
                        $record->update([
                            'status' => 'Rejected',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                        Notification::make()->title('Renewal rejected')->danger()->send();
                    }),

                Action::make('apply')
                    ->label('Apply')
                    ->icon('heroicon-o-bolt')
                    ->color('info')
                    ->visible(fn (AgreementRenewal $record) => $record->status === 'Approved')
                    ->requiresConfirmation()
                    ->action(function (AgreementRenewal $record): void {
                        $agreement = $record->agreement;
                        $decision = strtolower((string) ($record->decision?->label ?? ''));

                        if (str_contains($decision, 'terminate')) {
                            $agreement->update(['status' => 'Terminated']);
                        } else {
                            $agreement->update([
                                'monthly_rent' => $record->new_monthly_rent ?? $agreement->monthly_rent,
                                'start_date' => $record->new_start_date ?? $agreement->start_date,
                                'end_date' => $record->new_end_date ?? $agreement->end_date,
                                'status' => 'Active',
                            ]);
                        }

                        $record->update(['status' => 'Applied']);
                        Notification::make()->title('Renewal applied to agreement')->success()->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAgreementRenewals::route('/'),
        ];
    }
}
