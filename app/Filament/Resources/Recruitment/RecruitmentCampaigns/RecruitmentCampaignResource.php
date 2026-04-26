<?php

namespace App\Filament\Resources\Recruitment\RecruitmentCampaigns;

use App\Filament\Resources\Recruitment\RecruitmentCampaigns\Pages\CreateRecruitmentCampaign;
use App\Filament\Resources\Recruitment\RecruitmentCampaigns\Pages\EditRecruitmentCampaign;
use App\Filament\Resources\Recruitment\RecruitmentCampaigns\Pages\ListRecruitmentCampaigns;
use App\Filament\Resources\Recruitment\RecruitmentCampaigns\Schemas\RecruitmentCampaignForm;
use App\Filament\Resources\Recruitment\RecruitmentCampaigns\Tables\RecruitmentCampaignsTable;
use App\Models\Recruitment\RecruitmentCampaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\BelongsToModule;

class RecruitmentCampaignResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = RecruitmentCampaign::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Recruitment';
    protected static ?string $navigationLabel = 'Campaigns';
    protected static ?int $navigationSort = 2;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    public static function form(Schema $schema): Schema
    {
        return RecruitmentCampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentCampaignsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Section::make('Campaign Snapshot')
                ->icon('heroicon-o-megaphone')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('title')->weight('bold')->size('lg'),
                    \Filament\Infolists\Components\TextEntry::make('jobPosition.title')->label('Position'),
                    \Filament\Infolists\Components\TextEntry::make('channel.name')->label('Source Channel'),
                    \Filament\Infolists\Components\TextEntry::make('employment_type')->label('Type')
                        ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
                    \Filament\Infolists\Components\TextEntry::make('vacancies_needed')->label('Vacancies'),
                    \Filament\Infolists\Components\TextEntry::make('location')->label('Location')->default('—'),
                    \Filament\Infolists\Components\TextEntry::make('salary_range')
                        ->label('Salary')
                        ->getStateUsing(fn (RecruitmentCampaign $record) => $record->salary_min || $record->salary_max ? $record->salary_min . ' - ' . $record->salary_max . ' ' . $record->currency : '—')
                        ->visible(fn (RecruitmentCampaign $record) => (bool) $record->display_salary),
                    \Filament\Infolists\Components\TextEntry::make('start_date')->date(),
                    \Filament\Infolists\Components\TextEntry::make('end_date')->date(),
                    \Filament\Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'draft' => 'gray',
                            'submitted' => 'warning',
                            'active' => 'success',
                            'paused' => 'warning',
                            'closed' => 'danger',
                            'rejected' => 'danger',
                            default => 'gray',
                        }),
                    \Filament\Infolists\Components\TextEntry::make('approvalWorkflow.name')
                        ->label('Approval Workflow')
                        ->default('— None —'),
                ])->columns(['sm' => 2, 'xl' => 4]),

            \Filament\Schemas\Components\Section::make('Description')
                ->icon('heroicon-o-document-text')
                ->collapsible()
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('description')
                        ->hiddenLabel()
                        ->html()->columnSpanFull()->default('—'),
                ]),

            \Filament\Schemas\Components\Section::make('Requirements')
                ->icon('heroicon-o-clipboard-document-check')
                ->collapsible()
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('requirements')
                        ->hiddenLabel()
                        ->html()->columnSpanFull()->default('—'),
                ]),

            \Filament\Schemas\Components\Section::make('Internal Reason for Recruitment')
                ->icon('heroicon-o-information-circle')
                ->collapsible()
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('reason_for_recruitment')
                        ->hiddenLabel()
                        ->html()->columnSpanFull()->default('—'),
                ]),

            \Filament\Schemas\Components\Section::make('Candidate Metrics Profile')
                ->icon('heroicon-o-users')
                ->collapsible()
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('candidate_age_from')->label('Min Age')->default('—'),
                    \Filament\Infolists\Components\TextEntry::make('candidate_age_to')->label('Max Age')->default('—'),
                    \Filament\Infolists\Components\TextEntry::make('candidate_gender')->label('Gender')->default('Any')->formatStateUsing(fn ($state) => ucfirst($state)),
                    \Filament\Infolists\Components\TextEntry::make('candidate_literacy')->label('Education')->default('—')->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
                    \Filament\Infolists\Components\TextEntry::make('candidate_seniority')->label('Seniority')->default('—')->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
                ])->columns(['sm' => 3, 'xl' => 5]),

            \Filament\Schemas\Components\Section::make('Previous Rejection Reason')
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('danger')
                ->columnSpanFull()
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('rejection_notes')
                        ->hiddenLabel()
                        ->html()
                        ->getStateUsing(fn (RecruitmentCampaign $record) => '<div class="text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg p-4">'
                            . '<strong>This campaign was previously returned for revision.</strong><br><br>'
                            . '<strong>Reason:</strong> '
                            . e(\App\Services\Recruitment\RecruitmentApprovalService::previousRejectionNotes($record) ?? 'No reason provided.')
                            . '</div>'),
                ])
                ->visible(fn (RecruitmentCampaign $record) => $record->status === RecruitmentCampaign::STATUS_DRAFT
                    && \App\Services\Recruitment\RecruitmentApprovalService::hasBeenRejected($record)),

            \Filament\Schemas\Components\Section::make('Approval Trail')
                ->icon('heroicon-o-shield-check')
                ->columnSpanFull()
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('approval_trail')
                        ->hiddenLabel()
                        ->html()
                        ->getStateUsing(fn (RecruitmentCampaign $record) => \App\Services\Recruitment\RecruitmentApprovalService::renderApprovalTrailHtml($record, 'recruitment_campaign')),
                ])
                ->visible(fn (RecruitmentCampaign $record) => $record->status !== RecruitmentCampaign::STATUS_DRAFT),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ApplicationsRelationManager::class,
            RelationManagers\EvaluatedApplicationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecruitmentCampaigns::route('/'),
            'create' => CreateRecruitmentCampaign::route('/create'),
            'view' => Pages\ViewRecruitmentCampaign::route('/{record}'),
            'edit' => EditRecruitmentCampaign::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->where('status', '!=', \App\Models\Recruitment\RecruitmentCampaign::STATUS_DRAFT)
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
