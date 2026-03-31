<?php

namespace App\Filament\Resources\Campaigns;

use App\Filament\Resources\Campaigns\Pages\ManageCampaigns;
use App\Filament\Resources\Campaigns\Pages\ViewCampaign;
use App\Models\Campaign;
use BackedEnum;
use UnitEnum;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|UnitEnum|null $navigationGroup = 'Donor Fundraising';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()->tabs([
                    Tab::make('Campaign Information')
                        ->icon('heroicon-o-flag')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(150),
                                Select::make('status')
                                    ->options([
                                        'planned' => 'Planned',
                                        'active' => 'Active',
                                        'completed' => 'Completed',
                                    ])
                                    ->required()
                                    ->default('planned'),
                                Textarea::make('description')
                                    ->columnSpanFull()
                                    ->rows(3),
                            ]),
                        ]),

                    Tab::make('Financials & Goals')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                                TextInput::make('goal_amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix('ETB')
                                    ->minValue(0),
                                Select::make('currency_id')
                                    ->relationship('currency', 'name')
                                    ->required()
                                    ->default(fn () => \App\Models\Currency::where('code', 'ETB')->first()?->id ?? 1)
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('budget')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('ETB')
                                    ->minValue(0),
                            ]),
                        ]),

                    Tab::make('Timing')
                        ->icon('heroicon-o-calendar')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()->columns(['default' => 2])->schema([
                                DatePicker::make('start_date')
                                    ->required()
                                    ->displayFormat('d/m/Y'),
                                DatePicker::make('end_date')
                                    ->required()
                                    ->displayFormat('d/m/Y')
                                    ->after('start_date'),
                            ]),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('goal_amount')
                    ->money(fn ($record) => $record->currency->code ?? 'ETB')
                    ->sortable(),
                TextColumn::make('currency.code')
                    ->label('Currency')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('budget')
                    ->money(fn ($record) => $record->currency->code ?? 'ETB')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'planned' => 'gray',
                        'active' => 'success',
                        'completed' => 'info',
                    })
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'planned' => 'Planned',
                        'active' => 'Active',
                        'completed' => 'Completed',
                    ]),
                \Filament\Tables\Filters\Filter::make('start_date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CampaignResource\RelationManagers\EventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCampaigns::route('/'),
            'view' => ViewCampaign::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            CampaignResource\Widgets\CampaignStats::class,
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
