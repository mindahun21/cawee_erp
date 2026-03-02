<?php

namespace App\Filament\Resources\HR\Onboarding;

use App\Filament\Resources\HR\Onboarding\Pages\ManageOnboardingChecklist;
use App\Models\OnboardingChecklistItem;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OnboardingChecklistResource extends Resource
{
    protected static ?string $model = OnboardingChecklistItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'Onboarding Checklist';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(200)->columnSpanFull(),

            Select::make('phase')
                ->options(['Onboarding' => 'Onboarding', 'Offboarding' => 'Offboarding'])
                ->required()->default('Onboarding'),

            Select::make('category')
                ->options([
                    'Document to Sign' => 'Document to Sign',
                    'Form to Fill'     => 'Form to Fill',
                    'Training'         => 'Training',
                    'Equipment'        => 'Equipment',
                    'Other'            => 'Other',
                ])
                ->required()->default('Document to Sign'),

            Toggle::make('requires_signature')->default(true)->inline(false),
            Toggle::make('is_active')->default(true)->inline(false),

            TextInput::make('sort_order')->numeric()->default(0)->label('Sort Order'),

            FileUpload::make('document_template')
                ->label('Template Document (PDF/DOCX)')
                ->disk('local')
                ->directory('onboarding-templates')
                ->nullable()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('phase')
                    ->badge()
                    ->color(fn ($state) => $state === 'Onboarding' ? 'success' : 'warning'),
                TextColumn::make('category')->badge()->color('gray'),
                TextColumn::make('requires_signature')
                    ->label('Signature?')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                    ->badge()->color(fn ($state) => $state ? 'primary' : 'gray'),
                TextColumn::make('is_active')
                    ->label('Active')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                    ->badge()->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('phase')->options(['Onboarding' => 'Onboarding', 'Offboarding' => 'Offboarding']),
                SelectFilter::make('category')->options([
                    'Document to Sign' => 'Document to Sign',
                    'Form to Fill'     => 'Form to Fill',
                    'Training'         => 'Training',
                    'Equipment'        => 'Equipment',
                    'Other'            => 'Other',
                ]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOnboardingChecklist::route('/'),
        ];
    }
}
