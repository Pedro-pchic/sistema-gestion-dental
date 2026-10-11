<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $startsAt = CarbonImmutable::now()->addDays(2)->startOfHour();

        return [
            'patient_id' => Patient::factory(),
            'dentist_id' => Dentist::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addHour(),
            'planned_treatment' => 'Consulta general',
            'administrative_notes' => null,
            'status' => AppointmentStatus::Scheduled,
            'created_by' => User::factory(),
        ];
    }
}
