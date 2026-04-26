<?php

namespace App\Filament\Resources\HR\Contracts;

use App\Models\EmployeeContract;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class ContractResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = EmployeeContract::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'Contracts';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'contract_number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contract Details')->columns(2)->schema([
                Select::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable()
                    ->required(),

                Select::make('contract_type_id')
                    ->label('Contract Type')
                    ->relationship('contractType', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                TextInput::make('contract_number')
                    ->label('Contract Number')
                    ->maxLength(50),

                Select::make('status')
                    ->options([
                        'Active'      => 'Active',
                        'Expired'     => 'Expired',
                        'Terminated'  => 'Terminated',
                    ])
                    ->default('Active')
                    ->required(),

                DatePicker::make('start_date')->required(),
                DatePicker::make('end_date')->nullable()->helperText('Leave blank for open-ended contracts'),

                TextInput::make('salary')
                    ->numeric()
                    ->prefix('ETB')
                    ->nullable(),

                Textarea::make('notes')
                    ->rows(3)
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
                    ->weight('semibold')
                    ->sortable(),

                TextColumn::make('contract_number')
                    ->label('Contract #')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('contractType.name')
                    ->label('Type')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Active'     => 'success',
                        'Expired'    => 'warning',
                        'Terminated' => 'danger',
                        default      => 'gray',
                    }),

                TextColumn::make('start_date')->date()->sortable(),

                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->placeholder('Open-ended'),

                TextColumn::make('salary')
                    ->prefix('ETB ')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(),
            ])
            ->defaultSort('start_date', 'desc')
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('status')
                    ->options(['Active' => 'Active', 'Expired' => 'Expired', 'Terminated' => 'Terminated']),
                SelectFilter::make('contract_type_id')
                    ->label('Contract Type')
                    ->relationship('contractType', 'name'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'edit'   => Pages\EditContract::route('/{record}/edit'),
        ];
    }
}
