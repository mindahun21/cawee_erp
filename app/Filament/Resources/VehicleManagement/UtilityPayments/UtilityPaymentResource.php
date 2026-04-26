<?php

namespace App\Filament\Resources\VehicleManagement\UtilityPayments;

use App\Models\UtilityPayment;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class UtilityPaymentResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = UtilityPayment::class;

    protected static string|UnitEnum|null $navigationGroup = 'Vehicle Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Utility Payments';

    protected static ?int $navigationSort = 38;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('branch_utility_id')
                ->label('Branch Utility')
                ->relationship('utility', 'id')
                ->getOptionLabelFromRecordUsing(
                    fn ($record) => ($record->branch?->branch_name ?? 'Unknown Branch') . ' - ' . ($record->utilityType?->label ?? 'Utility')
                )
                ->preload()
                ->searchable()
                ->required(),

            DatePicker::make('period_start')->nullable(),
            DatePicker::make('period_end')->nullable()->afterOrEqual('period_start'),
            DatePicker::make('due_date')->required(),

            TextInput::make('amount')->numeric()->prefix('ETB')->required(),

            Select::make('status')
                ->options([
                    'Pending' => 'Pending',
                    'Paid' => 'Paid',
                    'Overdue' => 'Overdue',
                ])
                ->default('Pending')
                ->required(),

            DatePicker::make('paid_at')->label('Paid Date')->nullable(),
            TextInput::make('payment_reference')->maxLength(100),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('utility.branch.branch_name')->label('Branch')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('utility.utilityType.label')->label('Utility'),
                TextColumn::make('due_date')->date()->sortable(),
                TextColumn::make('amount')->money('ETB', true)->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Paid' => 'success',
                        'Overdue' => 'danger',
                        'Pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('paid_at')->date()->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'Pending' => 'Pending',
                    'Paid' => 'Paid',
                    'Overdue' => 'Overdue',
                ]),
            ])
            ->defaultSort('due_date')
            ->recordActions([
                Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (UtilityPayment $record) => $record->status !== 'Paid')
                    ->requiresConfirmation()
                    ->action(function (UtilityPayment $record): void {
                        $record->update([
                            'status' => 'Paid',
                            'paid_at' => now(),
                        ]);
                        Notification::make()->title('Payment marked as paid')->success()->send();
                    }),

                Action::make('mark_overdue')
                    ->label('Mark Overdue')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn (UtilityPayment $record) => $record->status === 'Pending')
                    ->requiresConfirmation()
                    ->action(function (UtilityPayment $record): void {
                        $record->update(['status' => 'Overdue']);
                        Notification::make()->title('Payment marked overdue')->warning()->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUtilityPayments::route('/'),
        ];
    }
}
