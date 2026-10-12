<?php

namespace App\Jobs;

use App\Enums\AppointmentReminderStatus;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\AppointmentReminderAttempt;
use App\Models\Patient;
use App\Notifications\AppointmentReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAppointmentReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public int $reminderId) {}

    public function handle(): void
    {
        $attempt = $this->beginAttempt();

        if ($attempt === null) {
            return;
        }

        $appointment = Appointment::query()->with('patient')->find($attempt['appointment_id']);
        $patient = $appointment?->patient;
        $reminder = AppointmentReminder::query()->find($attempt['reminder_id']);

        if (! $appointment instanceof Appointment
            || ! $patient instanceof Patient
            || $reminder === null
            || $reminder->status !== AppointmentReminderStatus::Sending
            || (int) $appointment->patient_id !== (int) $reminder->patient_id
            || ! $appointment->starts_at->equalTo($reminder->appointment_starts_at)
            || ! in_array($appointment->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true)
            || ! $patient->is_active
            || ! $this->patientCanReceive($patient, $attempt['channel'])
            || $appointment->starts_at->lessThanOrEqualTo(now('UTC'))) {
            $this->completeAsSkipped($attempt['reminder_id'], $attempt['attempt_id']);

            return;
        }

        try {
            $patient->notifyNow(new AppointmentReminderNotification($appointment, $attempt['channel']));
            $this->completeAsSent($attempt['reminder_id'], $attempt['attempt_id']);
        } catch (Throwable $exception) {
            $this->completeAsFailed($attempt['reminder_id'], $attempt['attempt_id'], $exception);
        }
    }

    /**
     * @return array{reminder_id: int, attempt_id: int, appointment_id: int, channel: string}|null
     */
    private function beginAttempt(): ?array
    {
        return DB::transaction(function (): ?array {
            $reminder = AppointmentReminder::query()
                ->whereKey($this->reminderId)
                ->lockForUpdate()
                ->first();

            if ($reminder === null || $reminder->status !== AppointmentReminderStatus::Queued) {
                return null;
            }

            $appointment = $reminder->appointment()->with('patient')->first();
            $patient = $appointment?->patient;

            if (! $appointment instanceof Appointment
                || ! $patient instanceof Patient
                || $appointment->patient_id !== $reminder->patient_id
                || ! $appointment->starts_at->equalTo($reminder->appointment_starts_at)
                || ! in_array($appointment->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true)
                || ! $patient->is_active
                || ! $this->patientCanReceive($patient, $reminder->channel)
                || $appointment->starts_at->lessThanOrEqualTo(now('UTC'))) {
                $reminder->update(['status' => AppointmentReminderStatus::Skipped]);

                return null;
            }

            $attemptNumber = $reminder->attempts + 1;
            $attempt = $reminder->attempts()->create([
                'attempt_number' => $attemptNumber,
                'result' => 'started',
                'attempted_at' => now('UTC'),
            ]);

            $reminder->update([
                'status' => AppointmentReminderStatus::Sending,
                'attempts' => $attemptNumber,
                'claimed_at' => now('UTC'),
                'queued_at' => null,
            ]);

            return [
                'reminder_id' => (int) $reminder->getKey(),
                'attempt_id' => (int) $attempt->getKey(),
                'appointment_id' => (int) $appointment->getKey(),
                'channel' => $reminder->channel,
            ];
        });
    }

    private function patientCanReceive(Patient $patient, string $channel): bool
    {
        if ($channel === 'email') {
            return (bool) config('appointment_reminders.email.enabled')
                && $patient->email_reminders_enabled
                && is_string($patient->email)
                && filter_var($patient->email, FILTER_VALIDATE_EMAIL) !== false;
        }

        return $channel === 'sms'
            && (bool) config('appointment_reminders.sms.enabled')
            && config('appointment_reminders.sms.driver') !== 'disabled'
            && $patient->sms_reminders_enabled
            && $this->hasValidPhone($patient->phone);
    }

    private function hasValidPhone(?string $phone): bool
    {
        return is_string($phone)
            && preg_match('/^\+?[0-9][0-9\s().-]{5,38}[0-9]$/', $phone) === 1
            && preg_match_all('/[0-9]/', $phone) >= 7;
    }

    private function completeAsSent(int $reminderId, int $attemptId): void
    {
        DB::transaction(function () use ($reminderId, $attemptId): void {
            AppointmentReminderAttempt::query()->whereKey($attemptId)->update([
                'result' => 'sent',
                'completed_at' => now('UTC'),
                'updated_at' => now('UTC'),
            ]);

            AppointmentReminder::query()
                ->whereKey($reminderId)
                ->where('status', AppointmentReminderStatus::Sending->value)
                ->update([
                    'status' => AppointmentReminderStatus::Sent->value,
                    'sent_at' => now('UTC'),
                    'claimed_at' => null,
                    'last_error' => null,
                    'updated_at' => now('UTC'),
                ]);
        });
    }

    private function completeAsSkipped(int $reminderId, int $attemptId): void
    {
        DB::transaction(function () use ($reminderId, $attemptId): void {
            AppointmentReminderAttempt::query()->whereKey($attemptId)->update([
                'result' => 'skipped',
                'completed_at' => now('UTC'),
                'updated_at' => now('UTC'),
            ]);

            AppointmentReminder::query()
                ->whereKey($reminderId)
                ->where('status', AppointmentReminderStatus::Sending->value)
                ->update([
                    'status' => AppointmentReminderStatus::Skipped->value,
                    'claimed_at' => null,
                    'updated_at' => now('UTC'),
                ]);
        });
    }

    private function completeAsFailed(int $reminderId, int $attemptId, Throwable $exception): void
    {
        $now = now('UTC');
        $errorType = class_basename($exception);
        $reminder = AppointmentReminder::query()->find($reminderId);

        if ($reminder === null) {
            return;
        }

        $maxAttempts = (int) config('appointment_reminders.retry.max_attempts', 3);
        $delays = config('appointment_reminders.retry.delays_minutes', [15, 60]);
        $delayIndex = min(max($reminder->attempts - 1, 0), count($delays) - 1);
        $nextAttemptAt = $reminder->attempts < $maxAttempts
            ? $now->copy()->addMinutes((int) ($delays[$delayIndex] ?? 60))
            : null;

        DB::transaction(function () use ($attemptId, $errorType, $nextAttemptAt, $now, $reminderId): void {
            AppointmentReminderAttempt::query()->whereKey($attemptId)->update([
                'result' => 'failed',
                'completed_at' => $now,
                'error_type' => $errorType,
                'updated_at' => $now,
            ]);

            AppointmentReminder::query()
                ->whereKey($reminderId)
                ->where('status', AppointmentReminderStatus::Sending->value)
                ->update([
                    'status' => AppointmentReminderStatus::Failed->value,
                    'next_attempt_at' => $nextAttemptAt,
                    'claimed_at' => null,
                    'last_error' => 'Delivery failed ('.$errorType.').',
                    'updated_at' => $now,
                ]);
        });

        Log::warning('Appointment reminder delivery failed.', [
            'reminder_id' => $reminderId,
            'attempt_id' => $attemptId,
            'exception_type' => $errorType,
        ]);
    }
}
