<?php

namespace App\Http\Controllers;

use App\Models\PublicSiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicSiteSettingController extends Controller
{
    public function edit(): Response
    {
        Gate::authorize('manage-public-site');

        $settings = PublicSiteSetting::query()->find(1) ?? new PublicSiteSetting;

        return Inertia::render('PublicSite/Edit', [
            'settings' => $settings->only([
                'clinic_name', 'description', 'phone', 'email', 'address', 'opening_hours',
                'facebook_url', 'instagram_url',
            ]),
            'bookingHours' => $settings->bookingHours(),
            'updateUrl' => route('configuracion-publica.update'),
            'publicUrl' => route('public.home'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('manage-public-site');
        $data = $request->validate([
            'clinic_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'opening_hours' => ['nullable', 'string', 'max:1000'],
            'booking_starts_at' => ['nullable', 'date_format:H:i', 'required_with:booking_ends_at'],
            'booking_ends_at' => ['nullable', 'date_format:H:i', 'required_with:booking_starts_at', 'after:booking_starts_at'],
            'booking_weekdays' => ['array'],
            'booking_weekdays.*' => ['integer', 'between:1,7'],
            'booking_hours' => ['sometimes', 'array:1,2,3,4,5,6,7'],
            'booking_hours.*' => ['array:start,end'],
            'booking_hours.*.start' => ['nullable', 'date_format:H:i'],
            'booking_hours.*.end' => ['nullable', 'date_format:H:i'],
            'facebook_url' => ['nullable', 'url:http,https', 'max:255'],
            'instagram_url' => ['nullable', 'url:http,https', 'max:255'],
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        if (isset($data['booking_hours'])) {
            $hours = [];

            foreach (range(1, 7) as $day) {
                $start = $data['booking_hours'][$day]['start'] ?? null;
                $end = $data['booking_hours'][$day]['end'] ?? null;

                if (filled($start) !== filled($end) || (filled($start) && $end <= $start)) {
                    throw ValidationException::withMessages(["booking_hours.$day.end" => 'Indica una apertura y un cierre posterior para este día.']);
                }

                if (filled($start)) {
                    $hours[$day] = ['start' => $start, 'end' => $end];
                }
            }

            $data['booking_hours'] = $hours;
        }

        if (array_key_exists('booking_weekdays', $data)) {
            $data['booking_weekdays'] = array_map('intval', $data['booking_weekdays']);
        }
        unset($data['hero_image'], $data['logo']);

        if (($data['booking_weekdays'] ?? []) !== [] && (! filled($data['booking_starts_at'] ?? null) || ! filled($data['booking_ends_at'] ?? null))) {
            throw ValidationException::withMessages(['booking_starts_at' => 'Indica el horario de reservas.']);
        }

        $settings = PublicSiteSetting::query()->find(1) ?? new PublicSiteSetting;
        $settings->id = 1;
        $oldImages = [];

        foreach (['hero_image' => 'hero_image_path', 'logo' => 'logo_path'] as $input => $column) {
            if ($request->hasFile($input)) {
                $oldImages[] = $settings->{$column};
                $data[$column] = $request->file($input)->store('public-site', 'public');
            }
        }

        $settings->fill($data)->save();

        foreach ($oldImages as $oldImage) {
            if ($oldImage) {
                Storage::disk('public')->delete($oldImage);
            }
        }

        return back()->with('status', 'Configuración pública actualizada.');
    }
}
