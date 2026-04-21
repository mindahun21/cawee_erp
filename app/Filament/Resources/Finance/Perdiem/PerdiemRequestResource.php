<?php

namespace App\Filament\Resources\Finance\Perdiem;

use App\Filament\Resources\Finance\Perdiem\Pages\CreatePerdiemRequests;
use App\Filament\Resources\Finance\Perdiem\Pages\ListPerdiemRequests;
use App\Filament\Resources\Finance\Perdiem\Pages\ViewPerdiemRequests;
use App\Models\Donor;
use App\Models\Employee;
use App\Models\Finance\CostCenter;
use App\Models\Finance\PerdiemRequest;
use App\Models\Finance\PerdiemType;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\Action as TblAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PerdiemRequestResource extends Resource
{
    protected static ?string $model                          = PerdiemRequest::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';
    protected static string|UnitEnum|null $navigationGroup  = 'Finance';
    protected static ?string $navigationParentItem          = 'Per Diem';
    protected static ?string $navigationLabel               = 'Per Diem Requests';
    protected static ?int    $navigationSort                = 75;
    protected static ?string $slug                          = 'finance/perdiem/requests';
    protected static bool $shouldSkipAuthorization          = true;

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        if (! $u) {
            return true;
        }

        return $u->isFinanceOfficer() || $u->isFinanceManager() || $u->isSuperAdmin();
    }
    public static function canCreate(): bool   { return static::canViewAny(); }
    public static function canEdit($r): bool   { return $r->isDraft() && static::canViewAny(); }
    public static function canDelete($r): bool { return $r->isDraft() && static::canViewAny(); }
    public static function canView($r): bool   { return static::canViewAny(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Request Details')->icon('heroicon-o-map-pin')->columns(3)->schema([
                TextInput::make('reference')->label('Reference')->disabled()->dehydrated()->placeholder('Auto-generated'),
                Select::make('employee_id')->label('Employee')->required()->native(false)->searchable()
                    ->options(fn () => Employee::orderBy('first_name')
                        ->get()->mapWithKeys(fn ($e) => [$e->id => $e->full_name])),
                Select::make('perdiem_type_id')->label('Per Diem Type')->required()->native(false)
                    ->options(fn () => PerdiemType::activeOptions()),
                TextInput::make('travel_destination')->label('Destination')->required()->maxLength(200)->columnSpan(2),
                TextInput::make('purpose')->label('Purpose')->required()->maxLength(500),
            ]),

            Section::make('Travel Dates & Rates')->icon('heroicon-o-calendar-days')->columns(4)->schema([
                DatePicker::make('start_date')->label('Start Date')->required(),
                DatePicker::make('end_date')->label('End Date')->required(),
                TextInput::make('days_count')->label('Days')->numeric()->required()->minValue(1)->default(1),
                Select::make('currency_id')->label('Currency')->native(false)->nullable()
                    ->options(fn () => \App\Models\Currency::orderBy('code')->pluck('code', 'id')),
                TextInput::make('daily_rate')->label('Daily Rate')->numeric()->required()->default(0),
                TextInput::make('total_requested')->label('Total Requested')->numeric()->required()->default(0),
                Toggle::make('advance_requested')->label('Request Advance?')->default(false)->columnSpan(1),
                TextInput::make('amount_advanced')->label('Advance Amount')->numeric()->default(0)
                    ->visible(fn ($get) => $get('advance_requested')),
            ]),

            Section::make('4-Dimension Coding')->icon('heroicon-o-tag')->columns(4)->schema([
                Select::make('cost_center_id')->label('Cost Center')->native(false)->nullable()
                    ->options(fn () => CostCenter::where('is_active', true)->pluck('name', 'id')),
                Select::make('project_id')->label('Project')->native(false)->nullable()
                    ->options(fn () => Project::orderBy('project_name')->pluck('project_name', 'id')),
                Select::make('donor_id')->label('Donor')->native(false)->nullable()
                    ->options(fn () => Donor::orderBy('first_name')->get()->mapWithKeys(fn ($d) => [$d->id => $d->full_name])),
                TextInput::make('activity_code')->label('Activity Code')->nullable(),
            ]),

            Section::make('Notes')->schema([Textarea::make('notes')->rows(2)->nullable()->columnSpanFull()])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Ref #')->badge()->color('primary')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('employee.first_name')->label('Employee')
                    ->formatStateUsing(fn ($state, $record) => $record->employee?->full_name ?? '—')->searchable(),
                TextColumn::make('perdiemType.name')->label('Type')->badge()->color('gray'),
                TextColumn::make('travel_destination')->label('Destination')->limit(25),
                TextColumn::make('start_date')->label('Start')->date()->sortable(),
                TextColumn::make('days_count')->label('Days')->alignCenter(),
                TextColumn::make('total_requested')->label('Total')->numeric(decimalPlaces: 2)->alignEnd()->fontFamily('mono'),
                TextColumn::make('advance_requested')->label('Advance')
                    ->formatStateUsing(fn ($s) => $s ? 'Yes' : 'No'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($s) => match($s) {
                        'draft'     => 'gray', 'pending' => 'warning',
                        'approved'  => 'success', 'rejected' => 'danger',
                        'settled'   => 'info', 'cancelled' => 'gray',
                        default     => 'gray',
                    }),
            ])
            ->filters([SelectFilter::make('status')->options(PerdiemRequest::statuses())])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (PerdiemRequest $r) => $r->isDraft()),
                DeleteAction::make()->visible(fn (PerdiemRequest $r) => $r->isDraft()),

                TblAction::make('tbl_submit')->label('Submit')->icon('heroicon-o-paper-airplane')
                    ->color('warning')->button()
                    ->visible(fn (PerdiemRequest $r) => $r->isDraft())
                    ->requiresConfirmation()
                    ->action(function (PerdiemRequest $record) {
                        $record->forceFill(['status' => 'pending'])->save();
                        Notification::make()->success()->title('Per diem request submitted.')->send();
                    }),

                TblAction::make('tbl_approve')->label('Approve')->icon('heroicon-o-check-badge')
                    ->color('success')->button()
                    ->visible(fn (PerdiemRequest $r) =>
                        $r->isPending() && (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin()))
                    ->requiresConfirmation()
                    ->action(function (PerdiemRequest $record) {
                        $record->forceFill([
                            'status'      => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ])->save();
                        Notification::make()->success()->title('Per diem request approved.')->send();
                    }),

                TblAction::make('tbl_reject')->label('Reject')->icon('heroicon-o-x-circle')
                    ->color('danger')->button()
                    ->visible(fn (PerdiemRequest $r) =>
                        $r->isPending() && (auth()->user()?->isFinanceManager() || auth()->user()?->isSuperAdmin()))
                    ->requiresConfirmation()
                    ->action(function (PerdiemRequest $record) {
                        $record->forceFill(['status' => 'rejected'])->save();
                        Notification::make()->danger()->title('Per diem request rejected.')->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Request')->icon('heroicon-o-map-pin')->columns(4)->schema([
                TextEntry::make('reference')->label('Ref #')->badge()->color('primary')->fontFamily('mono'),
                TextEntry::make('employee.first_name')->label('Employee')
                    ->formatStateUsing(fn ($state, $record) => $record->employee?->full_name ?? '—'),
                TextEntry::make('perdiemType.name')->label('Type')->badge()->color('gray'),
                TextEntry::make('status')->label('Status')->badge()
                    ->color(fn ($s) => match($s) {
                        'draft' => 'gray', 'pending' => 'warning', 'approved' => 'success',
                        'rejected' => 'danger', 'settled' => 'info', default => 'gray',
                    }),
                TextEntry::make('travel_destination')->label('Destination')->columnSpan(2),
                TextEntry::make('purpose')->label('Purpose')->columnSpan(2),
            ]),
            Section::make('Dates & Amounts')->icon('heroicon-o-calculator')->columns(4)->schema([
                TextEntry::make('start_date')->label('Start Date')->date(),
                TextEntry::make('end_date')->label('End Date')->date(),
                TextEntry::make('days_count')->label('Days'),
                TextEntry::make('currency.code')->label('Currency')->badge()->color('gray'),
                TextEntry::make('daily_rate')->label('Daily Rate')->numeric(decimalPlaces: 2)->fontFamily('mono'),
                TextEntry::make('total_requested')->label('Total Requested')->numeric(decimalPlaces: 2)->fontFamily('mono')->weight('bold'),
                TextEntry::make('advance_requested')->label('Advance Requested')
                    ->formatStateUsing(fn ($s) => $s ? '✓ Yes' : '✗ No'),
                TextEntry::make('amount_advanced')->label('Amount Advanced')->numeric(decimalPlaces: 2)->fontFamily('mono'),
            ]),
            Section::make('Dimension Coding')->icon('heroicon-o-tag')->columns(4)->schema([
                TextEntry::make('costCenter.name')->label('Cost Center')->placeholder('—'),
                TextEntry::make('project.project_name')->label('Project')->placeholder('—'),
                TextEntry::make('donor.full_name')->label('Donor')->placeholder('—'),
                TextEntry::make('activity_code')->label('Activity Code')->placeholder('—'),
            ]),
            Section::make('Approval')->icon('heroicon-o-clipboard-document-check')->columns(3)->schema([
                TextEntry::make('preparedBy.name')->label('Prepared By')->placeholder('—'),
                TextEntry::make('approvedBy.name')->label('Approved By')->placeholder('Pending'),
                TextEntry::make('approved_at')->label('Approved At')->dateTime()->placeholder('—'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPerdiemRequests::route('/'),
            'create' => CreatePerdiemRequests::route('/create'),
            'view'   => ViewPerdiemRequests::route('/{record}'),
            'edit'   => \App\Filament\Resources\Finance\Perdiem\Pages\EditPerdiemRequests::route('/{record}/edit'),
        ];
    }
}
