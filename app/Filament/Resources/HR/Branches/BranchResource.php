<?php

namespace App\Filament\Resources\HR\Branches;

use App\Models\HrBranch;
use App\Models\HrSettingOption;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class BranchResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = HrBranch::class;

    protected static ?string $cluster = \App\Filament\Clusters\CarRentManagement::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Branches';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('branch_name')->required()->maxLength(150),
            TextInput::make('branch_code')->maxLength(50)->unique(ignoreRecord: true),

            Select::make('location_id')
                ->label('Location')
                ->relationship('location', 'location_name')
                ->searchable()
                ->nullable(),

            Select::make('branch_type_option_id')
                ->label('Branch Type')
                ->options(HrSettingOption::optionsFor('branch_type'))
                ->searchable()
                ->required(),

            TextInput::make('proposed_office')->maxLength(150),

            Select::make('status')
                ->options([
                    'Requested' => 'Requested',
                    'Pending Agreement' => 'Pending Agreement',
                    'Active' => 'Active',
                    'Closed' => 'Closed',
                ])
                ->default('Requested')
                ->required(),

            Textarea::make('address')->rows(2)->columnSpanFull(),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch_name')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('branch_code')->searchable(),
                TextColumn::make('location.location_name')->label('Location')->toggleable(),
                TextColumn::make('branchType.label')->label('Type')->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'Active',
                        'warning' => 'Pending Agreement',
                        'danger' => 'Closed',
                        'info' => 'Requested',
                    ]),
                TextColumn::make('agreements_count')->counts('agreements')->label('Agreements'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'Requested' => 'Requested',
                    'Pending Agreement' => 'Pending Agreement',
                    'Active' => 'Active',
                    'Closed' => 'Closed',
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBranches::route('/'),
        ];
    }
}
