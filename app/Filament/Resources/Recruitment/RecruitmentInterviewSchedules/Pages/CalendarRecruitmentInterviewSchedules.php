<?php

namespace App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\Pages;

use App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\RecruitmentInterviewScheduleResource;
use App\Models\Recruitment\RecruitmentInterviewSchedule;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CalendarRecruitmentInterviewSchedules extends Page
{
    protected static string $resource = RecruitmentInterviewScheduleResource::class;
    protected string $view = 'filament.resources.recruitment.schedule-calendar';
    protected static ?string $title = 'Schedule Calendar';

    public string $currentDate;

    public function mount()
    {
        $this->currentDate = now()->format('Y-m-d');
    }

    public function previousMonth()
    {
        $this->currentDate = Carbon::parse($this->currentDate)->subMonth()->format('Y-m-d');
    }

    public function nextMonth()
    {
        $this->currentDate = Carbon::parse($this->currentDate)->addMonth()->format('Y-m-d');
    }

    public function today()
    {
        $this->currentDate = now()->format('Y-m-d');
    }

    public function getDays()
    {
        $date = Carbon::parse($this->currentDate);
        $startOfCalendar = $date->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $endOfCalendar = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        $period = CarbonPeriod::create($startOfCalendar, '1 day', $endOfCalendar);
        
        $schedules = RecruitmentInterviewSchedule::query()
            ->whereBetween('interview_date', [$startOfCalendar, $endOfCalendar])
            ->whereIn('status', ['scheduled', 'completed'])
            ->get()
            ->groupBy(fn ($s) => $s->interview_date->format('Y-m-d'));

        foreach ($period as $dt) {
            $days[] = [
                'date' => $dt->copy(),
                'isCurrentMonth' => $dt->month === $date->month,
                'isToday' => $dt->isToday(),
                'schedules' => $schedules->get($dt->format('Y-m-d'), []),
            ];
        }
        
        return $days;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list_view')
                ->label('List View')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->url(RecruitmentInterviewScheduleResource::getUrl('index')),
            CreateAction::make(),
        ];
    }
}
