<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT;

use App\Filament\Resources\BRT\ProgressUpdateResource\Pages\CreateProgressUpdate;
use App\Filament\Resources\BRT\ProgressUpdateResource\Pages\EditProgressUpdate;
use App\Filament\Resources\BRT\ProgressUpdateResource\Pages\ListProgressUpdates;
use App\Filament\Resources\BRT\ProgressUpdateResource\Pages\ViewProgressUpdate;
use App\Models\BRT\BrtProgressUpdate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class ProgressUpdateResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = BrtProgressUpdate::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Beneficiary Registry & Project Tracking';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Progress Updates';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'summary';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tracking Details')
                ->columns(2)
                ->schema([
                    Select::make('beneficiary_id')
                        ->label('Beneficiary')
                        ->relationship('beneficiary', 'full_name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('project_id')
                        ->label('Related Project')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->preload(),

                    DatePicker::make('update_date')
                        ->required()
                        ->default(now())
                        ->native(false),

                    Select::make('update_type')
                        ->label('Update Type')
                        ->options([
                            'routine_monitoring' => 'Routine Monitoring',
                            'milestone_review'   => 'Milestone Review',
                            'exit_assessment'    => 'Exit Assessment',
                            'home_visit'         => 'Home Visit',
                            'phone_checkin'      => 'Phone Check-in',
                            'other'              => 'Other',
                        ])
                        ->default('routine_monitoring')
                        ->required(),

                    Select::make('overall_progress')
                        ->label('Overall Progress')
                        ->options([
                            'improving' => 'Improving',
                            'stable'    => 'Stable',
                            'declining' => 'Declining',
                            'unknown'   => 'Unknown',
                        ])
                        ->default('stable')
                        ->required(),

                    Select::make('authored_by')
                        ->label('Monitoring Officer')
                        ->relationship('author', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    DatePicker::make('next_update_due')
                        ->label('Next Update Due')
                        ->native(false),

                    Toggle::make('high_risk_flag')
                        ->label('Flag as High-Risk')
                        ->helperText('Enable to trigger an automatic alert in the High-Risk Alerts section.'),

                    Select::make('alert_status')
                        ->label('Alert Status')
                        ->options([
                            'open'      => 'Open',
                            'in_review' => 'In Review',
                            'escalated' => 'Escalated',
                            'resolved'  => 'Resolved',
                        ])
                        ->default('open')
                        ->visible(fn ($get): bool => (bool) $get('high_risk_flag'))
                        ->required(fn ($get): bool => (bool) $get('high_risk_flag')),

                    Select::make('assigned_to')
                        ->label('Assigned Risk Officer')
                        ->relationship('assignedTo', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn ($get): bool => (bool) $get('high_risk_flag')),

                    DatePicker::make('resolved_at')
                        ->label('Resolved On')
                        ->native(false)
                        ->visible(fn ($get): bool => (bool) $get('high_risk_flag') && $get('alert_status') === 'resolved'),

                    Textarea::make('resolution_note')
                        ->label('Resolution Note')
                        ->rows(3)
                        ->columnSpanFull()
                        ->visible(fn ($get): bool => (bool) $get('high_risk_flag') && $get('alert_status') === 'resolved'),
                ]),

            Section::make('Update Content')
                ->schema([
                    Textarea::make('summary')
                        ->label('Progress Summary')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),

                    Textarea::make('challenges')
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('recommendations')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('update_date')->date()->sortable(),

                TextColumn::make('beneficiary.beneficiary_code')
                    ->label('Code')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('beneficiary.full_name')
                    ->label('Beneficiary')
                    ->searchable(),

                TextColumn::make('update_type')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string =>
                        str_replace('_', ' ', ucwords($state, '_'))
                    ),

                TextColumn::make('overall_progress')
                    ->badge()
                    ->color(fn (BrtProgressUpdate $record): string => $record->progress_color),

                TextColumn::make('author.name')
                    ->label('Officer')
                    ->placeholder('—'),

                TextColumn::make('next_update_due')
                    ->label('Next Due')
                    ->date()
                    ->placeholder('—')
                    ->color(fn (BrtProgressUpdate $record): string => $record->isOverdue() ? 'danger' : 'gray'),

                IconColumn::make('high_risk_flag')
                    ->label('High Risk')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray'),

                TextColumn::make('alert_status')
                    ->label('Alert Status')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => $state ? str_replace('_', ' ', ucwords($state, '_')) : '—')
                    ->color(fn (BrtProgressUpdate $record): string => $record->alert_status_color),

                TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('update_date', 'desc')
            ->filters([
                SelectFilter::make('overall_progress')
                    ->options([
                        'improving' => 'Improving',
                        'stable'    => 'Stable',
                        'declining' => 'Declining',
                        'unknown'   => 'Unknown',
                    ]),
                TernaryFilter::make('high_risk_flag')
                    ->label('High Risk Status'),
                SelectFilter::make('alert_status')
                    ->options([
                        'open'      => 'Open',
                        'in_review' => 'In Review',
                        'escalated' => 'Escalated',
                        'resolved'  => 'Resolved',
                    ]),
                TernaryFilter::make('overdue')
                    ->label('Next Update Overdue')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('next_update_due')->whereDate('next_update_due', '<', now()),
                        false: fn ($query) => $query->where(fn ($q) => $q->whereNull('next_update_due')->orWhereDate('next_update_due', '>=', now())),
                        blank: fn ($query) => $query
                    ),
            ])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProgressUpdates::route('/'),
            'create' => CreateProgressUpdate::route('/create'),
            'view'   => ViewProgressUpdate::route('/{record}'),
            'edit'   => EditProgressUpdate::route('/{record}/edit'),
        ];
    }
}
