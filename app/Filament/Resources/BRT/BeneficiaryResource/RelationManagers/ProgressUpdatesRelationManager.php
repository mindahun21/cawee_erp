<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\BeneficiaryResource\RelationManagers;

use App\Models\BRT\BrtProgressUpdate;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProgressUpdatesRelationManager extends RelationManager
{
    protected static string $relationship = 'progressUpdates';

    protected static ?string $title = 'Progress Updates';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
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

            Select::make('project_id')
                ->label('Related Project')
                ->relationship('project', 'name')
                ->searchable()
                ->preload(),

            Select::make('authored_by')
                ->label('Monitoring Officer')
                ->relationship('author', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Textarea::make('summary')
                ->label('Progress Summary')
                ->required()
                ->rows(4),

            Textarea::make('challenges')
                ->rows(3),

            Textarea::make('recommendations')
                ->rows(3),

            DatePicker::make('next_update_due')
                ->label('Next Update Due')
                ->native(false),

            Toggle::make('high_risk_flag')
                ->label('Flag as High-Risk')
                ->helperText('Enable to trigger an automatic alert for this beneficiary.'),

            Select::make('alert_status')
                ->label('Alert Status')
                ->options([
                    'open'      => 'Open',
                    'in_review' => 'In Review',
                    'escalated' => 'Escalated',
                    'resolved'  => 'Resolved',
                ])
                ->default('open')
                ->visible(fn ($get): bool => (bool) $get('high_risk_flag')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('update_date')->date()->sortable(),

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
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->defaultSort('update_date', 'desc');
    }
}
