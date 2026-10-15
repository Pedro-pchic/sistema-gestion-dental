<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\PublicSiteSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class PublicAppointmentAvailability
{
    /** @return array<string, bool> */
    public function days(int $dentistId, int $durationMinutes, CarbonImmutable $month, PublicSiteSetting $settings): array
    {
        $timezone = config('appointments.timezone');
        $firstDay = $month->setTimezone($timezone)->startOfMonth();
        $lastDay = $firstDay->endOfMonth();
        $appointments = $this->appointmentsForRange($dentistId, $firstDay->utc(), $lastDay->addDay()->startOfDay()->utc());
        $days = [];

        for ($date = $firstDay; $date->lessThanOrEqualTo($lastDay); $date = $date->addDay()) {
            $days[$date->toDateString()] = collect($this->times($dentistId, $durationMinutes, $date, $settings, $appointments))
                ->contains(fn (array $time): bool => $time['available']);
        }

        return $days;
    }

    /** @return list<string> */
    public function slots(int $dentistId, int $durationMinutes, CarbonImmutable $date, PublicSiteSetting $settings): array
    {
        $slots = [];

        foreach ($this->times($dentistId, $durationMinutes, $date, $settings) as $time) {
            if ($time['available']) {
                $slots[] = $time['time'];
            }
        }

        return $slots;
    }

    /**
     * @param  Collection<int, Appointment>|null  $appointments
     * @return list<array{time: string, available: bool}>
     */
    public function times(int $dentistId, int $durationMinutes, CarbonImmutable $date, PublicSiteSetting $settings, ?Collection $appointments = null): array
    {
        $timezone = config('appointments.timezone');
        $date = $date->setTimezone($timezone)->startOfDay();
        $hours = $settings->bookingHours()[$date->dayOfWeekIso] ?? null;

        if ($hours === null || $durationMinutes < 1
            || $date->lessThan(CarbonImmutable::today($timezone))
            || $date->greaterThan(CarbonImmutable::today($timezone)->addDays(60))) {
            return [];
        }

        $first = CarbonImmutable::parse($date->toDateString().' '.$hours['start'], $timezone);
        $last = CarbonImmutable::parse($date->toDateString().' '.$hours['end'], $timezone);
        $appointments ??= $this->appointmentsForRange($dentistId, $first->utc(), $last->utc());
        $times = [];

        for ($start = $first; $start->addMinutes($durationMinutes)->lessThanOrEqualTo($last); $start = $start->addMinutes(30)) {
            if ($start->lessThanOrEqualTo(CarbonImmutable::now($timezone))) {
                continue;
            }

            $end = $start->addMinutes($durationMinutes);
            $startUtc = $start->utc();
            $endUtc = $end->utc();

            $times[] = [
                'time' => $start->format('H:i'),
                'available' => ! $appointments->contains(fn (Appointment $appointment): bool => $appointment->starts_at->lessThan($endUtc)
                    && $appointment->ends_at->greaterThan($startUtc)),
            ];
        }

        return $times;
    }

    /** @return Collection<int, Appointment> */
    private function appointmentsForRange(int $dentistId, CarbonImmutable $startsAt, CarbonImmutable $endsAt): Collection
    {
        return Appointment::query()
            ->where('dentist_id', $dentistId)
            ->whereIn('status', AppointmentStatus::reservableValues())
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->get(['id', 'starts_at', 'ends_at']);
    }
}
