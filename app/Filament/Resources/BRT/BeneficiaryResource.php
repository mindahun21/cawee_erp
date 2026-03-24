<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT;

use App\Filament\Resources\BRT\BeneficiaryResource\Pages\CreateBeneficiary;
use App\Filament\Resources\BRT\BeneficiaryResource\Pages\EditBeneficiary;
use App\Filament\Resources\BRT\BeneficiaryResource\Pages\ListBeneficiaries;
use App\Filament\Resources\BRT\BeneficiaryResource\Pages\ViewBeneficiary;
use App\Filament\Resources\BRT\BeneficiaryResource\RelationManagers\AttendancesRelationManager;
use App\Filament\Resources\BRT\BeneficiaryResource\RelationManagers\BaselineAssessmentsRelationManager;
use App\Filament\Resources\BRT\BeneficiaryResource\RelationManagers\CaseNotesRelationManager;
use App\Filament\Resources\BRT\BeneficiaryResource\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\BRT\BeneficiaryResource\RelationManagers\ProgressUpdatesRelationManager;
use App\Filament\Resources\BRT\BeneficiaryResource\RelationManagers\ReferralsRelationManager;
use App\Models\ME\MeBeneficiary;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BeneficiaryResource extends Resource
{
    protected static ?string $model = MeBeneficiary::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Beneficiary Registry & Project Tracking';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Beneficiaries';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'full_name';

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Personal Information')
                ->description('Core identity fields for the beneficiary record.')
                ->icon('heroicon-o-user')
                ->columns(3)
                ->schema([
                    TextInput::make('full_name')
                        ->label('First Name')
                        ->required()
                        ->maxLength(150),

                    TextInput::make('father_name')
                        ->label("Father's Name")
                        ->maxLength(100),

                    TextInput::make('grandfather_name')
                        ->label("Grandfather's Name")
                        ->maxLength(100),

                    TextInput::make('full_name_local')
                        ->label('Full Name (Local Language)')
                        ->maxLength(150)
                        ->columnSpan(2),

                    Select::make('gender')
                        ->options([
                            'male'             => 'Male',
                            'female'           => 'Female',
                            'other'            => 'Other',
                            'prefer_not_to_say' => 'Prefer not to say',
                        ])
                        ->required(),

                    DatePicker::make('date_of_birth')
                        ->label('Date of Birth')
                        ->maxDate(now())
                        ->native(false),

                    TextInput::make('national_id')
                        ->label('National ID / Kebele Card')
                        ->maxLength(60),

                    TextInput::make('child_code')
                        ->label('Child Code')
                        ->placeholder('e.g. ET3003400')
                        ->required()
                        ->rule('regex:/^[A-Za-z]{2}\d{6,}$/')
                        ->maxLength(30)
                        ->unique(MeBeneficiary::class, 'child_code', ignoreRecord: true)
                        ->validationMessages([
                            'regex' => 'Child Code must start with two letters followed by digits (for example: ET3003400).',
                        ]),

                    TextInput::make('phone')
                        ->tel()
                        ->maxLength(30),

                    Select::make('disability_status')
                        ->options([
                            'none'      => 'None',
                            'physical'  => 'Physical',
                            'visual'    => 'Visual',
                            'hearing'   => 'Hearing',
                            'cognitive' => 'Cognitive',
                            'multiple'  => 'Multiple',
                        ])
                        ->default('none')
                        ->required(),

                    Select::make('status')
                        ->options([
                            'active'    => 'Active',
                            'inactive'  => 'Inactive',
                            'graduated' => 'Graduated',
                            'suspended' => 'Suspended',
                            'deceased'  => 'Deceased',
                        ])
                        ->default('active')
                        ->required(),
                ]),

            Section::make('Location')
                ->icon('heroicon-o-map-pin')
                ->columns(2)
                ->schema([
                    TextInput::make('kebele')->maxLength(100),
                    TextInput::make('woreda')->maxLength(100),
                    TextInput::make('zone')->maxLength(100),
                    TextInput::make('region')->maxLength(100),
                    Textarea::make('address')->columnSpanFull()->rows(2),
                ]),

            Section::make('Household & Registration')
                ->icon('heroicon-o-home')
                ->columns(2)
                ->schema([
                    Select::make('household_id')
                        ->label('Household')
                        ->relationship('household', 'household_code')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('household_code')->required()->maxLength(30),
                            TextInput::make('head_of_household')->maxLength(100),
                            TextInput::make('family_size')->numeric()->default(1),
                            Select::make('vulnerability_status')
                                ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'])
                                ->default('low'),
                            Select::make('income_level')
                                ->options(['none' => 'None', 'very_low' => 'Very Low', 'low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                                ->default('none'),
                        ]),

                    DatePicker::make('registered_at')
                        ->label('Registration Date')
                        ->default(now())
                        ->native(false),

                    Select::make('registered_by')
                        ->label('Registered By')
                        ->relationship('registeredBy', 'name')
                        ->searchable()
                        ->preload(),

                    FileUpload::make('photo_path')
                        ->label('Photo')
                        ->image()
                        ->directory('brt/beneficiaries')
                        ->columnSpanFull(),
                ]),

            Section::make('Notes')
                ->schema([
                    Textarea::make('notes')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    // ── Infolist ──────────────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Personal Information')
                ->icon('heroicon-o-user')
                ->columns(4)
                ->schema([
                    TextEntry::make('beneficiary_code')->badge()->color('primary')->label('BNF Code'),
                    TextEntry::make('child_code')->badge()->color('warning')->placeholder('—')->label('Child Code'),
                    TextEntry::make('full_name')->label('First Name'),
                    TextEntry::make('father_name')->placeholder('—'),
                    TextEntry::make('grandfather_name')->placeholder('—'),
                    TextEntry::make('full_name_local')->label('Name (Local)')->placeholder('—'),
                    TextEntry::make('gender')->badge(),
                    TextEntry::make('date_of_birth')->date()->placeholder('—'),
                    TextEntry::make('age')->suffix(' years')->placeholder('—'),
                    TextEntry::make('national_id')->placeholder('—'),
                    TextEntry::make('phone')->placeholder('—'),
                    TextEntry::make('disability_status')->badge(),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (MeBeneficiary $record): string => $record->status_color),
                ]),

            Section::make('Location')
                ->icon('heroicon-o-map-pin')
                ->columns(2)
                ->schema([
                    TextEntry::make('kebele')->placeholder('—'),
                    TextEntry::make('woreda')->placeholder('—'),
                    TextEntry::make('zone')->placeholder('—'),
                    TextEntry::make('region')->placeholder('—'),
                    TextEntry::make('address')->placeholder('—')->columnSpanFull(),
                ]),

            Section::make('Household')
                ->icon('heroicon-o-home')
                ->columns(2)
                ->schema([
                    TextEntry::make('household.household_code')->label('Household Code')->placeholder('—'),
                    TextEntry::make('household.vulnerability_status')->label('Vulnerability')->badge()->placeholder('—'),
                    TextEntry::make('household.family_size')->label('Family Size')->placeholder('—'),
                    TextEntry::make('household.income_level')->label('Income Level')->placeholder('—'),
                ]),

            Section::make('Registration')
                ->icon('heroicon-o-clipboard-document-check')
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
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn (MeBeneficiary $record): string =>
                        'https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&background=6366f1&color=fff'
                    )
                    ->size(36),

                TextColumn::make('beneficiary_code')
                    ->label('BNF Code')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('child_code')
                    ->label('Child Code')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('full_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('father_name')
                    ->label("Father's Name")
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('gender')
                    ->badge()
                    ->color(fn (MeBeneficiary $record): string => $record->gender === 'female' ? 'pink' : 'info'),

                TextColumn::make('age')
                    ->suffix(' yrs')
                    ->sortable(),

                TextColumn::make('woreda')->label('Woreda')->placeholder('—'),

                TextColumn::make('region')->label('Region')->placeholder('—')->toggleable(),

                TextColumn::make('household.vulnerability_status')
                    ->label('Vulnerability')
                    ->badge()
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

                TextColumn::make('registered_at')
                    ->label('Registered')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('registered_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active'    => 'Active',
                        'inactive'  => 'Inactive',
                        'graduated' => 'Graduated',
                        'suspended' => 'Suspended',
                        'deceased'  => 'Deceased',
                    ]),

                SelectFilter::make('gender')
                    ->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other']),

                SelectFilter::make('disability_status')
                    ->label('Disability')
                    ->options([
                        'none'      => 'None',
                        'physical'  => 'Physical',
                        'visual'    => 'Visual',
                        'hearing'   => 'Hearing',
                        'cognitive' => 'Cognitive',
                        'multiple'  => 'Multiple',
                    ]),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public static function getRelationManagers(): array
    {
        return [
            EnrollmentsRelationManager::class,
            BaselineAssessmentsRelationManager::class,
            CaseNotesRelationManager::class,
            ReferralsRelationManager::class,
            ProgressUpdatesRelationManager::class,
            AttendancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBeneficiaries::route('/'),
            'create' => CreateBeneficiary::route('/create'),
            'view'   => ViewBeneficiary::route('/{record}'),
            'edit'   => EditBeneficiary::route('/{record}/edit'),
        ];
    }
}
