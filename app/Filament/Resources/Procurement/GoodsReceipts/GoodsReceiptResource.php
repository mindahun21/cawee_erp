<?php

namespace App\Filament\Resources\Procurement\GoodsReceipts;

use App\Filament\Resources\Procurement\GoodsReceipts\Pages\CreateGoodsReceipt;
use App\Filament\Resources\Procurement\GoodsReceipts\Pages\EditGoodsReceipt;
use App\Filament\Resources\Procurement\GoodsReceipts\Pages\ListGoodsReceipts;
use App\Models\Procurement\GoodsReceipt;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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

class GoodsReceiptResource extends Resource
{
    protected static ?string $model = GoodsReceipt::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;
    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';
    protected static ?string $navigationLabel = 'Goods Receipts (GRN)';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Receipt Details')->columns(2)->schema([
                TextInput::make('grn_number')->label('GRN #')->disabled()->dehydrated()->placeholder('Auto-generated'),

                Select::make('purchase_order_id')
                    ->label('Purchase Order')
                    ->relationship('purchaseOrder', 'po_number')
                    ->searchable()->preload()->required(),

                DatePicker::make('receipt_date')->required()->default(now()->toDateString()),
                TextInput::make('delivery_note_number')->maxLength(100)->nullable()->label('Delivery Note #'),
                TextInput::make('delivery_location')->maxLength(200)->nullable(),

                Select::make('overall_condition')
                    ->options(['Good' => 'Good', 'Partial' => 'Partial', 'Rejected' => 'Rejected'])
                    ->default('Good')->required(),

                Textarea::make('inspection_notes')->rows(3)->columnSpanFull()->nullable(),

                FileUpload::make('attachments')
                    ->multiple()->disk('local')->directory('procurement/grn')
                    ->nullable()->columnSpanFull(),
            ]),

            Section::make('Items Received')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Select::make('po_item_id')
                            ->label('PO Line Item')
                            ->relationship('poItem', 'description')
                            ->searchable()->preload()->required()->columnSpan(2),
                        TextInput::make('received_quantity')->numeric()->minValue(0)->default(0)->required()->label('Received'),
                        TextInput::make('accepted_quantity')->numeric()->minValue(0)->default(0)->required()->label('Accepted'),
                        TextInput::make('rejected_quantity')->numeric()->minValue(0)->default(0)->label('Rejected'),
                        Select::make('condition')
                            ->options(['Pass' => 'Pass', 'Fail' => 'Fail', 'Partial' => 'Partial'])
                            ->default('Pass'),
                        Textarea::make('inspection_remarks')->rows(2)->nullable()->columnSpanFull()->label('Remarks'),
                    ])
                    ->columns(6)
                    ->addActionLabel('+ Add Item')
                    ->defaultItems(1)
                    ->collapsible(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grn_number')->label('GRN #')->searchable()->sortable()->weight('semibold')->copyable(),
                TextColumn::make('purchaseOrder.po_number')->label('PO #')->searchable()->sortable(),
                TextColumn::make('purchaseOrder.supplier.name')->label('Supplier')->searchable(),
                TextColumn::make('receipt_date')->date()->sortable(),
                TextColumn::make('overall_condition')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Good'     => 'success', 'Partial' => 'warning', 'Rejected' => 'danger', default => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Accepted' => 'success', 'Partial' => 'warning',
                        'Rejected' => 'danger', 'Inspecting' => 'info', default => 'gray',
                    }),
            ])
            ->defaultSort('receipt_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(['Draft' => 'Draft', 'Inspecting' => 'Inspecting', 'Accepted' => 'Accepted', 'Partial' => 'Partial', 'Rejected' => 'Rejected']),
            ])
            ->recordActions([
                Action::make('inspect')
                    ->label('Start Inspection')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->visible(fn (GoodsReceipt $r) => $r->canInspect() && auth()->user()->isProcurementStore())
                    ->requiresConfirmation()
                    ->action(fn (GoodsReceipt $r) =>
                        $r->update(['status' => 'Inspecting', 'inspected_by' => auth()->id(), 'inspected_at' => now()])
                        && Notification::make()->title('Inspection started')->info()->send()
                    ),

                Action::make('accept')
                    ->label('Accept Delivery')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (GoodsReceipt $r) => $r->canApprove() && auth()->user()->isProcurementStore())
                    ->requiresConfirmation()
                    ->modalHeading('Accept Goods Delivery')
                    ->modalDescription('Confirm that goods have been inspected and accepted. This will update inventory and allow invoice matching.')
                    ->action(fn (GoodsReceipt $r) =>
                        $r->update(['status' => 'Accepted', 'approved_by' => auth()->id(), 'approved_at' => now()])
                        && Notification::make()->title('Goods accepted — ready for 3-way matching')->success()->send()
                    ),

                Action::make('partial_accept')
                    ->label('Partial Accept')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (GoodsReceipt $r) => $r->canApprove() && auth()->user()->isProcurementStore())
                    ->requiresConfirmation()
                    ->action(fn (GoodsReceipt $r) =>
                        $r->update(['status' => 'Partial', 'approved_by' => auth()->id(), 'approved_at' => now()])
                        && Notification::make()->title('Partial delivery accepted — backorder noted')->warning()->send()
                    ),

                Action::make('reject')
                    ->label('Reject Delivery')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (GoodsReceipt $r) => $r->canApprove() && auth()->user()->isProcurementStore())
                    ->requiresConfirmation()
                    ->action(fn (GoodsReceipt $r) =>
                        $r->update(['status' => 'Rejected'])
                        && Notification::make()->title('Delivery rejected — return process initiated')->danger()->send()
                    ),

                EditAction::make(),
                DeleteAction::make()->visible(fn (GoodsReceipt $r) => $r->status === 'Draft'),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListGoodsReceipts::route('/'),
            'create' => CreateGoodsReceipt::route('/create'),
            'edit'   => EditGoodsReceipt::route('/{record}/edit'),
        ];
    }
}
