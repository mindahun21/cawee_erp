<?php

namespace App\Filament\Resources\Procurement\SupplierReturns;

use App\Filament\Resources\Procurement\SupplierReturns\Pages\CreateSupplierReturn;
use App\Filament\Resources\Procurement\SupplierReturns\Pages\EditSupplierReturn;
use App\Filament\Resources\Procurement\SupplierReturns\Pages\ListSupplierReturns;
use App\Models\Procurement\SupplierReturn;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class SupplierReturnResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = SupplierReturn::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;
    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';
    protected static ?string $navigationLabel = 'Supplier Returns (RTV)';
    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        $reasonOptions = [
            'Quality Defect'            => 'Quality Defect',
            'Wrong Item Delivered'      => 'Wrong Item Delivered',
            'Quantity Shortage'         => 'Quantity Shortage',
            'Damaged on Arrival'        => 'Damaged on Arrival',
            'Expired / Past Shelf Life' => 'Expired / Past Shelf Life',
            'Other'                     => 'Other',
        ];

        return $schema->components([
            Section::make('Return Details')->columns(2)->schema([
                TextInput::make('return_number')
                    ->label('Return #')
                    ->disabled()->dehydrated()
                    ->placeholder('Auto-generated'),

                Select::make('goods_receipt_id')
                    ->label('Goods Receipt (GRN)')
                    ->relationship('goodsReceipt', 'grn_number')
                    ->searchable()->preload()->required(),

                Select::make('purchase_order_id')
                    ->label('Purchase Order')
                    ->relationship('purchaseOrder', 'po_number')
                    ->searchable()->preload()->required(),

                Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()->preload()->required(),

                DatePicker::make('return_date')
                    ->label('Return Date')
                    ->required()->default(now()->toDateString()),

                DatePicker::make('expected_resolution_date')
                    ->label('Expected Resolution Date')
                    ->nullable(),

                Select::make('reason')
                    ->label('Primary Return Reason')
                    ->options($reasonOptions)
                    ->required(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'Draft'              => 'Draft',
                        'Sent to Supplier'   => 'Sent to Supplier',
                        'Acknowledged'       => 'Acknowledged',
                        'Completed'          => 'Completed',
                        'Cancelled'          => 'Cancelled',
                    ])
                    ->default('Draft')->required(),

                Textarea::make('return_notes')
                    ->label('Return Notes to Supplier')
                    ->rows(3)->columnSpanFull()->nullable(),

                Textarea::make('resolution_notes')
                    ->label('Resolution Notes')
                    ->helperText('Fill when supplier responds / resolves the return')
                    ->rows(2)->columnSpanFull()->nullable(),
            ]),

            Section::make('Return Items')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        TextInput::make('description')
                            ->required()->maxLength(300)->columnSpanFull(),

                        TextInput::make('quantity_returned')
                            ->label('Qty to Return')
                            ->numeric()->minValue(0.0001)->default(1)->required(),

                        TextInput::make('unit')
                            ->label('Unit')->nullable(),

                        Select::make('reason')
                            ->label('Item Reason')
                            ->options([
                                'Quality Defect'  => 'Quality Defect',
                                'Wrong Item'      => 'Wrong Item',
                                'Damaged'         => 'Damaged',
                                'Expired'         => 'Expired',
                                'Excess Quantity' => 'Excess Quantity',
                                'Other'           => 'Other',
                            ])
                            ->default('Quality Defect'),

                        Textarea::make('notes')
                            ->label('Item Notes')
                            ->rows(2)->nullable()->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->addActionLabel('+ Add Item')
                    ->defaultItems(1)
                    ->collapsible()
                    ->cloneable()
                    ->itemLabel(fn (array $state) =>
                        ($state['description'] ?? 'Item') .
                        (isset($state['quantity_returned']) ? '  ·  Qty: ' . $state['quantity_returned'] : '')
                    ),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('return_number')
                    ->label('Return #')->searchable()->sortable()->weight('semibold')->copyable(),
                TextColumn::make('goodsReceipt.grn_number')
                    ->label('GRN #')->searchable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier')->searchable()->sortable(),
                TextColumn::make('return_date')->date()->sortable(),
                TextColumn::make('reason')->badge()->color('warning'),
                TextColumn::make('expected_resolution_date')
                    ->label('Due')->date()->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Completed'        => 'success',
                        'Acknowledged'     => 'info',
                        'Sent to Supplier' => 'warning',
                        'Cancelled'        => 'gray',
                        default            => 'gray',
                    }),
            ])
            ->defaultSort('return_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Draft'            => 'Draft',
                        'Sent to Supplier' => 'Sent to Supplier',
                        'Acknowledged'     => 'Acknowledged',
                        'Completed'        => 'Completed',
                        'Cancelled'        => 'Cancelled',
                    ]),
                SelectFilter::make('reason')
                    ->options([
                        'Quality Defect'            => 'Quality Defect',
                        'Wrong Item Delivered'      => 'Wrong Item Delivered',
                        'Quantity Shortage'         => 'Quantity Shortage',
                        'Damaged on Arrival'        => 'Damaged on Arrival',
                        'Expired / Past Shelf Life' => 'Expired / Past Shelf Life',
                        'Other'                     => 'Other',
                    ]),
            ])
            ->recordActions([
                Action::make('send')
                    ->label('Send to Supplier')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (SupplierReturn $r) => $r->status === 'Draft')
                    ->requiresConfirmation()
                    ->modalDescription('Mark this return as sent to supplier. They will need to acknowledge receipt.')
                    ->action(fn (SupplierReturn $r) =>
                        $r->update(['status' => 'Sent to Supplier'])
                        && Notification::make()->title('Return marked as sent to supplier')->warning()->send()
                    ),

                Action::make('acknowledge')
                    ->label('Supplier Acknowledged')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn (SupplierReturn $r) => $r->status === 'Sent to Supplier')
                    ->requiresConfirmation()
                    ->action(fn (SupplierReturn $r) =>
                        $r->update(['status' => 'Acknowledged'])
                        && Notification::make()->title('Supplier acknowledged the return — awaiting resolution')->info()->send()
                    ),

                Action::make('complete')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (SupplierReturn $r) => $r->status === 'Acknowledged')
                    ->form([
                        Textarea::make('resolution_notes')
                            ->label('Resolution Notes')
                            ->helperText('Describe how the supplier resolved this (replacement, credit note, refund, etc.)')
                            ->rows(3)->required(),
                    ])
                    ->action(function (SupplierReturn $r, array $data) {
                        $r->update(['status' => 'Completed', 'resolution_notes' => $data['resolution_notes']]);
                        Notification::make()->title('Return completed — supplier resolved the issue')->success()->send();
                    }),

                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (SupplierReturn $r) => in_array($r->status, ['Draft', 'Sent to Supplier']))
                    ->requiresConfirmation()
                    ->action(fn (SupplierReturn $r) =>
                        $r->update(['status' => 'Cancelled'])
                        && Notification::make()->title('Return cancelled')->send()
                    ),

                EditAction::make(),
                DeleteAction::make()->visible(fn (SupplierReturn $r) => $r->status === 'Draft'),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSupplierReturns::route('/'),
            'create' => CreateSupplierReturn::route('/create'),
            'edit'   => EditSupplierReturn::route('/{record}/edit'),
        ];
    }
}
