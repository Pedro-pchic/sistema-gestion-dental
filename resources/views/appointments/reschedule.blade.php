<div>
    <!-- Let all your things have their places; let each part of your business have its time. - Benjamin Franklin -->
</div>
@extends('layouts.admin')

@section('title', 'Reprogramar cita')
@section('heading', 'Reprogramar cita')
@section('subheading', $appointment->patient?->first_name.' '.$appointment->patient?->last_name.' · '.$appointment->dentist?->user?->name)

@section('content')
    <form method="POST" action="{{ route('citas.update', $appointment) }}" class="ui-card grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
        @csrf
        @method('PUT')

        <div class="rounded-lg bg-stone-50 p-4 text-sm sm:col-span-2">
            <p><span class="font-semibold">Paciente:</span> {{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name }}</p>
            <p class="mt-1"><span class="font-semibold">Odontólogo:</span> {{ $appointment->dentist?->user?->name }}</p>
            <p class="mt-1"><span class="font-semibold">Estado:</span> {{ $appointment->status->label() }}</p>
        </div>

        <label for="starts_at" class="grid gap-2 text-sm font-semibold text-stone-700">
            Nuevo inicio ({{ $timezone }})
            <input id="starts_at" name="starts_at" type="datetime-local" step="900" required
                value="{{ old('starts_at', $startsAt) }}"
                class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
            @error('starts_at')
                <span class="font-normal text-red-700">{{ $message }}</span>
            @enderror
        </label>

        <label for="ends_at" class="grid gap-2 text-sm font-semibold text-stone-700">
            Nueva finalización ({{ $timezone }})
            <input id="ends_at" name="ends_at" type="datetime-local" step="900" required
                value="{{ old('ends_at', $endsAt) }}"
                class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
            @error('ends_at')
                <span class="font-normal text-red-700">{{ $message }}</span>
            @enderror
        </label>

        <div class="flex flex-wrap gap-3 sm:col-span-2">
            <button type="submit" class="ui-button ui-button-primary">Guardar nuevo horario</button>
            <a href="{{ route('citas.show', $appointment) }}" class="ui-button ui-button-secondary">Volver a la cita</a>
        </div>
    </form>
@endsection
