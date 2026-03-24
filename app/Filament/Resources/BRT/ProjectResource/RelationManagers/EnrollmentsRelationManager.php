<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\ProjectResource\RelationManagers;

use App\Models\ME\MeBeneficiaryEnrollment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Enrolled Beneficiaries';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('beneficiary_id')
                ->label('Beneficiary')
                ->relationship('beneficiary', 'full_name')
                ->searchable()
                ->preload()
                ->required(),

            DatePicker::make('enrollment_date')
                ->required()
                ->default(now())
                ->native(false),

            DatePicker::make('exit_date')
                ->nullable()
                ->native(false),

            Select::make('participation_status')
                ->options([
                    'enrolled'    => 'Enrolled',
                    'active'      => 'Active',
                    'completed'   => 'Completed',
                    'dropped_out' => 'Dropped Out',
                    'suspended'   => 'Suspended',
                ])
                ->default('enrolled')
                ->required(),

            TextInput::make('exit_reason')->maxLength(255),
            Textarea::make('notes')->rows(2),
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

                TextColumn::make('beneficiary.gender')->badge(),

                TextColumn::make('enrollment_date')->date()->sortable(),

                TextColumn::make('exit_date')
                    ->date()
                    ->placeholder('Still Enrolled')
                    ->sortable(),

                TextColumn::make('participation_status')
                    ->badge()
                    ->color(fn (MeBeneficiaryEnrollment $record): string => $record->status_color),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('enrollment_date', 'desc');
    }
}
