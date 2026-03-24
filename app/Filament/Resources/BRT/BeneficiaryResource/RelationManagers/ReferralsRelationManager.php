<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\BeneficiaryResource\RelationManagers;

use App\Models\ME\MeReferral;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReferralsRelationManager extends RelationManager
{
    protected static string $relationship = 'referrals';

    protected static ?string $title = 'Referrals';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
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

            Textarea::make('reason')->required()->rows(3),
            Textarea::make('outcome')->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('referral_type')->badge()->color('info'),
                TextColumn::make('referred_to')->label('Referred To')->wrap(),
                TextColumn::make('referral_date')->date()->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (MeReferral $record): string => $record->status_color),
                TextColumn::make('completed_at')->label('Completed')->date()->placeholder('—'),
                TextColumn::make('referredByUser.name')->label('By')->placeholder('—'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->defaultSort('referral_date', 'desc');
    }
}
