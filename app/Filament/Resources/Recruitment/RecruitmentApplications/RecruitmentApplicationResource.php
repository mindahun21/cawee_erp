<?php

namespace App\Filament\Resources\Recruitment\RecruitmentApplications;

use App\Filament\Resources\Recruitment\RecruitmentApplications\Pages\CreateRecruitmentApplication;
use App\Filament\Resources\Recruitment\RecruitmentApplications\Pages\EditRecruitmentApplication;
use App\Filament\Resources\Recruitment\RecruitmentApplications\Pages\ListRecruitmentApplications;
use App\Filament\Resources\Recruitment\RecruitmentApplications\Pages\ViewRecruitmentApplication;
use App\Filament\Resources\Recruitment\RecruitmentApplications\Pages\KanbanRecruitmentApplications;
use App\Filament\Resources\Recruitment\RecruitmentApplications\Schemas\RecruitmentApplicationForm;
use App\Filament\Resources\Recruitment\RecruitmentApplications\Tables\RecruitmentApplicationsTable;
use App\Models\Recruitment\RecruitmentApplication;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecruitmentApplicationResource extends Resource
{
    protected static ?string $model = RecruitmentApplication::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Recruitment';
    protected static ?string $navigationLabel = 'Applications';
    protected static ?int $navigationSort = 4;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return RecruitmentApplicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentApplicationsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Application Details')
                ->columnSpanFull()
                ->tabs([

                    // ──────────────────────────────────────────────
                    // TAB 1 — Candidate
                    // ──────────────────────────────────────────────
                    Tabs\Tab::make('Candidate')
                        ->icon('heroicon-o-user')
                        ->schema([

                            Section::make('Personal Information')
                                ->icon('heroicon-o-identification')
                                ->columns(['sm' => 3])
                                ->schema([
                                    TextEntry::make('candidate.candidate_code')->label('Candidate Code')->badge()->color('gray'),
                                    TextEntry::make('candidate.first_name')->label('First Name'),
                                    TextEntry::make('candidate.last_name')->label('Last Name'),
                                    TextEntry::make('candidate.gender')->label('Gender'),
                                    TextEntry::make('candidate.birthday')->label('Date of Birth')->date(),
                                    TextEntry::make('candidate.marital_status')->label('Marital Status'),
                                    TextEntry::make('candidate.nationality')->label('Nationality'),
                                    TextEntry::make('candidate.nation')->label('Nation / Ethnicity'),
                                    TextEntry::make('candidate.religion')->label('Religion'),
                                    TextEntry::make('candidate.birthplace')->label('Birthplace'),
                                    TextEntry::make('candidate.home_town')->label('Home Town'),
                                    TextEntry::make('candidate.identification')->label('ID Number'),
                                    TextEntry::make('candidate.days_for_identity')->label('ID Expiry Date')->date(),
                                    TextEntry::make('candidate.place_of_issue')->label('Place of Issue'),
                                ]),

                            Section::make('Contact & Social')
                                ->icon('heroicon-o-phone')
                                ->columns(['sm' => 3])
                                ->schema([
                                    TextEntry::make('candidate.email')->label('Email')->icon('heroicon-o-envelope'),
                                    TextEntry::make('candidate.phone')->label('Phone')->icon('heroicon-o-phone'),
                                    TextEntry::make('candidate.alternate_phone')->label('Alternate Phone'),
                                    TextEntry::make('candidate.skype')->label('Skype'),
                                    TextEntry::make('candidate.facebook')->label('Facebook'),
                                    TextEntry::make('candidate.linkedin_url')->label('LinkedIn')
                                        ->url(fn ($state) => $state, true)
                                        ->color('primary'),
                                    TextEntry::make('candidate.resident')->label('Resident Address')->columnSpanFull(),
                                    TextEntry::make('candidate.current_accommodation')->label('Current Accommodation')->columnSpanFull(),
                                ]),

                            Section::make('Physical & Professional')
                                ->icon('heroicon-o-academic-cap')
                                ->columns(['sm' => 3])
                                ->schema([
                                    TextEntry::make('candidate.height_m')->label('Height (m)')->suffix(' m'),
                                    TextEntry::make('candidate.weight_kg')->label('Weight (kg)')->suffix(' kg'),
                                    TextEntry::make('candidate.seniority')->label('Seniority Level'),
                                    TextEntry::make('candidate.currency')->label('Preferred Currency'),
                                    TextEntry::make('candidate.skills_snapshot')
                                        ->label('Skills')
                                        ->badge()
                                        ->color('success')
                                        ->separator(', '),
                                    TextEntry::make('candidate.interests')->label('Interests')->columnSpanFull(),
                                ]),

                            Section::make('Documents')
                                ->icon('heroicon-o-paper-clip')
                                ->columns(['sm' => 2])
                                ->schema([
                                    TextEntry::make('candidate.resume_path')->label('Resume / CV')
                                        ->formatStateUsing(fn ($state) => $state ? '📄 Download Resume' : '—')
                                        ->url(fn ($record) => $record->candidate?->resume_path ? \Illuminate\Support\Facades\Storage::url($record->candidate->resume_path) : null, true)
                                        ->color('primary'),
                                    TextEntry::make('candidate.photo_path')->label('Photo')
                                        ->formatStateUsing(fn ($state) => $state ? '🖼 View Photo' : '—')
                                        ->url(fn ($record) => $record->candidate?->photo_path ? \Illuminate\Support\Facades\Storage::url($record->candidate->photo_path) : null, true)
                                        ->color('primary'),
                                ]),
                        ]),

                    // ──────────────────────────────────────────────
                    // TAB 2 — Campaign & Position
                    // ──────────────────────────────────────────────
                    Tabs\Tab::make('Campaign & Position')
                        ->icon('heroicon-o-megaphone')
                        ->schema([

                            Section::make('Campaign Overview')
                                ->icon('heroicon-o-flag')
                                ->columns(['sm' => 3])
                                ->schema([
                                    TextEntry::make('campaign.campaign_code')->label('Campaign Code')->badge()->color('gray'),
                                    TextEntry::make('campaign.title')->label('Campaign Title')->weight('bold'),
                                    TextEntry::make('campaign.status')->label('Status')->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'active' => 'success',
                                            'draft' => 'gray',
                                            'paused' => 'warning',
                                            'closed' => 'danger',
                                            default => 'secondary',
                                        }),
                                    TextEntry::make('campaign.employment_type')->label('Employment Type')->badge()->color('info'),
                                    TextEntry::make('campaign.location')->label('Location')->icon('heroicon-o-map-pin'),
                                    TextEntry::make('campaign.is_public')->label('Public Listing')
                                        ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                        ->badge()
                                        ->color(fn ($state) => $state ? 'success' : 'gray'),
                                    TextEntry::make('campaign.start_date')->label('Start Date')->date(),
                                    TextEntry::make('campaign.end_date')->label('End Date')->date(),
                                    TextEntry::make('campaign.manager.name')->label('Hiring Manager'),
                                ]),

                            Section::make('Salary & Budget')
                                ->icon('heroicon-o-banknotes')
                                ->columns(['sm' => 3])
                                ->schema([
                                    TextEntry::make('campaign.salary_min')->label('Salary Min')->money('USD'),
                                    TextEntry::make('campaign.salary_max')->label('Salary Max')->money('USD'),
                                    TextEntry::make('campaign.currency')->label('Currency'),
                                    TextEntry::make('campaign.display_salary')->label('Display Salary?')
                                        ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                                ]),

                            Section::make('Candidate Requirements')
                                ->icon('heroicon-o-clipboard-document-check')
                                ->columns(['sm' => 3])
                                ->schema([
                                    TextEntry::make('campaign.candidate_gender')->label('Required Gender'),
                                    TextEntry::make('campaign.candidate_height_min')->label('Min Height (m)')->suffix(' m'),
                                    TextEntry::make('campaign.candidate_weight_min')->label('Min Weight (kg)')->suffix(' kg'),
                                    TextEntry::make('campaign.candidate_literacy')->label('Required Literacy'),
                                    TextEntry::make('campaign.candidate_seniority')->label('Required Seniority'),
                                    TextEntry::make('campaign.skills.name')->label('Required Skills')->badge()->color('primary'),
                                ]),

                            Section::make('Campaign Description & Requirements')
                                ->icon('heroicon-o-document-text')
                                ->schema([
                                    TextEntry::make('campaign.description')->label('Description')->html()->columnSpanFull(),
                                    TextEntry::make('campaign.requirements')->label('Requirements')->html()->columnSpanFull(),
                                    TextEntry::make('campaign.reason_for_recruitment')->label('Reason for Recruitment')->columnSpanFull(),
                                    TextEntry::make('campaign.notes')->label('Internal Notes')->columnSpanFull(),
                                ]),

                            Section::make('Job Position')
                                ->icon('heroicon-o-briefcase')
                                ->columns(['sm' => 3])
                                ->collapsible()
                                ->schema([
                                    TextEntry::make('campaign.jobPosition.title')->label('Position Title')->weight('bold'),
                                    TextEntry::make('campaign.jobPosition.department.name')->label('Department')->badge()->color('info'),
                                    TextEntry::make('campaign.jobPosition.grade.name')->label('Grade'),
                                    TextEntry::make('campaign.jobPosition.vacancy_count')->label('Total Vacancies'),
                                    TextEntry::make('campaign.jobPosition.salary_min')->label('Position Salary Min')->money('ETB'),
                                    TextEntry::make('campaign.jobPosition.salary_max')->label('Position Salary Max')->money('ETB'),
                                    TextEntry::make('campaign.jobPosition.is_active')->label('Position Active')
                                        ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                        ->badge()
                                        ->color(fn ($state) => $state ? 'success' : 'gray'),
                                    TextEntry::make('campaign.jobPosition.description')->label('Job Description')->html()->columnSpanFull(),
                                    TextEntry::make('campaign.jobPosition.requirements')->label('Job Requirements')->html()->columnSpanFull(),
                                ]),

                            Section::make('Recruitment Channel')
                                ->icon('heroicon-o-signal')
                                ->columns(['sm' => 3])
                                ->collapsible()
                                ->schema([
                                    TextEntry::make('channel.name')->label('Channel Name'),
                                    TextEntry::make('channel.type')->label('Channel Type')->badge(),
                                    TextEntry::make('channel.status')->label('Channel Status')->badge(),
                                    TextEntry::make('channel.language')->label('Language'),
                                    TextEntry::make('channel.is_active')->label('Active')
                                        ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                        ->badge()
                                        ->color(fn ($state) => $state ? 'success' : 'gray'),
                                ]),

                            Section::make('Recruitment Plan')
                                ->icon('heroicon-o-calendar-days')
                                ->columns(['sm' => 3])
                                ->collapsible()
                                ->schema([
                                    TextEntry::make('campaign.recruitmentPlan.title')->label('Plan Title'),
                                    TextEntry::make('campaign.recruitmentPlan.status')->label('Plan Status')->badge(),
                                    TextEntry::make('campaign.recruitmentPlan.working_from')->label('Working Type'),
                                    TextEntry::make('campaign.recruitmentPlan.workplace')->label('Workplace'),
                                    TextEntry::make('campaign.recruitmentPlan.salary_from')->label('Salary From')->money('ETB'),
                                    TextEntry::make('campaign.recruitmentPlan.salary_to')->label('Salary To')->money('ETB'),
                                    TextEntry::make('campaign.recruitmentPlan.start_date')->label('Plan Start Date')->date(),
                                    TextEntry::make('campaign.recruitmentPlan.end_date')->label('Plan End Date')->date(),
                                    TextEntry::make('campaign.recruitmentPlan.budget')->label('Budget')->money('ETB'),
                                    TextEntry::make('campaign.recruitmentPlan.notes')->label('Plan Notes')->columnSpanFull(),
                                ]),
                        ]),

                    // ──────────────────────────────────────────────
                    // TAB 3 — Application
                    // ──────────────────────────────────────────────
                    Tabs\Tab::make('Application')
                        ->icon('heroicon-o-document-text')
                        ->schema([

                            Section::make('Application Status')
                                ->icon('heroicon-o-check-circle')
                                ->columns(['sm' => 3])
                                ->schema([
                                    TextEntry::make('status')->label('Current Status')->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'applied' => 'info',
                                            'under_review' => 'warning',
                                            'shortlisted' => 'success',
                                            'interview_scheduled' => 'primary',
                                            'offer_pending' => 'warning',
                                            'offer_accepted', 'hired' => 'success',
                                            'offer_declined', 'rejected' => 'danger',
                                            'withdrawn' => 'gray',
                                            default => 'secondary',
                                        }),
                                    TextEntry::make('applied_at')->label('Applied At')->dateTime(),
                                    TextEntry::make('created_at')->label('Record Created')->dateTime(),
                                    TextEntry::make('reviewer.name')->label('Reviewed By'),
                                    TextEntry::make('shortlister.name')->label('Shortlisted By'),
                                ]),

                            Section::make('Candidate Submission')
                                ->icon('heroicon-o-pencil-square')
                                ->schema([
                                    TextEntry::make('desired_salary')->label('Desired Salary (Application)')->money('USD'),
                                    TextEntry::make('introduce_yourself')->label('Self Introduction')->columnSpanFull(),
                                    TextEntry::make('cover_letter')->label('Cover Letter')->columnSpanFull(),
                                ]),

                            Section::make('Internal Review')
                                ->icon('heroicon-o-chat-bubble-left-right')
                                ->schema([
                                    TextEntry::make('rejection_reason')->label('Rejection Reason')->columnSpanFull(),
                                    TextEntry::make('internal_notes')->label('Internal Notes')->columnSpanFull(),
                                ]),
                        ]),

                ]),
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
            'index' => ListRecruitmentApplications::route('/'),
            'kanban' => KanbanRecruitmentApplications::route('/kanban'),
            'create' => CreateRecruitmentApplication::route('/create'),
            'view' => ViewRecruitmentApplication::route('/{record}'),
            'edit' => EditRecruitmentApplication::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with([
                'candidate',
                'campaign.jobPosition.department',
                'campaign.jobPosition.grade',
                'campaign.channel',
                'campaign.recruitmentPlan',
                'campaign.manager',
                'campaign.skills',
                'channel',
                'reviewer',
                'shortlister',
            ]);
    }
}
