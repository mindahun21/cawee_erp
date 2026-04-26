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
use App\Traits\BelongsToModule;

class SupplierResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Supplier Registry';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // 1. Company Identity (mirror portal registration)
            Section::make('Company Identity')->columns(2)->schema([
                TextInput::make('name')
                    ->label('Legal Company Name')
                    ->required()
                    ->maxLength(200),

                TextInput::make('code')
                    ->maxLength(50)
                    ->nullable()
                    ->unique(ignoreRecord: true)
                    ->helperText('Optional internal supplier code / ID'),

                Select::make('category')
                    ->options(fn () => \App\Models\Procurement\ProcurementCategory::where('is_active', true)->pluck('name', 'name')->toArray())
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('tin_number')
                    ->label('TIN / Tax ID Number')
                    ->maxLength(50)
                    ->nullable(),

                TextInput::make('vat_number')
                    ->label('VAT Registration Number')
                    ->maxLength(50)
                    ->nullable(),

                TextInput::make('website')
                    ->label('Company Website')
                    ->url()
                    ->maxLength(200)
                    ->nullable(),
            ]),

            // 2. Contact Information
            Section::make('Contact Information')->columns(2)->schema([
                TextInput::make('contact_person')
                    ->label('Contact Person Full Name')
                    ->maxLength(150)
                    ->nullable(),

                TextInput::make('contact_person_title')
                    ->label('Title / Position')
                    ->maxLength(50)
                    ->nullable(),

                TextInput::make('email')
                    ->label('Official Email')
                    ->email()
                    ->maxLength(150)
                    ->nullable(),

                TextInput::make('phone')
                    ->label('Company Phone')
                    ->tel()
                    ->maxLength(50)
                    ->nullable(),

                TextInput::make('contact_phone')
                    ->label('Contact Direct Phone')
                    ->maxLength(50)
                    ->nullable(),
            ]),

            // 3. Business Address
            Section::make('Business Address')->columns(3)->schema([
                TextInput::make('country')
                    ->maxLength(100)
                    ->nullable(),

                TextInput::make('city')
                    ->maxLength(100)
                    ->nullable(),

                TextInput::make('state')
                    ->label('State / Region')
                    ->maxLength(100)
                    ->nullable(),

                TextInput::make('zip_code')
                    ->label('ZIP / Postal Code')
                    ->maxLength(20)
                    ->nullable(),

                Textarea::make('address')
                    ->label('Street Address')
                    ->rows(2)
                    ->columnSpanFull()
                    ->nullable(),
            ]),

            // 4. Banking & Payment Details
            Section::make('Banking & Payment Details')->columns(2)->schema([
                TextInput::make('bank_name')
                    ->label('Bank Name')
                    ->maxLength(100)
                    ->nullable(),

                TextInput::make('bank_account')
                    ->label('Account Number')
                    ->maxLength(100)
                    ->nullable(),

                TextInput::make('bank_branch')
                    ->label('Branch Name')
                    ->maxLength(150)
                    ->nullable(),

                Select::make('payment_term_id')
                    ->label('Payment Terms')
                    ->relationship('paymentTerm', 'name')
                    ->nullable()
                    ->createOptionForm([
                        TextInput::make('name')->required()->unique('payment_terms', 'name'),
                    ]),

                Select::make('currency')
                    ->label('Preferred Currency')
                    ->options([
                        'ETB' => 'ETB',
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                        'GBP' => 'GBP',
                    ])
                    ->nullable(),
            ])->collapsible(),

            // 5. Portal Settings & Internal Notes
            Section::make('Portal Settings & Notes')->columns(2)->schema([
                Select::make('status')
                    ->options([
                        'Active'      => 'Active',
                        'Inactive'    => 'Inactive',
                        'Blacklisted' => 'Blacklisted',
                    ])
                    ->default('Active')
                    ->required(),

                Toggle::make('portal_access')
                    ->label('Portal Access Granted')
                    ->helperText('Allow this supplier to log in to the vendor portal and submit bids.'),

                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull()
                    ->nullable(),

                \Filament\Forms\Components\FileUpload::make('attachments')
                    ->label('Registration Documents')
                    ->multiple()
                    ->disk('local')
                    ->directory('procurement/suppliers')
                    ->columnSpanFull(),
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

                TextColumn::make('paymentTerm.name')->label('Payment Terms')->toggleable(),
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
