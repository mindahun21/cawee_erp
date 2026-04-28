<?php

namespace App\Filament\Resources\Donors;

use App\Filament\Resources\Donors\Pages\ManageDonors;
use App\Filament\Resources\Donors\Pages\ViewDonor;
use App\Filament\Resources\Donors\DonorResource\RelationManagers\DonationsRelationManager;
use App\Filament\Resources\Donors\DonorResource\RelationManagers\InteractionsRelationManager;
use App\Filament\Resources\Donors\DonorResource\RelationManagers\PledgesRelationManager;
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
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;


class DonorResource extends Resource
{
    protected static ?string $model = Donor::class;

    protected static string|UnitEnum|null $navigationGroup = 'Donor Fundraising';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'organization_name', 'email', 'phone'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()->tabs([
                    Tab::make('Basic Information')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Section::make()->columns(2)->schema([
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
                                    ->default('lead'),
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
                                PhoneInput::make('phone')
                                    ->defaultCountry('ET')
                                    ->placeholder('0912 345 678'),
                            ]),
                        ]),

                    Tab::make('Address & Categorization')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Section::make()->columns(2)->schema([
                                TextInput::make('city')
                                    ->placeholder('Addis Ababa'),
                                Select::make('country')
                                    ->options(config('countries'))
                                    ->searchable()
                                    ->preload(),
                                Textarea::make('address')
                                    ->columnSpanFull()
                                    ->placeholder('Sub City, Woreda, House No...'),
                                
                                Select::make('categories')
                                    ->multiple()
                                    ->relationship('categories', 'name')
                                    ->preload()
                                    ->columnSpanFull()
                                    ->required(fn ($get) => $get('status') !== 'lead')
                                    ->validationMessages([
                                        'required' => 'Please select at least one category for donor segmentation (FDD 4.3).',
                                    ]),
                            ]),
                        ]),

                    Tab::make('Preferences & Interests')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            Section::make('Communication Preferences')
                                ->description('Manage how this donor prefers to be contacted.')
                                ->columns(2)
                                ->schema([
                                    CheckboxList::make('communication_preferences')
                                        ->label('Preferred Channels')
                                        ->options([
                                            'email' => 'Email',
                                            'sms' => 'SMS',
                                            'phone' => 'Phone Call',
                                            'mail' => 'Postal Mail',
                                        ])
                                        ->descriptions([
                                            'email' => 'Official receipts and newsletters',
                                            'sms' => 'Urgent alerts and event reminders',
                                        ])
                                        ->columns(2)
                                        ->default(['email']),
                                    Toggle::make('marketing_opt_in')
                                        ->label('Marketing Consent')
                                        ->helperText('Donor has agreed to receive promotional outreach and campaign updates.')
                                        ->default(true)
                                        ->inline(false)
                                        ->onIcon('heroicon-m-check')
                                        ->offIcon('heroicon-m-x-mark')
                                        ->onColor('success'),
                                ]),
                            Section::make('Donor Interests')
                                ->description('Identify specific campaign types for targeted outreach.')
                                ->schema([
                                    CheckboxList::make('interests')
                                        ->label('Campaign Interests')
                                        ->options([
                                            'emergency' => 'Emergency Relief',
                                            'education' => 'Education & Scholarship',
                                            'health' => 'Healthcare & Medical',
                                            'environment' => 'Environmental Protection',
                                            'advocacy' => 'Policy & Advocacy',
                                        ])
                                        ->columns(3),
                                ]),
                        ]),

                    Tab::make('Notes')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make()->schema([
                                Textarea::make('notes')
                                    ->columnSpanFull()
                                    ->rows(6)
                                    ->placeholder('Any additional information about this donor...'),
                            ]),
                        ]),
                ])->columnSpanFull(),
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
                    ->weight('semibold'),
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
                    ->money('ETB')
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
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
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

    public static function getRelations(): array
    {
        return [
            DonationsRelationManager::class,
            InteractionsRelationManager::class,
            PledgesRelationManager::class,
        ];
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
