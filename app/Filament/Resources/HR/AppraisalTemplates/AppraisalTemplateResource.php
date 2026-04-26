<?php

namespace App\Filament\Resources\HR\AppraisalTemplates;

use App\Filament\Resources\HR\AppraisalTemplates\Pages\ManageAppraisalTemplates;
use App\Models\AppraisalTemplate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class AppraisalTemplateResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = AppraisalTemplate::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationParentItem = 'HR Settings';

    protected static ?string $navigationLabel = 'Appraisal Templates';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Template Details')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(150)->columnSpanFull(),

                Select::make('type')
                    ->options(['Employee' => 'Employee', 'Supervisor' => 'Supervisor'])
                    ->required()
                    ->default('Employee'),

                Toggle::make('is_active')->default(true)->inline(false),

                Textarea::make('description')->rows(3)->columnSpanFull(),
            ]),

            Section::make('Sections & Criteria')
                ->description('Add evaluation sections (e.g. INDIVIDUAL, TASK EFFECTIVENESS) and their criteria/factors.')
                ->schema([
                    Repeater::make('sections')
                        ->relationship('sections')
                        ->orderColumn('sort_order')
                        ->schema([
                            TextInput::make('title')->required()->maxLength(150)->label('Section Title'),

                            Repeater::make('criteria')
                                ->relationship('criteria')
                                ->orderColumn('sort_order')
                                ->label('Criteria / Factors')
                                ->schema([
                                    TextInput::make('factor_name')
                                        ->required()
                                        ->maxLength(200)
                                        ->label('Factor Name'),

                                    Textarea::make('description')
                                        ->rows(2)
                                        ->label('Description'),

                                    TextInput::make('max_score')
                                        ->numeric()
                                        ->default(5)
                                        ->minValue(1)
                                        ->label('Max Score'),

                                    TextInput::make('weight')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(0.1)
                                        ->step(0.1)
                                        ->label('Weight (for averaging)'),

                                    Toggle::make('is_active')->default(true)->inline(false),
                                ])
                                ->addActionLabel('+ Add Criterion')
                                ->columns(2)
                                ->collapsible(),
                        ])
                        ->addActionLabel('+ Add Section')
                        ->collapsible(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn ($state) => $state === 'Supervisor' ? 'primary' : 'info'),
                TextColumn::make('sections_count')
                    ->label('Sections')
                    ->counts('sections'),
                TextColumn::make('is_active')
                    ->label('Active')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('type')->options(['Employee' => 'Employee', 'Supervisor' => 'Supervisor']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAppraisalTemplates::route('/'),
        ];
    }
}
