<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class NotifyOverdueTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notify-overdue-tasks';

    protected $description = 'Notify employees about overdue tasks';

    public function handle()
    {
        $overdueTasks = \App\Models\Task::where('status', '!=', 'completed')
            ->where('deadline', '<', now())
            ->with(['employee.user', 'plan'])
            ->get();

        foreach ($overdueTasks as $task) {
            if ($task->employee && $task->employee->user) {
                \Filament\Notifications\Notification::make()
                    ->title('Overdue Task Alert')
                    ->body("The task \"{$task->title}\" for plan \"{$task->plan->title}\" is overdue!")
                    ->danger()
                    ->sendToDatabase($task->employee->user);
            }
        }

        $this->info("Notified " . $overdueTasks->count() . " overdue tasks.");
    }
}
