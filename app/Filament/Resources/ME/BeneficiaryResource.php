<?php

declare(strict_types=1);

namespace App\Filament\Resources\ME;

use App\Filament\Resources\ME\BeneficiaryResource\Pages;
use App\Filament\Resources\ME\BeneficiaryResource\RelationManagers;
use App\Models\ME\MeBeneficiary;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class BeneficiaryResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = MeBeneficiary::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring and Evaluation';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Beneficiaries (Legacy)';

    protected static ?int $navigationSort = 7;

    /**
     * Beneficiary management has been moved to the BRT module.
     * This resource is kept for backward compatibility but hidden from navigation.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'full_name';

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Personal Information')
                ->columns(2)
                ->schema([
                    \Filament\Forms\Components\TextInput::make('full_name')
                        ->label('Full Name')->required()->maxLength(150),

                    \Filament\Forms\Components\TextInput::make('full_name_local')
                        ->label('Full Name (Local Language)')->maxLength(150),

                    \Filament\Forms\Components\Select::make('gender')
                        ->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'])
                        ->required(),

                    \Filament\Forms\Components\DatePicker::make('date_of_birth')
                        ->label('Date of Birth')->maxDate(now())->native(false),

                    \Filament\Forms\Components\TextInput::make('national_id')
                        ->label('National ID / Kebele Card')->maxLength(60),

                    \Filament\Forms\Components\TextInput::make('phone')->tel()->maxLength(30),

                    \Filament\Forms\Components\Select::make('disability_status')
                        ->options([
                            'none' => 'None', 'physical' => 'Physical', 'visual' => 'Visual',
                            'hearing' => 'Hearing', 'cognitive' => 'Cognitive', 'multiple' => 'Multiple',
                        ])->default('none')->required(),

                    \Filament\Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active', 'inactive' => 'Inactive', 'graduated' => 'Graduated',
                            'suspended' => 'Suspended', 'deceased' => 'Deceased',
                        ])->default('active')->required(),
                ]),

            Section::make('Location')
                ->columns(2)
                ->schema([
                    \Filament\Forms\Components\TextInput::make('kebele')->maxLength(100),
                    \Filament\Forms\Components\TextInput::make('woreda')->maxLength(100),
                    \Filament\Forms\Components\TextInput::make('zone')->maxLength(100),
                    \Filament\Forms\Components\TextInput::make('region')->maxLength(100),
                    \Filament\Forms\Components\Textarea::make('address')->columnSpanFull()->rows(2),
                ]),

            Section::make('Household & Registration')
                ->columns(2)
                ->schema([
                    \Filament\Forms\Components\Select::make('household_id')
                        ->label('Household')
                        ->relationship('household', 'household_code')
                        ->searchable()->preload()
                        ->createOptionForm([
                            \Filament\Forms\Components\TextInput::make('household_code')->required()->maxLength(30),
                            \Filament\Forms\Components\TextInput::make('head_of_household')->maxLength(100),
                            \Filament\Forms\Components\TextInput::make('family_size')->numeric()->default(1),
                            \Filament\Forms\Components\Select::make('vulnerability_status')
                                ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'])
                                ->default('low'),
                            \Filament\Forms\Components\Select::make('income_level')
                                ->options(['none' => 'None', 'very_low' => 'Very Low', 'low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                                ->default('none'),
                        ]),

                    \Filament\Forms\Components\DatePicker::make('registered_at')
                        ->label('Registration Date')->default(now())->native(false),

                    \Filament\Forms\Components\Select::make('registered_by')
                        ->label('Registered By')
                        ->relationship('registeredBy', 'name')
                        ->searchable()->preload(),

                    \Filament\Forms\Components\FileUpload::make('photo_path')
                        ->label('Photo')->image()->directory('me/beneficiaries')->columnSpanFull(),
                ]),

            Section::make('Notes')->schema([
                \Filament\Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    // ── Infolist ──────────────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Personal Information')
                ->columns(3)
                ->schema([
                    TextEntry::make('beneficiary_code')->badge()->color('primary'),
                    TextEntry::make('full_name'),
                    TextEntry::make('full_name_local')->placeholder('—'),
                    TextEntry::make('gender')->badge(),
                    TextEntry::make('date_of_birth')->date()->placeholder('—'),
                    TextEntry::make('age')->suffix(' years')->placeholder('—'),
                    TextEntry::make('national_id')->placeholder('—'),
                    TextEntry::make('phone')->placeholder('—'),
                    TextEntry::make('disability_status')->badge(),
                    TextEntry::make('status')->badge()
                        ->color(fn (MeBeneficiary $record): string => $record->status_color),
                ]),

            Section::make('Location')
                ->columns(2)
                ->schema([
                    TextEntry::make('kebele')->placeholder('—'),
                    TextEntry::make('woreda')->placeholder('—'),
                    TextEntry::make('zone')->placeholder('—'),
                    TextEntry::make('region')->placeholder('—'),
                    TextEntry::make('address')->placeholder('—')->columnSpanFull(),
                ]),

            Section::make('Household')
                ->columns(2)
                ->schema([
                    TextEntry::make('household.household_code')->label('Household Code')->placeholder('—'),
                    TextEntry::make('household.vulnerability_status')->label('Vulnerability')->badge()->placeholder('—'),
                    TextEntry::make('household.family_size')->label('Family Size')->placeholder('—'),
                    TextEntry::make('household.income_level')->label('Income Level')->placeholder('—'),
                ]),

            Section::make('Registration')
                ->columns(2)
                ->schema([
                    TextEntry::make('registered_at')->date(),
                    TextEntry::make('registeredBy.name')->label('Registered By')->placeholder('—'),
                    TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('')->circular()
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=B&background=6366f1&color=fff')
                    ->size(36),

                TextColumn::make('beneficiary_code')
                    ->label('Code')->badge()->color('primary')
                    ->searchable()->sortable(),

                TextColumn::make('full_name')
                    ->label('Name')->searchable()->sortable(),

                TextColumn::make('gender')
                    ->badge()
                    ->color(fn (MeBeneficiary $record): string => $record->gender === 'female' ? 'pink' : 'info'),

                TextColumn::make('age')->suffix(' yrs')->sortable(),

                TextColumn::make('household.vulnerability_status')
                    ->label('Vulnerability')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high'     => 'warning',
                        'medium'   => 'info',
                        default    => 'success',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (MeBeneficiary $record): string => $record->status_color)
                    ->sortable(),

                TextColumn::make('woreda')->label('Woreda')->placeholder('—'),

                TextColumn::make('registered_at')->label('Registered')->date()->sortable(),
            ])
            ->defaultSort('registered_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'graduated' => 'Graduated', 'suspended' => 'Suspended']),

                SelectFilter::make('gender')
                    ->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other']),

                SelectFilter::make('disability_status')->label('Disability')
                    ->options([
                        'none' => 'None', 'physical' => 'Physical', 'visual' => 'Visual',
                        'hearing' => 'Hearing', 'cognitive' => 'Cognitive', 'multiple' => 'Multiple',
                    ]),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\EnrollmentsRelationManager::class,
            RelationManagers\BaselineAssessmentsRelationManager::class,
            RelationManagers\CaseNotesRelationManager::class,
            RelationManagers\ReferralsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBeneficiaries::route('/'),
            'create' => Pages\CreateBeneficiary::route('/create'),
            'view'   => Pages\ViewBeneficiary::route('/{record}'),
            'edit'   => Pages\EditBeneficiary::route('/{record}/edit'),
        ];
    }
}
