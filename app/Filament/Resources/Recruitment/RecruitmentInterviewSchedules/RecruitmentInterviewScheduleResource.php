<?php

namespace App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules;

use App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\Pages\CreateRecruitmentInterviewSchedule;
use App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\Pages\EditRecruitmentInterviewSchedule;
use App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\Pages\ListRecruitmentInterviewSchedules;
use App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\Pages\ViewRecruitmentInterviewSchedule;
use App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\Schemas\RecruitmentInterviewScheduleForm;
use App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\Tables\RecruitmentInterviewSchedulesTable;
use App\Models\Recruitment\RecruitmentInterviewSchedule;
use App\Services\Recruitment\RecruitmentApprovalService;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecruitmentInterviewScheduleResource extends Resource
{
    protected static ?string $model = RecruitmentInterviewSchedule::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Recruitment';
    protected static ?string $navigationLabel = 'Interview Schedules';
    protected static ?int $navigationSort = 5;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    public static function form(Schema $schema): Schema
    {
        return RecruitmentInterviewScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentInterviewSchedulesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Schedule Snapshot')
                ->icon('heroicon-o-calendar')
                ->schema([
                    TextEntry::make('name')->weight('bold')->size('lg'),
                    TextEntry::make('campaign.title')->label('Campaign'),
                    TextEntry::make('interview_date')->date(),
                    TextEntry::make('time_range')
                        ->label('Time')
                        ->getStateUsing(fn (RecruitmentInterviewSchedule $record) => ($record->from_time ?? '—') . ' – ' . ($record->to_time ?? '—')),
                    TextEntry::make('interview_type')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => ucwords(str_replace('_', ' ', $state))),
                    TextEntry::make('location')
                        ->label(fn (RecruitmentInterviewSchedule $record) => $record->interview_type === 'online' ? 'Meeting Link' : 'Location')
                        ->url(fn (RecruitmentInterviewSchedule $record) => $record->interview_type === 'online' ? $record->location : null)
                        ->openUrlInNewTab()
                        ->color(fn (RecruitmentInterviewSchedule $record) => $record->interview_type === 'online' ? 'primary' : null)
                        ->icon(fn (RecruitmentInterviewSchedule $record) => $record->interview_type === 'online' ? 'heroicon-o-video-camera' : 'heroicon-o-map-pin'),
                    TextEntry::make('evaluationTemplate.name')->label('Evaluation Template'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'draft' => 'gray',
                            'submitted' => 'warning',
                            'scheduled' => 'success',
                            'completed' => 'primary',
                            'cancelled' => 'danger',
                            'rejected' => 'danger',
                            default => 'gray',
                        }),
                ])->columns(['sm' => 2, 'xl' => 4]),

            Section::make('Interview Panel')
                ->icon('heroicon-o-users')
                ->schema([
                    \Filament\Infolists\Components\RepeatableEntry::make('scheduleInterviewers')
                        ->label('Panelists')
                        ->schema([
                            TextEntry::make('user.name')->label('Name')->weight('bold'),
                            TextEntry::make('role')->badge()->color('info')->formatStateUsing(fn (string $state) => ucfirst($state)),
                            TextEntry::make('notes')->label('Notes')->columnSpanFull(),
                        ])
                        ->columns(['md' => 2])
                        ->grid(['md' => 2]),
                ]),

            Section::make('Approval Trail')
                ->icon('heroicon-o-shield-check')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('approval_trail')
                        ->hiddenLabel()
                        ->html()
                        ->getStateUsing(fn (RecruitmentInterviewSchedule $record) => RecruitmentApprovalService::renderApprovalTrailHtml($record, 'recruitment_interview_schedule')),
                ])
                ->visible(fn (RecruitmentInterviewSchedule $record) => $record->status !== RecruitmentInterviewSchedule::STATUS_DRAFT),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CandidatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecruitmentInterviewSchedules::route('/'),
            'calendar' => Pages\CalendarRecruitmentInterviewSchedules::route('/calendar'),
            'create' => CreateRecruitmentInterviewSchedule::route('/create'),
            'view' => ViewRecruitmentInterviewSchedule::route('/{record}'),
            'edit' => EditRecruitmentInterviewSchedule::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->where('status', '!=', RecruitmentInterviewSchedule::STATUS_DRAFT)
                      ->orWhere('created_by', auth()->id());
            });
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery();
    }
}
