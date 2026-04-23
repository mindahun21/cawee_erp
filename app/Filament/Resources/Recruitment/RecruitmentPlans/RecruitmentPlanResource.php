<?php

namespace App\Filament\Resources\Recruitment\RecruitmentPlans;

use App\Filament\Resources\Recruitment\RecruitmentPlans\Pages\CreateRecruitmentPlan;
use App\Filament\Resources\Recruitment\RecruitmentPlans\Pages\EditRecruitmentPlan;
use App\Filament\Resources\Recruitment\RecruitmentPlans\Pages\ListRecruitmentPlans;
use App\Filament\Resources\Recruitment\RecruitmentPlans\Pages\ViewRecruitmentPlan;
use App\Filament\Resources\Recruitment\RecruitmentPlans\Schemas\RecruitmentPlanForm;
use App\Filament\Resources\Recruitment\RecruitmentPlans\Tables\RecruitmentPlansTable;
use App\Models\Recruitment\RecruitmentPlan;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecruitmentPlanResource extends Resource
{
    protected static ?string $model = RecruitmentPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Recruitment';

    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Plans';
    protected static ?string $modelLabel = 'Plan';
    protected static ?string $pluralModelLabel = 'Plans';

    public static function form(Schema $schema): Schema
    {
        return RecruitmentPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentPlansTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('General Information')
                ->icon('heroicon-o-briefcase')
                ->columnSpanFull()
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('department.name')
                            ->label('Department'),
                        TextEntry::make('jobPosition.title')
                            ->label('Position')
                            ->weight('bold'),
                        TextEntry::make('manager.name')
                            ->label('Hiring Manager'),
                        TextEntry::make('vacancies_needed')
                            ->label('Vacancies'),
                        TextEntry::make('working_from')
                            ->label('Working From'),
                        TextEntry::make('workplace')
                            ->label('Workplace / Location')
                            ->default('—'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => RecruitmentPlan::statusLabel($state))
                            ->color(fn (string $state) => RecruitmentPlan::statusColor($state)),
                        TextEntry::make('approvalWorkflow.name')
                            ->label('Approval Workflow')
                            ->default('— None —'),
                        TextEntry::make('creator.name')
                            ->label('Created By'),
                    ]),
                ]),

            Section::make('Salary & Budget')
                ->icon('heroicon-o-banknotes')
                ->columnSpanFull()
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('salary_currency')
                            ->label('Currency'),
                        TextEntry::make('salary_from')
                            ->label('Salary From')
                            ->numeric(decimalPlaces: 2)
                            ->default('—'),
                        TextEntry::make('salary_to')
                            ->label('Salary To')
                            ->numeric(decimalPlaces: 2)
                            ->default('—'),
                        TextEntry::make('budget')
                            ->label('Budget')
                            ->numeric(decimalPlaces: 2)
                            ->default('—'),
                    ]),
                ]),

            Section::make('Recruitment Timeline')
                ->icon('heroicon-o-calendar-days')
                ->columnSpanFull()
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('start_date')
                            ->label('Start Date')
                            ->date(),
                        TextEntry::make('end_date')
                            ->label('End Date')
                            ->date(),
                    ]),
                ]),

            Section::make('Previous Rejection Reason')
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('danger')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('rejection_notes')
                        ->hiddenLabel()
                        ->html()
                        ->getStateUsing(fn (RecruitmentPlan $record) => '<div class="text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg p-4">'
                            . '<strong>This plan was previously returned for revision.</strong><br><br>'
                            . '<strong>Reason:</strong> '
                            . e(\App\Services\Recruitment\RecruitmentApprovalService::previousRejectionNotes($record) ?? 'No reason provided.')
                            . '</div>'),
                ])
                ->visible(fn (RecruitmentPlan $record) => $record->status === RecruitmentPlan::STATUS_DRAFT
                    && \App\Services\Recruitment\RecruitmentApprovalService::hasBeenRejected($record)),

            Section::make('Reason for Recruitment')
                ->icon('heroicon-o-document-text')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('reason')
                        ->html()
                        ->columnSpanFull()
                        ->hiddenLabel(),
                ])
                ->visible(fn (RecruitmentPlan $record) => ! empty($record->reason)),

            Section::make('Job Description')
                ->icon('heroicon-o-clipboard-document-list')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('job_description')
                        ->html()
                        ->columnSpanFull()
                        ->hiddenLabel(),
                ])
                ->visible(fn (RecruitmentPlan $record) => ! empty($record->job_description)),

            Section::make('Approval Trail')
                ->icon('heroicon-o-shield-check')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('approval_trail')
                        ->hiddenLabel()
                        ->html()
                        ->getStateUsing(fn (RecruitmentPlan $record) => \App\Services\Recruitment\RecruitmentApprovalService::renderApprovalTrailHtml($record, 'recruitment_plan')),
                ])
                ->visible(fn (RecruitmentPlan $record) => $record->status !== RecruitmentPlan::STATUS_DRAFT),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecruitmentPlans::route('/'),
            'create' => CreateRecruitmentPlan::route('/create'),
            'view' => ViewRecruitmentPlan::route('/{record}'),
            'edit' => EditRecruitmentPlan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->where('status', '!=', \App\Models\Recruitment\RecruitmentPlan::STATUS_DRAFT)
                      ->orWhere('created_by', auth()->id());
            })
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

