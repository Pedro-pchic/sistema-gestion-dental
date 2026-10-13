<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentReminderStatus;
use App\Models\AppointmentReminder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentReminderController extends Controller
{
    public function __invoke(Request $request): View
    {
        Gate::authorize('view-appointment-reminders');

        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::in(array_map(
                fn (AppointmentReminderStatus $status): string => $status->value,
                AppointmentReminderStatus::cases(),
            ))],
            'channel' => ['nullable', Rule::in(['email', 'sms'])],
        ]);
        if (isset($filters['from'], $filters['to']) && $filters['from'] > $filters['to']) {
            throw ValidationException::withMessages(['to' => 'La fecha final debe ser igual o posterior a la inicial.']);
        }

        $timezone = config('appointments.timezone');
        $reminders = AppointmentReminder::query()
            ->with([
                'appointment:id',
                'patient:id,first_name,last_name',
            ])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['channel'] ?? null, fn (Builder $query, string $channel): Builder => $query->where('channel', $channel))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from): Builder => $query->where(
                'scheduled_at',
                '>=',
                CarbonImmutable::createFromFormat('!Y-m-d', $from, $timezone)->utc(),
            ))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to): Builder => $query->where(
                'scheduled_at',
                '<',
                CarbonImmutable::createFromFormat('!Y-m-d', $to, $timezone)->addDay()->utc(),
            ))
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('appointments.reminders', compact('reminders', 'filters', 'timezone'));
    }
}
