<?php

namespace App\Filament\Resources\Procurement\Settings;

use App\Models\Procurement\ProcurementApprovalWorkflow;
use App\Models\User;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApprovalWorkflowResource extends Resource
{
    protected static ?string $model = ProcurementApprovalWorkflow::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    // Hidden from main sidebar — accessed via Settings nav item
    protected static bool $shouldRegisterNavigation = false;
    protected static bool $shouldSkipAuthorization  = true;

    protected static string|\UnitEnum|null $navigationGroup  = 'Procurement';
    protected static ?string $navigationParentItem            = 'Settings';
    protected static ?string $navigationLabel                 = 'Approval Workflows';
    protected static ?int $navigationSort                     = 2;

    protected static ?string $recordTitleAttribute = 'name';

    // ── Policy bypasses ──────────────────────────────────────────────
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isProcurementOfficer() || $user->isSuperAdmin());
    }

    public static function canCreate(): bool  { return static::canViewAny(); }
    public static function canEdit($r): bool  { return static::canViewAny(); }
    public static function canDelete($r): bool { return static::canViewAny(); }

    // ── Form ─────────────────────────────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Workflow Details')
                ->columns(2)
                ->schema([
                    Select::make('document_type')
                        ->label('Document Type')
                        ->options(ProcurementApprovalWorkflow::documentTypes())
                        ->required()
                        ->unique(ProcurementApprovalWorkflow::class, 'document_type', ignoreRecord: true)
                        ->helperText('One workflow per document type.'),

                    TextInput::make('name')
                        ->label('Workflow Name')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('e.g., Supplier Invoice Approval'),

                    Textarea::make('description')
                        ->columnSpanFull()
                        ->rows(2)
                        ->nullable(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Only active workflows are applied to new documents.'),
                ]),

            Section::make('Approval Stages')
                ->description('Define the approval chain in order. Approvals are sequential — each stage must be approved before the next activates.')
                ->schema([
                    Repeater::make('stages')
                        ->relationship()
                        ->orderColumn('stage_order')
                        ->columns(3)
                        ->schema([
                            TextInput::make('stage_name')
                                ->label('Stage Name')
                                ->required()
                                ->maxLength(80)
                                ->placeholder('e.g., Finance Manager'),

                            Select::make('required_role')
                                ->label('Required Role')
                                ->options(ProcurementApprovalWorkflow::availableRoles())
                                ->required()
                                ->searchable(),

                            Toggle::make('can_reject')
                                ->label('Can Reject')
                                ->default(true)
                                ->inline(false)
                                ->helperText('Allow this approver to reject.'),
                        ])
                        ->addActionLabel('+ Add Stage')
                        ->reorderable('stage_order')
                        ->cloneable()
                        ->minItems(1),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_type')
                    ->label('Document Type')
                    ->formatStateUsing(fn ($state) => ProcurementApprovalWorkflow::documentTypes()[$state] ?? $state)
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('stages_count')
                    ->label('Stages')
                    ->counts('stages')
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ManageApprovalWorkflows::route('/'),
            'create' => Pages\CreateApprovalWorkflow::route('/create'),
            'edit'   => Pages\EditApprovalWorkflow::route('/{record}/edit'),
        ];
    }
}
