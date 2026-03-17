<?php

namespace App\Filament\Resources\HR\BranchUtilities;

use App\Models\BranchUtility;
use App\Models\HrSettingOption;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BranchUtilityResource extends Resource
{
    protected static ?string $model = BranchUtility::class;

    protected static ?string $cluster = \App\Filament\Clusters\CarRentManagement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Branch Utilities';

    protected static ?int $navigationSort = 37;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('branch_id')
                ->relationship('branch', 'branch_name')
                ->preload()
                ->searchable()
                ->required(),

            Select::make('utility_type_option_id')
                ->label('Utility Type')
                ->options(fn () => HrSettingOption::optionsFor('utility_type'))
                ->searchable()
                ->required(),

            TextInput::make('provider')->maxLength(150),
            TextInput::make('account_number')->maxLength(100),

            Select::make('payment_cycle_option_id')
                ->label('Payment Cycle')
                ->options(fn () => HrSettingOption::optionsFor('utility_payment_cycle'))
                ->nullable(),

            TextInput::make('estimated_amount')->numeric()->prefix('ETB')->default(0),
            DatePicker::make('next_due_date')->nullable(),

            Select::make('status')
                ->options(['Active' => 'Active', 'Inactive' => 'Inactive'])
                ->default('Active')
                ->required(),

            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch.branch_name')->label('Branch')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('utilityType.label')->label('Utility')->badge(),
                TextColumn::make('provider')->searchable()->toggleable(),
                TextColumn::make('account_number')->toggleable(),
                TextColumn::make('paymentCycle.label')->label('Cycle')->toggleable(),
                TextColumn::make('estimated_amount')->money('ETB', true),
                TextColumn::make('next_due_date')->date()->sortable()->placeholder('-'),
                TextColumn::make('days_until_due')
                    ->label('Days To Due')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state < 0 => 'danger',
                        $state <= 7 => 'danger',
                        $state <= 30 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Active' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->options(['Active' => 'Active', 'Inactive' => 'Inactive']),
            ])
            ->defaultSort('next_due_date')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBranchUtilities::route('/'),
        ];
    }
}
