<?php

declare(strict_types=1);

namespace App\Filament\Resources\ME\ProjectResource\RelationManagers;

use App\Models\ME\MeDisaggregationOption;
use App\Models\ME\MeReportingPeriod;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FeedbackRelationManager extends RelationManager
{
    protected static string $relationship = 'feedbacks';

    protected static ?string $title = 'Beneficiary Feedback';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateTimePicker::make('submitted_at')
                    ->label('Submitted At')
                    ->default(now())
                    ->required()
                    ->native(false),
                Select::make('reporting_period_id')
                    ->label('Reporting Period')
                    ->options(function (): array {
                        $owner = $this->getOwnerRecord();

                        return MeReportingPeriod::query()
                            ->where('project_id', $owner?->id)
                            ->orderByDesc('start_date')
                            ->get()
                            ->mapWithKeys(fn (MeReportingPeriod $p): array => [
                                $p->id => "{$p->label} ({$p->type})",
                            ])
                            ->toArray();
                    })
                    ->searchable(),
                Select::make('channel')
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
                TextInput::make('location')
                    ->label('Location')
                    ->maxLength(255),
                Select::make('sentiment')
                    ->required()
                    ->options([
                        'positive' => '👍  Positive',
                        'neutral'  => '😐  Neutral',
                        'negative' => '👎  Negative',
                    ])
                    ->default('neutral'),
                Select::make('rating')
                    ->label('Rating (1–5)')
                    ->options([
                        5 => '★★★★★  5',
                        4 => '★★★★☆  4',
                        3 => '★★★☆☆  3',
                        2 => '★★☆☆☆  2',
                        1 => '★☆☆☆☆  1',
                    ]),
                Select::make('gender_option_id')
                    ->label('Gender')
                    ->options(fn (): array => \App\Filament\Resources\ME\BeneficiaryFeedbackResource::optionsForCategoryKey('gender'))
                    ->searchable(),
                Select::make('age_group_option_id')
                    ->label('Age Group')
                    ->options(fn (): array => \App\Filament\Resources\ME\BeneficiaryFeedbackResource::optionsForCategoryKey('age'))
                    ->searchable(),
                Select::make('disability_option_id')
                    ->label('Disability Status')
                    ->options(fn (): array => \App\Filament\Resources\ME\BeneficiaryFeedbackResource::optionsForCategoryKey('disability'))
                    ->searchable(),
                Textarea::make('comment')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y')
                    ->sortable(),
                TextColumn::make('sentiment')
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
                        : '—'),
                TextColumn::make('genderOption.label')
                    ->label('Gender')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('channel')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? str_replace('_', ' ', ucwords($state, '_'))
                        : '—')
                    ->placeholder('—'),
                TextColumn::make('location')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('comment')
                    ->limit(60)
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('sentiment')
                    ->options([
                        'positive' => 'Positive',
                        'neutral'  => 'Neutral',
                        'negative' => 'Negative',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()->label('Record Feedback'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
