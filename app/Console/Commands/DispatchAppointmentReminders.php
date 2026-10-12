<?php

namespace App\Console\Commands;

use App\Services\AppointmentReminderScheduler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('appointments:dispatch-reminders')]
#[Description('Queue due appointment reminders')]
class DispatchAppointmentReminders extends Command
{
    public function handle(AppointmentReminderScheduler $scheduler): int
    {
        $queued = $scheduler->dispatchDueReminders();

        $this->info("Recordatorios encolados: {$queued}.");

        return self::SUCCESS;
    }
}
