<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:check-overdue-tasks')]
#[Description('Checks for overdue tasks and sends notifications and 24h reminders')]
class CheckOverdueTasks extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        // 1. Overdue Tasks Check (due_date < today and not completed)
        $overdueTasks = \App\Models\Task::where('due_date', '<', $today)
            ->where('status', '!=', 'completed')
            ->get();

        $overdueCount = 0;
        foreach ($overdueTasks as $task) {
            // Log activity
            \App\Models\ActivityLog::create([
                'user_id' => null, // System Action
                'action' => 'scheduler_overdue',
                'description' => "Task #{$task->id} ('{$task->title}') was flagged as OVERDUE.",
                'ip_address' => '127.0.0.1'
            ]);

            // Notify assignees
            foreach ($task->assignees as $assignee) {
                // Insert database notification
                \Illuminate\Support\Facades\DB::table('notifications')->insert([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'App\\Notifications\\TaskOverdueNotification',
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $assignee->id,
                    'data' => json_encode([
                        'task_id' => $task->id,
                        'title' => $task->title,
                        'message' => "Task '{$task->title}' is overdue! Due date was " . $task->due_date,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $overdueCount++;
        }

        // 2. 24h Before Due Reminder (due_date = tomorrow and not completed)
        $reminderTasks = \App\Models\Task::whereDate('due_date', $tomorrow)
            ->where('status', '!=', 'completed')
            ->get();

        $reminderCount = 0;
        foreach ($reminderTasks as $task) {
            foreach ($task->assignees as $assignee) {
                \Illuminate\Support\Facades\DB::table('notifications')->insert([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'App\\Notifications\\TaskDueReminderNotification',
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $assignee->id,
                    'data' => json_encode([
                        'task_id' => $task->id,
                        'title' => $task->title,
                        'message' => "Reminder: Task '{$task->title}' is due in 24 hours (on " . $task->due_date . ")",
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $reminderCount++;
        }

        $this->info("Scheduler complete: Flagged {$overdueCount} overdue tasks and sent {$reminderCount} reminders.");
        return 0;
    }
}
