<?php

namespace App\Filament\Resources\Procurement\Suppliers;

use App\Filament\Resources\Procurement\Suppliers\Pages\CreateSupplier;
use App\Filament\Resources\Procurement\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Procurement\Suppliers\Pages\ListSuppliers;
use App\Models\Procurement\Supplier;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Supplier Registry';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Supplier Information')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(200),
                TextInput::make('code')->maxLength(50)->nullable()->unique(ignoreRecord: true)
                    ->helperText('Optional unique supplier code / ID'),

                TextInput::make('email')->email()->maxLength(150)->nullable(),
                TextInput::make('phone')->tel()->maxLength(50)->nullable(),

                TextInput::make('contact_person')->maxLength(150)->nullable(),
                TextInput::make('tin_number')->label('TIN Number')->maxLength(50)->nullable(),

                Select::make('category')
                    ->options(fn () => \App\Models\Procurement\ProcurementCategory::where('is_active', true)->pluck('name', 'name')->toArray())
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('status')
                    ->options([
                        'Active'      => 'Active',
                        'Inactive'    => 'Inactive',
                        'Blacklisted' => 'Blacklisted',
                    ])
                    ->default('Active')
                    ->required(),

                Textarea::make('address')->rows(2)->columnSpanFull()->nullable(),
                Textarea::make('notes')->rows(2)->columnSpanFull()->nullable(),

                Toggle::make('portal_access')
                    ->label('Portal Access Granted')
                    ->helperText('Allow this supplier to log in to the vendor portal and submit bids.')
                    ->columnSpanFull(),
            ]),

            Section::make('Banking Details')->columns(2)->schema([
                TextInput::make('bank_name')->maxLength(100)->nullable(),
                TextInput::make('bank_account')->maxLength(100)->nullable()->label('Account Number'),
            ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()->sortable()->weight('semibold'),

                TextColumn::make('code')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('phone')->toggleable(),
                TextColumn::make('contact_person')->toggleable(),

                TextColumn::make('category')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Goods'       => 'info',
                        'Services'    => 'primary',
                        'Works'       => 'warning',
                        'Consultancy' => 'purple',
                        default       => 'gray',
                    }),

                \Filament\Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'Active'      => 'Active',
                        'Inactive'    => 'Inactive',
                        'Blacklisted' => 'Blacklisted',
                    ])
                    ->sortable()
                    ->searchable(),

                TextColumn::make('portal_access')
                    ->label('Portal')
                    ->badge()
                    ->getStateUsing(fn (Supplier $r) => $r->portal_access ? 'Enabled' : 'Disabled')
                    ->color(fn ($state) => $state === 'Enabled' ? 'success' : 'gray'),

                TextColumn::make('created_at')->label('Since')->date()->sortable()->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('status')
                    ->options(['Active' => 'Active', 'Inactive' => 'Inactive', 'Blacklisted' => 'Blacklisted']),
                SelectFilter::make('category')
                    ->options(fn () => \App\Models\Procurement\ProcurementCategory::pluck('name', 'name')->toArray()),
            ])
            ->recordActions([
                Action::make('grant_portal')
                    ->label(fn (Supplier $r) => $r->portal_access ? 'Revoke Portal' : 'Grant Portal')
                    ->icon(fn (Supplier $r) => $r->portal_access ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (Supplier $r) => $r->portal_access ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Supplier $r) {
                        $r->update([
                            'portal_access' => ! $r->portal_access,
                            'status'        => ! $r->portal_access ? 'Active' : $r->status,
                        ]);
                        Notification::make()
                            ->title($r->fresh()->portal_access ? 'Portal access granted to '.$r->name : 'Portal access revoked for '.$r->name)
                            ->success()
                            ->send();
                    }),
                EditAction::make(), DeleteAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'edit'   => EditSupplier::route('/{record}/edit'),
        ];
    }
}
