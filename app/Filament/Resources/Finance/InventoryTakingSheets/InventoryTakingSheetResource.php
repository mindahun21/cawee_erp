<?php

namespace App\Filament\Resources\Finance\InventoryTakingSheets;

use App\Filament\Resources\Finance\InventoryTakingSheets\Pages\CreateInventoryTakingSheets;
use App\Filament\Resources\Finance\InventoryTakingSheets\Pages\EditInventoryTakingSheets;
use App\Filament\Resources\Finance\InventoryTakingSheets\Pages\ListInventoryTakingSheets;
use App\Filament\Resources\Finance\InventoryTakingSheets\Pages\ViewInventoryTakingSheets;
use App\Models\Finance\CostCenter;
use App\Models\Finance\InventoryTakingSheet;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\Action as TblAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class InventoryTakingSheetResource extends Resource
{
    protected static ?string $model                          = InventoryTakingSheet::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|UnitEnum|null $navigationGroup  = 'Finance';
    protected static ?string $navigationLabel               = 'Inventory Taking';
    protected static ?int    $navigationSort                = 88;
    protected static ?string $slug                          = 'finance/inventory-taking-sheets';
    protected static bool $shouldSkipAuthorization          = true;

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        if (! $u) {
            return true;
        }

        return $u->isFinanceOfficer() || $u->isFinanceManager() || $u->isSuperAdmin() || $u->hasRole('store_keeper');
    }
    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($record): bool   { return $record->status === 'draft' && static::canViewAny(); }
    public static function canDelete($record): bool { return $record->status === 'draft' && static::canViewAny(); }
    public static function canView($record): bool   { return static::canViewAny(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sheet Header')->icon('heroicon-o-clipboard-document')->columns(3)->schema([
                TextInput::make('reference')->label('Ref #')->disabled()->dehydrated()->placeholder('Auto-generated'),
                DatePicker::make('taking_date')->label('Taking Date')->required()->default(now()),
                Select::make('cost_center_id')->label('Cost Center')->native(false)->required()->searchable()
                    ->options(fn () => CostCenter::where('is_active', true)->pluck('name', 'id')),
                Select::make('project_id')->label('Project')->native(false)->nullable()->searchable()
                    ->options(fn () => Project::orderBy('project_name')->pluck('project_name', 'id')),
            ]),

            Section::make('Inventory Items')->icon('heroicon-o-list-bullet')->schema([
                Repeater::make('items')->relationship('items')->schema([
                    Select::make('item_type')->label('Type')->native(false)->required()
                        ->options(['asset' => 'Fixed Asset', 'inventory_item' => 'Consumable/Inventory']),
                    TextInput::make('item_description')->label('Description / Name')->required(),
                    TextInput::make('book_quantity')->label('System Qty')->numeric()->default(0),
                    TextInput::make('physical_quantity')->label('Counted Qty')->numeric()->default(0),
                    TextInput::make('unit_cost')->label('Unit Cost (Avg)')->numeric()->default(0),
                    TextInput::make('notes')->label('Notes/Condition')->nullable()->columnSpanFull(),
                ])->columns(5)->addActionLabel('Add Item Row')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Ref #')->badge()->color('primary')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('taking_date')->label('Date')->date()->sortable(),
                TextColumn::make('costCenter.name')->label('Cost Center')->limit(20)->searchable(),
                TextColumn::make('project.project_name')->label('Project')->limit(20)->placeholder('—'),
                TextColumn::make('items_count')->counts('items')->label('Items Count')->badge()->color('gray'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($state) => match($state) { 'draft' => 'gray', 'verified' => 'primary', 'submitted' => 'success', default => 'gray' }),
            ])
            ->filters([SelectFilter::make('status')->options(InventoryTakingSheet::statuses())])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn ($record) => $record->status === 'draft'),
                DeleteAction::make()->visible(fn ($record) => $record->status === 'draft'),

                TblAction::make('verify')->label('Verify')->icon('heroicon-o-check-badge')->color('primary')->button()
                    ->visible(fn ($record) => $record->status === 'draft' && (auth()->user()?->isFinanceOfficer() || auth()->user()?->isFinanceManager()))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->forceFill(['status' => 'verified', 'verified_by' => auth()->id()])->save();
                        Notification::make()->success()->title('Inventory count verified.')->send();
                    }),
                    
                TblAction::make('submit')->label('Submit to Donor/Management')->icon('heroicon-o-paper-airplane')->color('success')->button()
                    ->visible(fn ($record) => $record->status === 'verified' && (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin()))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->forceFill(['status' => 'submitted'])->save();
                        Notification::make()->success()->title('Inventory taking sheet finalized.')->send();
                    }),
            ])
            ->defaultSort('taking_date', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Taking Details')->icon('heroicon-o-clipboard-document')->columns(3)->schema([
                TextEntry::make('reference')->label('Ref #')->badge()->color('primary')->fontFamily('mono'),
                TextEntry::make('taking_date')->label('Taking Date')->date(),
                TextEntry::make('status')->label('Status')->badge()
                    ->color(fn ($state) => match($state) { 'draft' => 'gray', 'verified' => 'primary', 'submitted' => 'success', default => 'gray' }),
                TextEntry::make('costCenter.name')->label('Cost Center'),
                TextEntry::make('project.project_name')->label('Project')->placeholder('—'),
                TextEntry::make('conductedBy.name')->label('Conducted By'),
                TextEntry::make('verifiedBy.name')->label('Verified By')->placeholder('—'),
            ]),
            
            Section::make('Counted Items')->icon('heroicon-o-list-bullet')->schema([
                RepeatableEntry::make('items')->schema([
                    TextEntry::make('item_type')->label('Type')->badge()->color('gray'),
                    TextEntry::make('item_description')->label('Item'),
                    TextEntry::make('book_quantity')->label('System Qty')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                    TextEntry::make('physical_quantity')->label('Physical Qty')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold'),
                    TextEntry::make('variance')->label('Variance')->numeric(decimalPlaces: 2)->fontFamily('mono')
                        ->color(fn ($state) => (float)$state < 0 ? 'danger' : ((float)$state > 0 ? 'success' : 'gray')),
                    TextEntry::make('unit_cost')->label('Unit Cost')->numeric(decimalPlaces: 2)->fontFamily('mono')->placeholder('—'),
                    TextEntry::make('variance_amount')->label('Variance Value')->numeric(decimalPlaces: 2)->fontFamily('mono')
                        ->color(fn ($state) => (float)$state < 0 ? 'danger' : ((float)$state > 0 ? 'success' : 'gray')),
                    TextEntry::make('notes')->label('Notes')->columnSpanFull()->placeholder('—'),
                ])->columns(7),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListInventoryTakingSheets::route('/'),
            'create' => CreateInventoryTakingSheets::route('/create'),
            'view'   => ViewInventoryTakingSheets::route('/{record}'),
            'edit'   => EditInventoryTakingSheets::route('/{record}/edit'),
        ];
    }
}
