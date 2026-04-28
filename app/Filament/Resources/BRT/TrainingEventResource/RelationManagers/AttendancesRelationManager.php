<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\TrainingEventResource\RelationManagers;

use App\Models\BRT\BrtAttendance;
use App\Models\ME\MeBeneficiary;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $title = 'Attendance Register';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('beneficiary_id')
                ->label('Beneficiary')
                ->options(
                    MeBeneficiary::query()
                        ->orderBy('full_name')
                        ->get()
                        ->mapWithKeys(fn (MeBeneficiary $b): array => [
                            $b->id => "[{$b->beneficiary_code}] {$b->full_name}",
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
                TextColumn::make('beneficiary.beneficiary_code')
                    ->label('Code')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('beneficiary.full_name')
                    ->label('Beneficiary')
                    ->searchable(),

                TextColumn::make('beneficiary.gender')
                    ->label('Sex')
                    ->badge(),

                TextColumn::make('beneficiary.age')
                    ->label('Age')
                    ->suffix(' yrs'),

                TextColumn::make('attendance_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (BrtAttendance $record): string => $record->status_color),

                TextColumn::make('remarks')
                    ->placeholder('—')
                    ->limit(60),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('created_at', 'asc');
    }
}
