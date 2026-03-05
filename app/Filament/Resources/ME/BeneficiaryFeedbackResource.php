<?php

namespace App\Filament\Resources\ME;

use App\Filament\Resources\ME\BeneficiaryFeedbackResource\Pages\CreateBeneficiaryFeedback;
use App\Filament\Resources\ME\BeneficiaryFeedbackResource\Pages\EditBeneficiaryFeedback;
use App\Filament\Resources\ME\BeneficiaryFeedbackResource\Pages\ListBeneficiaryFeedback;
use App\Filament\Resources\ME\BeneficiaryFeedbackResource\Pages\ViewBeneficiaryFeedback;
use App\Filament\Resources\ME\Support\MeAuditTrail;
use App\Models\ME\MeBeneficiaryFeedback;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BeneficiaryFeedbackResource extends Resource
{
    protected static ?string $model = MeBeneficiaryFeedback::class;
    
    protected static ?string $modelLabel = 'Beneficiary Feedback';
    
    protected static ?string $pluralModelLabel = 'Beneficiary Feedback';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring and Evaluation';

    protected static ?string $navigationLabel = 'Beneficiary Feedback';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Feedback')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        DateTimePicker::make('submitted_at')
                            ->default(now())
                            ->required(),
                        TextInput::make('location')
                            ->maxLength(255),
                        Select::make('sentiment')
                            ->required()
                            ->options([
                                'positive' => 'Positive',
                                'neutral' => 'Neutral',
                                'negative' => 'Negative',
                            ]),
                        Textarea::make('comment')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('metadata')
                            ->label('Metadata (JSON)')
                            ->formatStateUsing(fn ($state): ?string => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                            ->dehydrateStateUsing(function ($state): ?array {
                                if (blank($state)) {
                                    return null;
                                }

                                return json_decode((string) $state, true);
                            })
                            ->rules(['nullable', 'json'])
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Feedback')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('submitted_at')
                            ->dateTime(),
                        TextEntry::make('location')
                            ->placeholder('-'),
                        TextEntry::make('sentiment')
                            ->badge(),
                        TextEntry::make('comment')
                            ->columnSpanFull(),
                        TextEntry::make('metadata')
                            ->formatStateUsing(fn ($state): string => $state ? json_encode($state, JSON_PRETTY_PRINT) : '-')
                            ->columnSpanFull(),
                    ]),
                MeAuditTrail::section('me_beneficiary_feedback'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('location')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('sentiment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'positive' => 'success',
                        'neutral' => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('comment')
                    ->limit(80)
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('sentiment')
                    ->options([
                        'positive' => 'Positive',
                        'neutral' => 'Neutral',
                        'negative' => 'Negative',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBeneficiaryFeedback::route('/'),
            'create' => CreateBeneficiaryFeedback::route('/create'),
            'view' => ViewBeneficiaryFeedback::route('/{record}'),
            'edit' => EditBeneficiaryFeedback::route('/{record}/edit'),
        ];
    }
}
