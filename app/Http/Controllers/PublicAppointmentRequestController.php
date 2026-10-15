<?php

namespace App\Http\Controllers;

use App\Models\AppointmentRequest;
use App\Models\Dentist;
use App\Models\PublicSiteSetting;
use App\Models\Role;
use App\Models\Treatment;
use App\Services\PublicAppointmentAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicAppointmentRequestController extends Controller
{
    public function slots(Request $request, PublicAppointmentAvailability $availability): JsonResponse
    {
        $data = $request->validate([
            'treatment_id' => ['required', 'integer', Rule::exists('treatments', 'id')->where('is_active', true)->where('is_public', true)],
            'dentist_id' => ['required', 'integer', Rule::exists('dentists', 'id')->where('is_active', true)->where('is_public', true)],
            'date' => ['required_without:month', 'prohibits:month', 'date_format:Y-m-d'],
            'month' => ['required_without:date', 'prohibits:date', 'date_format:Y-m'],
        ]);
        $treatment = Treatment::query()->findOrFail($data['treatment_id']);
        $dentist = $this->publicDentist((int) $data['dentist_id']);
        $settings = PublicSiteSetting::query()->find(1) ?? new PublicSiteSetting;

        if (isset($data['month'])) {
            return response()->json(['days' => $availability->days(
                $dentist->id,
                $treatment->estimated_duration_minutes,
                CarbonImmutable::createFromFormat('!Y-m', $data['month'], config('appointments.timezone')),
                $settings,
            )]);
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $data['date'], config('appointments.timezone'));
        $times = $availability->times($dentist->id, $treatment->estimated_duration_minutes, $date, $settings);

        return response()->json([
            'slots' => array_values(array_column(array_filter($times, fn (array $time): bool => $time['available']), 'time')),
            'times' => $times,
        ]);
    }

    public function store(Request $request, PublicAppointmentAvailability $availability): RedirectResponse
    {
        $data = $request->validate([
            'treatment_id' => ['required', 'integer', Rule::exists('treatments', 'id')->where('is_active', true)->where('is_public', true)],
            'dentist_id' => ['required', 'integer', Rule::exists('dentists', 'id')->where('is_active', true)->where('is_public', true)],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9][0-9\s().-]{5,38}[0-9]$/'],
            'email' => ['required', 'email', 'max:255'],
            'website' => ['nullable', 'max:0'],
        ]);
        $dentist = $this->publicDentist((int) $data['dentist_id']);
        $treatment = Treatment::query()->findOrFail($data['treatment_id']);
        $settings = PublicSiteSetting::query()->find(1) ?? new PublicSiteSetting;
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $data['date'], config('appointments.timezone'));
        $start = CarbonImmutable::parse($data['date'].' '.$data['time'], config('appointments.timezone'));
        $end = $start->addMinutes($treatment->estimated_duration_minutes);
        $key = hash('sha256', mb_strtolower(trim($data['email'])).'|'.$dentist->id.'|'.$start->utc()->toIso8601String());

        DB::transaction(function () use ($availability, $data, $date, $dentist, $end, $key, $settings, $start, $treatment): void {
            $lockedDentist = Dentist::query()->whereKey($dentist->id)->lockForUpdate()->firstOrFail();
            $lockedTreatment = Treatment::query()->whereKey($treatment->id)->lockForUpdate()->firstOrFail();

            if (! $lockedDentist->is_active || ! $lockedDentist->is_public || ! $lockedTreatment->is_active || ! $lockedTreatment->is_public) {
                throw ValidationException::withMessages(['time' => 'El servicio o el odontólogo ya no está disponible.']);
            }

            if (! in_array($data['time'], $availability->slots($dentist->id, $treatment->estimated_duration_minutes, $date, $settings), true)) {
                throw ValidationException::withMessages(['time' => 'Selecciona un horario disponible.']);
            }

            if (AppointmentRequest::query()->where('duplicate_key', $key)->exists()) {
                throw ValidationException::withMessages(['time' => 'Esta solicitud ya fue recibida.']);
            }

            $booking = AppointmentRequest::query()->create([
                'treatment_id' => $treatment->id,
                'dentist_id' => $dentist->id,
                'starts_at' => $start->utc(),
                'ends_at' => $end->utc(),
                'name' => trim($data['name']),
                'phone' => trim($data['phone']),
                'email' => mb_strtolower(trim($data['email'])),
                'status' => 'pending',
                'duplicate_key' => $key,
            ]);
            $booking->events()->create(['event_type' => 'received']);
        }, 3);

        return redirect(route('public.home').'#cita')->with('status', 'Recibimos tu solicitud. Recepción confirmará la disponibilidad antes de programar la cita.');
    }

    private function publicDentist(int $dentistId): Dentist
    {
        return Dentist::query()->whereKey($dentistId)->where('is_active', true)->where('is_public', true)
            ->whereHas('user', fn (Builder $query): Builder => $query->where('is_active', true))
            ->whereHas('user.role', fn (Builder $query): Builder => $query->where('name', Role::DENTIST))
            ->firstOrFail();
    }
}
