<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\HouseholdResource\RelationManagers;

use App\Models\ME\MeBeneficiary;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BeneficiariesRelationManager extends RelationManager
{
    protected static string $relationship = 'beneficiaries';

    protected static ?string $title = 'Household Members';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('full_name')->label('First Name')->required()->maxLength(150),
            TextInput::make('father_name')->label("Father's Name")->maxLength(100),
            TextInput::make('grandfather_name')->label("Grandfather's Name")->maxLength(100),

            Select::make('gender')
                ->options([
                    'male'              => 'Male',
                    'female'            => 'Female',
                    'other'             => 'Other',
                    'prefer_not_to_say' => 'Prefer not to say',
                ])
                ->required(),

            DatePicker::make('date_of_birth')->label('Date of Birth')->native(false),
            TextInput::make('child_code')->label('Child Code')->maxLength(30),
            TextInput::make('national_id')->label('National ID')->maxLength(60),
            TextInput::make('phone')->tel()->maxLength(30),

            Select::make('disability_status')
                ->options([
                    'none'      => 'None',
                    'physical'  => 'Physical',
                    'visual'    => 'Visual',
                    'hearing'   => 'Hearing',
                    'cognitive' => 'Cognitive',
                    'multiple'  => 'Multiple',
                ])
                ->default('none'),

            Select::make('status')
                ->options([
                    'active'    => 'Active',
                    'inactive'  => 'Inactive',
                    'graduated' => 'Graduated',
                    'suspended' => 'Suspended',
                    'deceased'  => 'Deceased',
                ])
                ->default('active'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn (MeBeneficiary $record): string =>
                        'https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&background=6366f1&color=fff'
                    )
                    ->size(32),

                TextColumn::make('beneficiary_code')->badge()->color('primary')->label('Code'),
                TextColumn::make('child_code')->badge()->color('warning')->placeholder('—')->label('Child Code'),
                TextColumn::make('full_name')->label('First Name')->searchable(),
                TextColumn::make('father_name')->label("Father's")->placeholder('—'),
                TextColumn::make('gender')->badge(),
                TextColumn::make('age')->suffix(' yrs'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (MeBeneficiary $record): string => $record->status_color),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->defaultSort('full_name');
    }
}
