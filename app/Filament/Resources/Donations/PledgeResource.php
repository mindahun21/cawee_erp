<?php

namespace App\Filament\Resources\Donations;

use App\Filament\Resources\Donations\PledgeResource\Pages;
use App\Filament\Resources\Donations\PledgeResource\RelationManagers\DonationsRelationManager;
use App\Models\Pledge;
use App\Models\Currency;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use BackedEnum;
use App\Traits\BelongsToModule;

class PledgeResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = Pledge::class;

    protected static ?string $slug = 'pledges';

    protected static ?string $navigationLabel = 'Pledges';

    protected static UnitEnum|string|null $navigationGroup = 'Donor Fundraising';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-check';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()->tabs([
                    Tab::make('Pledge Details')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make()->columns(2)->schema([
                                Select::make('donor_id')
                                    ->relationship('donor', 'id')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('campaign_id')
                                    ->relationship('campaign', 'title')
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('total_amount')
                                    ->label('Total Pledge Amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix('ETB'),
                                Select::make('currency_id')
                                    ->relationship('currency', 'name')
                                    ->required()
                                    ->default(fn () => Currency::where('code', 'ETB')->first()?->id ?? 1),
                                DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->required()
                                    ->default(now()),
                                DatePicker::make('end_date')
                                    ->label('End Date (Optional)'),
                                Select::make('frequency')
                                    ->options([
                                        'one_time' => 'One Time',
                                        'monthly' => 'Monthly',
                                        'quarterly' => 'Quarterly',
                                        'yearly' => 'Yearly',
                                    ])
                                    ->default('one_time')
                                    ->required(),
                                Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                        'overdue' => 'Overdue',
                                    ])
                                    ->default('active')
                                    ->required(),
                            ]),
                        ]),

                    Tab::make('Notes')
                        ->icon('heroicon-o-pencil-square')
                        ->schema([
                            Section::make()->schema([
                                Textarea::make('notes')
                                    ->rows(6)
                                    ->placeholder('Add internal notes about this pledge...'),
                            ]),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('donor.first_name')
                    ->label('Donor')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->donor->full_name),
                TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('ETB')
                    ->sortable(),
                TextColumn::make('fulfilled_amount')
                    ->label('Fulfilled')
                    ->money('ETB')
                    ->badge()
                    ->color(fn ($state, $record) => $state >= $record->total_amount ? 'success' : 'warning'),
                TextColumn::make('percent_fulfilled')
                    ->label('%')
                    ->suffix('%')
                    ->numeric(1),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'gray',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->placeholder('General')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'overdue' => 'Overdue',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DonationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPledges::route('/'),
            'create' => Pages\CreatePledge::route('/create'),
            'edit' => Pages\EditPledge::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
