<?php

declare(strict_types=1);

namespace App\Filament\Resources\ME;

use App\Filament\Resources\ME\BeneficiaryFeedbackResource\Pages\CreateBeneficiaryFeedback;
use App\Filament\Resources\ME\BeneficiaryFeedbackResource\Pages\EditBeneficiaryFeedback;
use App\Filament\Resources\ME\BeneficiaryFeedbackResource\Pages\ListBeneficiaryFeedback;
use App\Filament\Resources\ME\BeneficiaryFeedbackResource\Pages\ViewBeneficiaryFeedback;
use App\Filament\Resources\ME\Support\MeAuditTrail;
use App\Models\ME\MeBeneficiaryFeedback;
use App\Models\ME\MeDisaggregationCategory;
use App\Models\ME\MeDisaggregationOption;
use App\Models\ME\MeProject;
use App\Models\ME\MeReportingPeriod;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BeneficiaryFeedbackResource extends Resource
{
    protected static ?string $model = MeBeneficiaryFeedback::class;

    protected static ?string $modelLabel = 'Beneficiary Feedback';

    protected static ?string $pluralModelLabel = 'Beneficiary Feedback';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring and Evaluation';

    protected static ?string $navigationLabel = 'Beneficiary Feedback';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'comment';

    // ─── Form ─────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ── Context ──────────────────────────────────────────────
                \Filament\Schemas\Components\Section::make('Project Context')
                    ->description('Link this feedback entry to a ME project and reporting period.')
                    ->icon('heroicon-o-folder-open')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('project_id')
                            ->label('Project')
                            ->relationship('project', 'name')
                            ->getOptionLabelFromRecordUsing(fn (MeProject $record): string => "{$record->project_code} — {$record->name}")
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('reporting_period_id', null))
                            ->required(),
                        Select::make('reporting_period_id')
                            ->label('Reporting Period')
                            ->options(function (Get $get): array {
                                $projectId = $get('project_id');

                                if (! $projectId) {
                                    return [];
                                }

                                return MeReportingPeriod::query()
                                    ->where('project_id', $projectId)
                                    ->orderByDesc('start_date')
                                    ->get()
                                    ->mapWithKeys(fn (MeReportingPeriod $p): array => [
                                        $p->id => "{$p->label} ({$p->type})",
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->live()
                            ->helperText('Choose a period — filtered by selected project.'),
                    ]),

                // ── Submission Details ────────────────────────────────────
                \Filament\Schemas\Components\Section::make('Feedback Details')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        DateTimePicker::make('submitted_at')
                            ->label('Submitted At')
                            ->default(now())
                            ->required()
                            ->native(false),
                        Select::make('channel')
                            ->label('Collection Channel')
                            ->options([
                                'in_person'  => 'In Person',
                                'phone'      => 'Phone',
                                'mobile_app' => 'Mobile App',
                                'web'        => 'Web Portal',
                                'paper'      => 'Paper Form',
                                'sms'        => 'SMS',
                                'other'      => 'Other',
                            ])
                            ->searchable()
                            ->helperText('How was this feedback collected?'),
                        Select::make('location_id')
                            ->label('Sub-Location')
                            ->relationship('locationRecord', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Optional: select from the ME location registry.'),
                        TextInput::make('location')
                            ->label('Location (Free Text)')
                            ->maxLength(255)
                            ->helperText('Alternatively, enter a free-text location description.'),
                        Select::make('sentiment')
                            ->label('Overall Sentiment')
                            ->options([
                                'positive' => '👍  Positive',
                                'neutral'  => '😐  Neutral',
                                'negative' => '👎  Negative',
                            ])
                            ->required()
                            ->default('neutral'),
                        Select::make('rating')
                            ->label('Satisfaction Rating')
                            ->options([
                                5 => '★★★★★  Excellent (5)',
                                4 => '★★★★☆  Good (4)',
                                3 => '★★★☆☆  Average (3)',
                                2 => '★★☆☆☆  Poor (2)',
                                1 => '★☆☆☆☆  Very Poor (1)',
                            ])
                            ->helperText('Optional 1–5 star rating.'),
                        Textarea::make('comment')
                            ->label('Feedback Comment')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                // ── Disaggregation ────────────────────────────────────────
                \Filament\Schemas\Components\Section::make('Beneficiary Demographics')
                    ->description('Categorise this respondent by gender, age group and disability status.')
                    ->icon('heroicon-o-users')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('gender_option_id')
                            ->label('Gender')
                            ->options(fn (): array => static::optionsForCategoryKey('gender'))
                            ->searchable()
                            ->helperText('Gender disaggregation option.'),
                        Select::make('age_group_option_id')
                            ->label('Age Group')
                            ->options(fn (): array => static::optionsForCategoryKey('age'))
                            ->searchable()
                            ->helperText('Age-group disaggregation option.'),
                        Select::make('disability_option_id')
                            ->label('Disability Status')
                            ->options(fn (): array => static::optionsForCategoryKey('disability'))
                            ->searchable()
                            ->helperText('Disability status disaggregation option.'),
                    ]),
            ]);
    }

    // ─── Infolist ─────────────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project Context')
                    ->icon('heroicon-o-folder-open')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('project.project_code')
                            ->label('Project Code')
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('project.name')
                            ->label('Project')
                            ->placeholder('—'),
                        TextEntry::make('reportingPeriod.label')
                            ->label('Reporting Period')
                            ->placeholder('—'),
                        TextEntry::make('reportingPeriod.type')
                            ->label('Period Type')
                            ->badge()
                            ->placeholder('—'),
                    ]),

                Section::make('Feedback Details')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('submitted_at')
                            ->label('Submitted At')
                            ->dateTime(),
                        TextEntry::make('channel')
                            ->label('Collection Channel')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => $state
                                ? str_replace('_', ' ', ucwords($state, '_'))
                                : '—')
                            ->placeholder('—'),
                        TextEntry::make('locationRecord.name')
                            ->label('Location (Registry)')
                            ->placeholder('—'),
                        TextEntry::make('location')
                            ->label('Location (Free Text)')
                            ->placeholder('—'),
                        TextEntry::make('sentiment')
                            ->label('Sentiment')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'positive' => 'success',
                                'neutral'  => 'warning',
                                'negative' => 'danger',
                                default    => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'positive' => '👍  Positive',
                                'neutral'  => '😐  Neutral',
                                'negative' => '👎  Negative',
                                default    => '—',
                            }),
                        TextEntry::make('rating')
                            ->label('Satisfaction Rating')
                            ->formatStateUsing(fn (?int $state): string => $state
                                ? str_repeat('★', $state) . str_repeat('☆', 5 - $state) . " ({$state}/5)"
                                : '—')
                            ->placeholder('—'),
                        TextEntry::make('comment')
                            ->label('Feedback Comment')
                            ->columnSpanFull(),
                    ]),

                Section::make('Beneficiary Demographics')
                    ->icon('heroicon-o-users')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('genderOption.label')
                            ->label('Gender')
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('ageGroupOption.label')
                            ->label('Age Group')
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('disabilityOption.label')
                            ->label('Disability Status')
                            ->badge()
                            ->placeholder('—'),
                    ]),

                MeAuditTrail::section('me_beneficiary_feedback'),
            ]);
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('project.project_code')
                    ->label('Project')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('reportingPeriod.label')
                    ->label('Period')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sentiment')
                    ->label('Sentiment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'positive' => 'success',
                        'neutral'  => 'warning',
                        'negative' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'positive' => '👍 Positive',
                        'neutral'  => '😐 Neutral',
                        'negative' => '👎 Negative',
                        default    => ucfirst($state),
                    }),
                TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn (?int $state): string => $state
                        ? str_repeat('★', $state) . str_repeat('☆', 5 - $state)
                        : '—')
                    ->sortable(),
                TextColumn::make('genderOption.label')
                    ->label('Gender')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('ageGroupOption.label')
                    ->label('Age Group')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('disabilityOption.label')
                    ->label('Disability')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('channel')
                    ->label('Channel')
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? str_replace('_', ' ', ucwords($state, '_'))
                        : '—')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('location')
                    ->label('Location')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('comment')
                    ->label('Comment')
                    ->limit(70)
                    ->wrap()
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->getOptionLabelFromRecordUsing(fn (MeProject $record): string => "{$record->project_code} — {$record->name}")
                    ->searchable()
                    ->preload(),
                SelectFilter::make('sentiment')
                    ->options([
                        'positive' => 'Positive',
                        'neutral'  => 'Neutral',
                        'negative' => 'Negative',
                    ]),
                SelectFilter::make('channel')
                    ->label('Channel')
                    ->options([
                        'in_person'  => 'In Person',
                        'phone'      => 'Phone',
                        'mobile_app' => 'Mobile App',
                        'web'        => 'Web Portal',
                        'paper'      => 'Paper Form',
                        'sms'        => 'SMS',
                        'other'      => 'Other',
                    ]),
                SelectFilter::make('rating')
                    ->label('Rating')
                    ->options([
                        5 => '★★★★★  5',
                        4 => '★★★★☆  4',
                        3 => '★★★☆☆  3',
                        2 => '★★☆☆☆  2',
                        1 => '★☆☆☆☆  1',
                    ]),
                SelectFilter::make('gender_option_id')
                    ->label('Gender')
                    ->options(fn (): array => static::optionsForCategoryKey('gender')),
                SelectFilter::make('age_group_option_id')
                    ->label('Age Group')
                    ->options(fn (): array => static::optionsForCategoryKey('age')),
                SelectFilter::make('disability_option_id')
                    ->label('Disability Status')
                    ->options(fn (): array => static::optionsForCategoryKey('disability')),
                Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Submitted From')
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('to')
                            ->label('Submitted To')
                            ->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn ($q, $v) => $q->whereDate('submitted_at', '>=', $v))
                        ->when($data['to'] ?? null, fn ($q, $v) => $q->whereDate('submitted_at', '<=', $v)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'From: ' . $data['from'];
                        }
                        if ($data['to'] ?? null) {
                            $indicators[] = 'To: ' . $data['to'];
                        }
                        return $indicators;
                    }),
            ])
            ->filtersFormColumns(3)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [];
    }

    // ─── Pages ────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListBeneficiaryFeedback::route('/'),
            'create' => CreateBeneficiaryFeedback::route('/create'),
            'view'   => ViewBeneficiaryFeedback::route('/{record}'),
            'edit'   => EditBeneficiaryFeedback::route('/{record}/edit'),
        ];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Return a flat label => label options map for disaggregation options
     * belonging to a category with the given key. Falls back to all options
     * when the category key is not yet seeded.
     */
    public static function optionsForCategoryKey(string $key): array
    {
        $category = MeDisaggregationCategory::query()
            ->where('key', $key)
            ->first();

        if (! $category) {
            return MeDisaggregationOption::query()
                ->orderBy('sort_order')
                ->pluck('label', 'id')
                ->toArray();
        }

        return MeDisaggregationOption::query()
            ->where('category_id', $category->id)
            ->orderBy('sort_order')
            ->pluck('label', 'id')
            ->toArray();
    }
}
