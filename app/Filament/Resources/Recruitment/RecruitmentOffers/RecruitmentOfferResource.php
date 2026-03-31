<?php

namespace App\Filament\Resources\Recruitment\RecruitmentOffers;

use App\Models\Recruitment\RecruitmentOffer;
use App\Models\Recruitment\RecruitmentApplication;
use App\Services\Recruitment\RecruitmentApprovalService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use App\Filament\Resources\Recruitment\RecruitmentOffers\Pages\CreateRecruitmentOffer;
use App\Filament\Resources\Recruitment\RecruitmentOffers\Pages\EditRecruitmentOffer;
use App\Filament\Resources\Recruitment\RecruitmentOffers\Pages\ListRecruitmentOffers;
use App\Filament\Resources\Recruitment\RecruitmentOffers\Pages\ViewRecruitmentOffer;
use App\Filament\Resources\Recruitment\RecruitmentOffers\Tables\RecruitmentOffersTable;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecruitmentOfferResource extends Resource
{
    protected static ?string $model = RecruitmentOffer::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Recruitment';
    protected static ?string $navigationLabel = 'Offers';
    protected static ?int $navigationSort = 6;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Offer Details')
                ->icon('heroicon-o-document-check')
                ->columnSpanFull()
                ->schema([
                    Select::make('application_id')
                        ->label('Application')
                        ->relationship(
                            'application',
                            'id',
                            fn (Builder $query) => $query->where('status', 'selected')
                                ->with('candidate', 'campaign')
                        )
                        ->getOptionLabelFromRecordUsing(fn ($record) => 
                            ($record->candidate?->first_name ?? '') . ' ' .
                            ($record->candidate?->last_name ?? '') . ' — ' .
                            ($record->campaign?->title ?? '')
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->unique(ignoreRecord: true),

                    TextInput::make('offered_salary')
                        ->label('Offered Salary')
                        ->numeric()
                        ->prefix('ETB')
                        ->nullable(),

                    DatePicker::make('offer_date')
                        ->label('Offer Date')
                        ->default(now())
                        ->required(),

                    DatePicker::make('offer_expiry_date')
                        ->label('Offer Expiry Date')
                        ->nullable()
                        ->after('offer_date'),

                    FileUpload::make('offer_letter_path')
                        ->label('Offer Letter (Optional)')
                        ->disk('private')
                        ->directory('offer-letters')
                        ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                        ->nullable(),

                    Hidden::make('issued_by')
                        ->default(fn () => auth()->id()),

                    Select::make('approval_workflow_id')
                        ->label('Approval Workflow')
                        ->relationship('approvalWorkflow', 'name',
                            fn (Builder $query) => $query->where('document_type', 'recruitment_offer')->where('is_active', true)
                        )
                        ->preload()
                        ->nullable(),

                    RichEditor::make('notes')
                        ->label('Custom Message/Notes')
                        ->placeholder('Add a custom message for the candidate or notes for the approvers...')
                        ->columnSpanFull()
                        ->nullable(),
                ])->columns(['sm' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentOffersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Offer Details')
                ->icon('heroicon-o-document-check')
                ->schema([
                    TextEntry::make('application.candidate.first_name')
                        ->label('Candidate First Name'),
                    TextEntry::make('application.candidate.last_name')
                        ->label('Candidate Last Name'),
                    TextEntry::make('offered_salary')
                        ->money('ETB'),
                    TextEntry::make('offer_date')
                        ->date(),
                    TextEntry::make('offer_expiry_date')
                        ->date(),
                    TextEntry::make('decline_reason')
                        ->label('Decline Reason')
                        ->placeholder('—')
                        ->columnSpanFull(),
                    
                    TextEntry::make('notes')
                        ->label('Custom Message/Notes')
                        ->html()
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])->columns(['sm' => 2, 'xl' => 3]),

            Section::make('Approval Trail')
                ->icon('heroicon-o-shield-check')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('approval_trail')
                        ->hiddenLabel()
                        ->html()
                        ->getStateUsing(fn (RecruitmentOffer $record) =>
                            RecruitmentApprovalService::renderApprovalTrailHtml($record, 'recruitment_offer')
                        ),
                ])
                ->visible(fn (RecruitmentOffer $record) => $record->status !== RecruitmentOffer::STATUS_DRAFT),
        ]);
    }

    public static function getApprovalWorkflowSelect(): Select
    {
        return Select::make('approval_workflow_id')
            ->label('Approval Workflow')
            ->options(
                \App\Models\Recruitment\RecruitmentApprovalWorkflow::where('document_type', 'recruitment_offer')
                    ->where('is_active', true)
                    ->pluck('name', 'id')
            )
            ->preload()
            ->nullable();
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRecruitmentOffers::route('/'),
            'create' => CreateRecruitmentOffer::route('/create'),
            'view'   => ViewRecruitmentOffer::route('/{record}'),
            'edit'   => EditRecruitmentOffer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
