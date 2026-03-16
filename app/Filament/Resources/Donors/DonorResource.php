<?php

namespace App\Filament\Resources\Donors;

use App\Filament\Resources\Donors\Pages\ManageDonors;
use App\Filament\Resources\Donors\Pages\ViewDonor;
use App\Models\Donor;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DonorResource extends Resource
{
    protected static ?string $model = Donor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Donor Fundraising';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'organization_name', 'email', 'phone'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Basic Information')
                    ->columns(2)
                    ->schema([
                        ToggleButtons::make('donor_type')
                            ->options([
                                'individual' => 'Individual',
                                'corporate' => 'Corporate',
                                'foundation' => 'Foundation',
                            ])
                            ->icons([
                                'individual' => 'heroicon-m-user',
                                'corporate' => 'heroicon-m-building-office',
                                'foundation' => 'heroicon-m-academic-cap',
                            ])
                            ->colors([
                                'individual' => 'info',
                                'corporate' => 'primary',
                                'foundation' => 'warning',
                            ])
                            ->required()
                            ->reactive()
                            ->inline(),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'lead' => 'Lead',
                                'prospect' => 'Prospect',
                            ])
                            ->required()
                            ->default('active'),
                        TextInput::make('first_name')
                            ->label('First Name')
                            ->placeholder('John')
                            ->hidden(fn ($get) => in_array($get('donor_type'), ['corporate', 'foundation']))
                            ->required(fn ($get) => $get('donor_type') === 'individual'),
                        TextInput::make('last_name')
                            ->label('Last Name')
                            ->placeholder('Doe')
                            ->hidden(fn ($get) => in_array($get('donor_type'), ['corporate', 'foundation']))
                            ->required(fn ($get) => $get('donor_type') === 'individual'),
                        TextInput::make('organization_name')
                            ->label('Organization Name')
                            ->placeholder('Acme Corp')
                            ->hidden(fn ($get) => $get('donor_type') === 'individual')
                            ->required(fn ($get) => in_array($get('donor_type'), ['corporate', 'foundation'])),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('john@example.com'),
                        TextInput::make('phone')
                            ->tel()
                            ->placeholder('(123) 456-7890'),
                    ]),
                Section::make('Address & Categorization')
                    ->columns(2)
                    ->schema([
                        TextInput::make('city')
                            ->placeholder('New York'),
                        Select::make('country')
                            ->options(config('countries'))
                            ->searchable()
                            ->preload(),
                        Textarea::make('address')
                            ->columnSpanFull()
                            ->placeholder('123 Main St, Apt 4B'),
                    ]),
                Section::make('Donor Categories')
                    ->schema([
                        Select::make('categories')
                            ->multiple()
                            ->relationship('categories', 'name')
                            ->preload()
                            ->columnSpanFull(),
                    ]),
                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->columnSpanFull()
                            ->placeholder('Any additional information about this donor...'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                TextColumn::make('donor_type')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'individual' => 'heroicon-m-user',
                        'corporate' => 'heroicon-m-building-office',
                        'foundation' => 'heroicon-m-academic-cap',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'individual' => 'info',
                        'corporate' => 'primary',
                        'foundation' => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name', 'organization_name'])
                    ->sortable()
                    ->description(fn ($record) => new \Illuminate\Support\HtmlString('
                        <div class="hover-actions-wrapper flex gap-2 pt-1 items-center">
                            <a href="'.\App\Filament\Resources\Donors\DonorResource::getUrl('view', ['record' => $record]).'" class="hover-action-link text-gray-400 hover:text-gray-500">View</a>
                            <span class="text-gray-200">|</span>
                            <a href="'.\App\Filament\Resources\Donors\DonorResource::getUrl('edit', ['record' => $record]).'" class="hover-action-link text-primary-600 hover:text-primary-700">Edit</a>
                            <span class="text-gray-200">|</span>
                            <button type="button" 
                                x-on:click="$wire.mountTableAction(\'delete\', '.$record->id.')"
                                class="hover-action-link text-danger-600 hover:text-danger-700 font-medium">Delete</button>
                        </div>
                    '), position: 'below'),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),
                TextColumn::make('phone')
                    ->searchable()
                    ->icon('heroicon-m-phone'),
                TextColumn::make('city')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('country')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'lead' => 'info',
                        'prospect' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('total_donated')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('last_donation_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('donor_type')
                    ->options([
                        'individual' => 'Individual',
                        'corporate' => 'Corporate',
                        'foundation' => 'Foundation',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'lead' => 'Lead',
                        'prospect' => 'Prospect',
                    ]),
                SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('assignCategories')
                        ->label('Assign Categories')
                        ->icon('heroicon-m-tag')
                        ->form([
                            Select::make('category_ids')
                                ->label('Categories')
                                ->multiple()
                                ->relationship('categories', 'name')
                                ->preload()
                                ->required(),
                            Checkbox::make('append')
                                ->label('Append to existing categories')
                                ->default(true),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                if ($data['append']) {
                                    $record->categories()->syncWithoutDetaching($data['category_ids']);
                                } else {
                                    $record->categories()->sync($data['category_ids']);
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDonors::route('/'),
            'view' => ViewDonor::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
