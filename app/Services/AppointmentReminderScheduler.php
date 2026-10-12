<?php

namespace App\Services;

use App\Contracts\SmsSender;
use App\Enums\AppointmentReminderStatus;
use App\Enums\AppointmentStatus;
use App\Jobs\SendAppointmentReminder;
use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppointmentReminderScheduler
{
    public function __construct(private SmsSender $smsSender) {}

    public function dispatchDueReminders(): int
    {
        $now = Carbon::now('UTC');

        $this->recoverExpiredClaims($now);
        $this->supersedeObsoleteReminders();
        $this->createDueReminders($now);

        return $this->dispatchReadyReminders($now);
    }

    private function createDueReminders(Carbon $now): void
    {
        $channels = $this->enabledChannels();

        if ($channels === []) {
            return;
        }

        $hoursBefore = max(1, (int) config('appointment_reminders.email.hours_before', 24));
        $latestStart = $now->copy()->addHours($hoursBefore);

        Appointment::query()
            ->with('patient')
            ->whereIn('status', [AppointmentStatus::Scheduled->value, AppointmentStatus::Confirmed->value])
            ->where('starts_at', '>', $now)
            ->where('starts_at', '<=', $latestStart)
            ->chunkById(100, function ($appointments) use ($channels, $hoursBefore): void {
                foreach ($appointments as $appointment) {
                    $patient = $appointment->patient;

                    if (! $patient instanceof Patient || ! $patient->is_active) {
                        continue;
                    }

                    foreach ($channels as $channel) {
                        if (! $this->patientCanReceive($patient, $channel)) {
                            continue;
                        }

                        $scheduledAt = $appointment->starts_at->utc()->subHours($hoursBefore);

                        AppointmentReminder::query()->firstOrCreate([
                            'appointment_id' => $appointment->getKey(),
                            'patient_id' => $patient->getKey(),
                            'channel' => $channel,
                            'appointment_starts_at' => $appointment->starts_at->utc(),
                        ], [
                            'scheduled_at' => $scheduledAt,
                            'status' => AppointmentReminderStatus::Pending,
                            'attempts' => 0,
                            'next_attempt_at' => $scheduledAt,
                        ]);
                    }
                }
            });
    }

    private function supersedeObsoleteReminders(): void
    {
        AppointmentReminder::query()
            ->whereIn('status', [
                AppointmentReminderStatus::Pending->value,
                AppointmentReminderStatus::Queued->value,
                AppointmentReminderStatus::Sending->value,
                AppointmentReminderStatus::Failed->value,
            ])
            ->with('appointment:id,patient_id,starts_at,status')
            ->chunkById(100, function ($reminders): void {
                foreach ($reminders as $reminder) {
                    $appointment = $reminder->appointment;

                    if ($appointment === null
                        || ! in_array($appointment->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true)
                        || (int) $appointment->patient_id !== (int) $reminder->patient_id
                        || ! $appointment->starts_at->equalTo($reminder->appointment_starts_at)) {
                        $reminder->update(['status' => AppointmentReminderStatus::Superseded]);
                    }
                }
            });
    }

    private function recoverExpiredClaims(Carbon $now): void
    {
        $claimExpiredAt = $now->copy()->subMinutes(max(1, (int) config(
            'appointment_reminders.retry.claim_timeout_minutes',
            15,
        )));

        $expiredQueuedIds = AppointmentReminder::query()
            ->where('status', AppointmentReminderStatus::Queued->value)
            ->where('queued_at', '<=', $claimExpiredAt)
            ->limit(100)
            ->pluck('id');

        AppointmentReminder::query()->whereIn('id', $expiredQueuedIds)->update([
            'status' => AppointmentReminderStatus::Pending->value,
            'queued_at' => null,
            'updated_at' => $now,
        ]);

        AppointmentReminder::query()
            ->where('status', AppointmentReminderStatus::Sending->value)
            ->where('claimed_at', '<=', $claimExpiredAt)
            ->chunkById(100, function ($reminders) use ($now): void {
                foreach ($reminders as $reminder) {
                    $attempt = $reminder->attempts()->where('result', 'started')->latest('id')->first();
                    $attempt?->update([
                        'result' => 'failed',
                        'completed_at' => $now,
                        'error_type' => 'WorkerTimeout',
                    ]);

                    $reminder->update([
                        'status' => AppointmentReminderStatus::Failed,
                        'next_attempt_at' => $this->nextAttemptAt($reminder->attempts, $now),
                        'claimed_at' => null,
                        'last_error' => 'Delivery worker did not finish.',
                    ]);
                }
            });
    }

    private function dispatchReadyReminders(Carbon $now): int
    {
        $queued = 0;

        AppointmentReminder::query()
            ->where(function (Builder $query) use ($now): void {
                $query->where(function (Builder $query) use ($now): void {
                    $query->where('status', AppointmentReminderStatus::Pending->value)
                        ->where('scheduled_at', '<=', $now);
                })->orWhere(function (Builder $query) use ($now): void {
                    $query->where('status', AppointmentReminderStatus::Failed->value)
                        ->whereNotNull('next_attempt_at')
                        ->where('next_attempt_at', '<=', $now);
                });
            })
            ->orderBy('id')
            ->limit(500)
            ->get(['id'])
            ->each(function (AppointmentReminder $reminder) use ($now, &$queued): void {
                if (! $this->queueReminder((int) $reminder->getKey(), $now)) {
                    return;
                }

                try {
                    SendAppointmentReminder::dispatch((int) $reminder->getKey());
                    $queued++;
                } catch (Throwable $exception) {
                    AppointmentReminder::query()
                        ->whereKey($reminder->getKey())
                        ->where('status', AppointmentReminderStatus::Queued->value)
                        ->update([
                            'status' => AppointmentReminderStatus::Pending->value,
                            'queued_at' => null,
                            'last_error' => 'Queue dispatch failed.',
                            'updated_at' => $now,
                        ]);

                    Log::warning('Appointment reminder could not be queued.', [
                        'reminder_id' => $reminder->getKey(),
                        'exception_type' => $exception::class,
                    ]);
                }
            });

        return $queued;
    }

    private function queueReminder(int $reminderId, Carbon $now): bool
    {
        return DB::transaction(function () use ($reminderId, $now): bool {
            $reminder = AppointmentReminder::query()->whereKey($reminderId)->lockForUpdate()->first();

            if ($reminder === null
                || ! in_array($reminder->status, [AppointmentReminderStatus::Pending, AppointmentReminderStatus::Failed], true)
                || ($reminder->status === AppointmentReminderStatus::Failed
                    && ($reminder->next_attempt_at === null || $reminder->next_attempt_at->isAfter($now)))
                || $reminder->attempts >= (int) config('appointment_reminders.retry.max_attempts', 3)) {
                return false;
            }

            $reminder->update([
                'status' => AppointmentReminderStatus::Queued,
                'queued_at' => $now,
                'next_attempt_at' => null,
            ]);

            return true;
        });
    }

    /** @return list<string> */
    private function enabledChannels(): array
    {
        $channels = [];

        if ((bool) config('appointment_reminders.email.enabled')) {
            $channels[] = 'email';
        }

        if ((bool) config('appointment_reminders.sms.enabled')
            && config('appointment_reminders.sms.driver') !== 'disabled'
            && $this->smsSender->isAvailable()) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    private function patientCanReceive(Patient $patient, string $channel): bool
    {
        if ($channel === 'email') {
            return $patient->email_reminders_enabled
                && is_string($patient->email)
                && filter_var($patient->email, FILTER_VALIDATE_EMAIL) !== false;
        }

        return $patient->sms_reminders_enabled && $this->hasValidPhone($patient->phone);
    }

    private function hasValidPhone(?string $phone): bool
    {
        return is_string($phone)
            && preg_match('/^\+?[0-9][0-9\s().-]{5,38}[0-9]$/', $phone) === 1
            && preg_match_all('/[0-9]/', $phone) >= 7;
    }

    private function nextAttemptAt(int $attempts, Carbon $now): ?Carbon
    {
        if ($attempts >= (int) config('appointment_reminders.retry.max_attempts', 3)) {
            return null;
        }

        $delays = config('appointment_reminders.retry.delays_minutes', [15, 60]);
        $delayIndex = min(max($attempts - 1, 0), count($delays) - 1);

        return $now->copy()->addMinutes((int) ($delays[$delayIndex] ?? 60));
    }
}
