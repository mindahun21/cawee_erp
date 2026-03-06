<?php

namespace App\Filament\Resources\Procurement\Tenders;

use App\Filament\Resources\Procurement\Tenders\Pages\CreateTender;
use App\Filament\Resources\Procurement\Tenders\Pages\EditTender;
use App\Filament\Resources\Procurement\Tenders\Pages\ListTenders;
use App\Models\Procurement\Tender;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TenderResource extends Resource
{
    protected static ?string $model = Tender::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Tenders & RFQs';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tender Details')->columns(2)->schema([
                TextInput::make('tender_number')
                    ->label('Tender No.')
                    ->disabled()->dehydrated()
                    ->placeholder('Auto-generated'),

                Select::make('requisition_id')
                    ->label('Linked Requisition')
                    ->relationship('requisition', 'requisition_number')
                    ->searchable()->preload()->nullable()
                    ->helperText('Optional — link to the originating approved requisition.'),

                TextInput::make('title')->required()->maxLength(300)->columnSpanFull(),

                Select::make('method')
                    ->label('Procurement Method')
                    ->options([
                        'Open Tender'        => 'Open Tender',
                        'Restricted Tender'  => 'Restricted Tender',
                        'Two-Stage Tender'   => 'Two-Stage Tender',
                        'RFP'                => 'Request for Proposal (RFP)',
                        'RFQ'                => 'Request for Quotation (RFQ)',
                        'Direct Procurement' => 'Direct Procurement',
                    ])
                    ->required(),

                TextInput::make('estimated_value')->numeric()->prefix('ETB')->nullable(),
                TextInput::make('currency')->default('ETB')->maxLength(10),

                DatePicker::make('issue_date'),
                DatePicker::make('submission_deadline')->required(),
                DatePicker::make('opening_date')->nullable(),
                DatePicker::make('award_date')->nullable(),

                Textarea::make('description')->rows(3)->columnSpanFull()->nullable(),
                Textarea::make('terms_and_conditions')->rows(3)->columnSpanFull()->nullable(),

                FileUpload::make('attachments')
                    ->label('Tender Documents')
                    ->multiple()->disk('local')->directory('procurement/tenders')
                    ->nullable()->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tender_number')
                    ->label('Tender #')
                    ->searchable()->sortable()->weight('semibold')->copyable()->copyMessage('Copied!'),

                TextColumn::make('title')->searchable()->wrap()->limit(60),

                TextColumn::make('method')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Open Tender'        => 'info',
                        'Restricted Tender'  => 'primary',
                        'Two-Stage Tender'   => 'purple',
                        'RFP'                => 'warning',
                        'RFQ'                => 'gray',
                        'Direct Procurement' => 'success',
                        default              => 'gray',
                    }),

                TextColumn::make('submission_deadline')->date()->sortable(),
                TextColumn::make('award_date')->date()->sortable()->toggleable(),

                TextColumn::make('estimated_value')
                    ->label('Est. Value')
                    ->numeric(2)->prefix('ETB ')->toggleable(),

                TextColumn::make('bids_count')
                    ->label('Bids')
                    ->counts('bids')
                    ->badge()->color('info'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Published'   => 'info',
                        'Evaluation'  => 'warning',
                        'Awarded'     => 'success',
                        'Cancelled'   => 'danger',
                        'Closed'      => 'gray',
                        default       => 'gray',
                    }),
            ])
            ->defaultSort('submission_deadline', 'desc')
            ->filters([
                SelectFilter::make('method')
                    ->options([
                        'Open Tender' => 'Open Tender', 'Restricted Tender' => 'Restricted Tender',
                        'Two-Stage Tender' => 'Two-Stage Tender', 'RFP' => 'RFP',
                        'RFQ' => 'RFQ', 'Direct Procurement' => 'Direct Procurement',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'Draft' => 'Draft', 'Published' => 'Published', 'Closed' => 'Closed',
                        'Evaluation' => 'Evaluation', 'Awarded' => 'Awarded', 'Cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                // Publish tender
                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-megaphone')
                    ->color('info')
                    ->visible(fn (Tender $r) =>
                        $r->status === 'Draft' && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Publish Tender')
                    ->modalDescription('Publishing will make this tender visible to invited suppliers for bid submission.')
                    ->action(fn (Tender $r) =>
                        $r->update(['status' => 'Published', 'issue_date' => now()->toDateString()])
                        && Notification::make()->title('Tender published — open for bids ')->info()->send()
                    ),

                // Close submissions
                Action::make('close_submissions')
                    ->label('Close Submissions')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(fn (Tender $r) =>
                        $r->status === 'Published' && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->action(fn (Tender $r) =>
                        $r->update(['status' => 'Closed'])
                        && Notification::make()->title('Submissions closed — proceed to evaluation')->warning()->send()
                    ),

                // Move to Evaluation
                Action::make('start_evaluation')
                    ->label('Start Evaluation')
                    ->icon('heroicon-o-magnifying-glass-circle')
                    ->color('primary')
                    ->visible(fn (Tender $r) =>
                        $r->status === 'Closed' && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->action(fn (Tender $r) =>
                        $r->update(['status' => 'Evaluation'])
                        && Notification::make()->title('Evaluation phase started')->info()->send()
                    ),

                // Award
                Action::make('award')
                    ->label('Mark Awarded')
                    ->icon('heroicon-o-trophy')
                    ->color('success')
                    ->visible(fn (Tender $r) =>
                        $r->status === 'Evaluation' && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Mark Tender as Awarded')
                    ->modalDescription('Confirm that the evaluation is complete and a supplier has been selected. You can then create a Purchase Order from the awarded bid.')
                    ->action(fn (Tender $r) =>
                        $r->update(['status' => 'Awarded', 'award_date' => now()->toDateString()])
                        && Notification::make()->title('Tender awarded   — generate PO from awarded bid')->success()->send()
                    ),

                // Cancel
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Tender $r) =>
                        ! in_array($r->status, ['Awarded', 'Cancelled'])
                        && auth()->user()->isProcurementOfficer()
                    )
                    ->requiresConfirmation()
                    ->action(fn (Tender $r) =>
                        $r->update(['status' => 'Cancelled'])
                        && Notification::make()->title('Tender cancelled')->danger()->send()
                    ),

                EditAction::make(),
                DeleteAction::make()->visible(fn (Tender $r) => $r->status === 'Draft'),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTenders::route('/'),
            'create' => CreateTender::route('/create'),
            'edit'   => EditTender::route('/{record}/edit'),
        ];
    }
}
