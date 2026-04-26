<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT;

use App\Filament\Resources\BRT\ReferralResource\Pages\CreateReferral;
use App\Filament\Resources\BRT\ReferralResource\Pages\EditReferral;
use App\Filament\Resources\BRT\ReferralResource\Pages\ListReferrals;
use App\Filament\Resources\BRT\ReferralResource\Pages\ViewReferral;
use App\Models\ME\MeReferral;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class ReferralResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = MeReferral::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Beneficiary Registry & Project Tracking';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Referrals';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'referred_to';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Referral Details')
                ->columns(2)
                ->schema([
                    Select::make('beneficiary_id')
                        ->label('Beneficiary')
                        ->relationship('beneficiary', 'full_name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('referral_type')
                        ->options([
                            'health'       => 'Health',
                            'psychosocial' => 'Psychosocial Support',
                            'legal'        => 'Legal Aid',
                            'education'    => 'Education',
                            'livelihood'   => 'Livelihood / IGA',
                            'shelter'      => 'Shelter',
                            'protection'   => 'Protection',
                            'other'        => 'Other',
                        ])
                        ->required(),

                    TextInput::make('referred_to')
                        ->label('Referred To (Organisation / Clinic)')
                        ->required()
                        ->maxLength(200),

                    DatePicker::make('referral_date')
                        ->required()
                        ->default(now())
                        ->native(false),

                    Select::make('referred_by')
                        ->label('Referred By')
                        ->relationship('referredByUser', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('project_id')
                        ->label('Related Project')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->preload(),

                    Select::make('status')
                        ->options([
                            'pending'     => 'Pending',
                            'in_progress' => 'In Progress',
                            'completed'   => 'Completed',
                            'cancelled'   => 'Cancelled',
                        ])
                        ->default('pending')
                        ->required(),

                    DatePicker::make('completed_at')
                        ->label('Completed On')
                        ->native(false),
                ]),

            Section::make('Reasons & Outcomes')
                ->schema([
                    Textarea::make('reason')
                        ->label('Reason for Referral')
                        ->required()
                        ->rows(3),

                    Textarea::make('outcome')
                        ->label('Referral Outcome')
                        ->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('beneficiary.beneficiary_code')
                    ->label('Code')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('beneficiary.full_name')
                    ->label('Beneficiary')
                    ->searchable(),

                TextColumn::make('referral_type')->badge()->color('info'),

                TextColumn::make('referred_to')
                    ->label('Referred To')
                    ->wrap(),

                TextColumn::make('referral_date')->date()->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (MeReferral $record): string => $record->status_color),

                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->date()
                    ->placeholder('—'),

                TextColumn::make('referredByUser.name')
                    ->label('By')
                    ->placeholder('—'),
            ])
            ->defaultSort('referral_date', 'desc')
            ->filters([
                SelectFilter::make('referral_type')
                    ->options([
                        'health'       => 'Health',
                        'psychosocial' => 'Psychosocial Support',
                        'legal'        => 'Legal Aid',
                        'education'    => 'Education',
                        'livelihood'   => 'Livelihood / IGA',
                        'shelter'      => 'Shelter',
                        'protection'   => 'Protection',
                        'other'        => 'Other',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'pending'     => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed'   => 'Completed',
                        'cancelled'   => 'Cancelled',
                    ]),
            ])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListReferrals::route('/'),
            'create' => CreateReferral::route('/create'),
            'view'   => ViewReferral::route('/{record}'),
            'edit'   => EditReferral::route('/{record}/edit'),
        ];
    }
}
