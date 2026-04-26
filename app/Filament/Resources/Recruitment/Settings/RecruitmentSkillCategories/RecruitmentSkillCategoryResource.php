<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentSkillCategories;

use App\Filament\Resources\Recruitment\Settings\RecruitmentSkillCategories\Pages\ListRecruitmentSkillCategories;
use App\Models\Recruitment\RecruitmentSkillCategory;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Traits\BelongsToModule;

class RecruitmentSkillCategoryResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = RecruitmentSkillCategory::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Skill Categories';

    // ── Form (shown in modal) ────────────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state))),
            TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('slug')
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('skills_count')
                    ->label('Skills')
                    ->counts('skills')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecruitmentSkillCategories::route('/'),
        ];
    }
}
