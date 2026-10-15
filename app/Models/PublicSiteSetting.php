<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['clinic_name', 'description', 'phone', 'email', 'address', 'opening_hours', 'booking_starts_at', 'booking_ends_at', 'booking_weekdays', 'booking_hours', 'facebook_url', 'instagram_url', 'hero_image_path', 'logo_path'])]
class PublicSiteSetting extends Model
{
    protected function casts(): array
    {
        return ['booking_weekdays' => 'array', 'booking_hours' => 'array'];
    }

    /** @return array<int, array{start: string, end: string}> */
    public function bookingHours(): array
    {
        if ($this->booking_hours !== null) {
            return $this->booking_hours;
        }

        if ($this->booking_weekdays !== null) {
            $hours = [];

            if ($this->booking_starts_at && $this->booking_ends_at) {
                foreach ($this->booking_weekdays as $day) {
                    $hours[(int) $day] = [
                        'start' => substr($this->booking_starts_at, 0, 5),
                        'end' => substr($this->booking_ends_at, 0, 5),
                    ];
                }
            }

            return $hours;
        }

        return config('appointments.public_hours');
    }
}
