@extends('layouts.admin')
@section('title', 'Sitio público')
@section('heading', 'Sitio público')
@section('subheading', 'Información institucional visible y horarios habilitados para solicitudes')
@section('content')
<form method="POST" action="{{ route('configuracion-publica.update') }}" enctype="multipart/form-data" class="ui-form-section max-w-4xl sm:grid-cols-2">
    @csrf
    @method('PUT')
    <div class="sm:col-span-2"><label for="clinic_name" class="block text-sm font-semibold">Nombre comercial</label><input id="clinic_name" name="clinic_name" value="{{ old('clinic_name', $settings->clinic_name) }}" maxlength="255" class="mt-2 w-full" @error('clinic_name') aria-invalid="true" aria-describedby="clinic-name-error" @enderror>@error('clinic_name')<p id="clinic-name-error" class="ui-field-error mt-1">{{ $message }}</p>@enderror</div>
    <div class="sm:col-span-2"><label for="description" class="block text-sm font-semibold">Descripción</label><textarea id="description" name="description" rows="3" maxlength="2000" class="mt-2 w-full">{{ old('description', $settings->description) }}</textarea></div>
    <div><label for="phone" class="block text-sm font-semibold">Teléfono</label><input id="phone" name="phone" value="{{ old('phone', $settings->phone) }}" maxlength="40" class="mt-2 w-full"></div>
    <div><label for="email" class="block text-sm font-semibold">Correo público</label><input id="email" name="email" type="email" value="{{ old('email', $settings->email) }}" class="mt-2 w-full"></div>
    <div><label for="address" class="block text-sm font-semibold">Dirección</label><textarea id="address" name="address" rows="3" maxlength="1000" class="mt-2 w-full">{{ old('address', $settings->address) }}</textarea></div>
    <div><label for="opening_hours" class="block text-sm font-semibold">Horario de atención visible</label><textarea id="opening_hours" name="opening_hours" rows="3" maxlength="1000" class="mt-2 w-full">{{ old('opening_hours', $settings->opening_hours) }}</textarea></div>
    @php($bookingHours = old('booking_hours', $settings->bookingHours()))
    <fieldset class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
        <legend class="text-sm font-bold">Horario para solicitudes en línea</legend>
        <p class="mt-1 text-sm text-slate-500">La última cita debe terminar a la hora de cierre. Deja ambas horas vacías para cerrar un día.</p>
        <div class="mt-4 grid gap-3">
            @foreach ([1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'] as $day => $label)
                <div class="grid items-center gap-3 rounded-xl border border-slate-200 p-3 sm:grid-cols-[8rem_1fr_1fr]">
                    <span class="font-semibold">{{ $label }}</span>
                    <label class="text-sm">Apertura<input name="booking_hours[{{ $day }}][start]" type="time" value="{{ $bookingHours[$day]['start'] ?? '' }}" class="mt-1 w-full" aria-label="Apertura del {{ mb_strtolower($label) }}" @error("booking_hours.$day.start") aria-invalid="true" @enderror></label>
                    <label class="text-sm">Cierre<input name="booking_hours[{{ $day }}][end]" type="time" value="{{ $bookingHours[$day]['end'] ?? '' }}" class="mt-1 w-full" aria-label="Cierre del {{ mb_strtolower($label) }}" @error("booking_hours.$day.end") aria-invalid="true" @enderror></label>
                </div>
            @endforeach
        </div>
    </fieldset>
    <div><label for="facebook_url" class="block text-sm font-semibold">Facebook</label><input id="facebook_url" name="facebook_url" type="url" value="{{ old('facebook_url', $settings->facebook_url) }}" class="mt-2 w-full"></div>
    <div><label for="instagram_url" class="block text-sm font-semibold">Instagram</label><input id="instagram_url" name="instagram_url" type="url" value="{{ old('instagram_url', $settings->instagram_url) }}" class="mt-2 w-full"></div>
    <div><label for="hero_image" class="block text-sm font-semibold">Imagen principal</label><input id="hero_image" name="hero_image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 w-full"></div>
    <div><label for="logo" class="block text-sm font-semibold">Logotipo</label><input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 w-full"></div>
    <div class="sm:col-span-2"><button class="ui-button ui-button-primary">Guardar configuración</button></div>
</form>
@endsection
