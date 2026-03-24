<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\BeneficiaryResource\RelationManagers;

use App\Models\BRT\BrtAttendance;
use App\Models\BRT\BrtTrainingEvent;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $title = 'Training & Event Attendance';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('event_id')
                ->label('Training / Event')
                ->options(
                    BrtTrainingEvent::query()
                        ->orderByDesc('event_date')
                        ->get()
                        ->mapWithKeys(fn (BrtTrainingEvent $e): array => [
                            $e->id => "[{$e->event_code}] {$e->title} ({$e->event_date->format('d M Y')})",
                        ])
                        ->toArray()
                )
                ->searchable()
                ->required(),

            Select::make('attendance_status')
                ->label('Status')
                ->options([
                    'present' => 'Present',
                    'absent'  => 'Absent',
                    'late'    => 'Late',
                    'excused' => 'Excused',
                ])
                ->default('present')
                ->required(),

            Textarea::make('remarks')->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event.title')
                    ->label('Event')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('event.event_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('event.event_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucwords($state, '_'))),

                TextColumn::make('attendance_status')
                    ->badge()
                    ->color(fn (BrtAttendance $record): string => $record->status_color),

                TextColumn::make('remarks')->placeholder('—')->limit(60),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('created_at', 'desc');
    }
}
