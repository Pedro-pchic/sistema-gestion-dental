@extends('layouts.public')

@section('content')
<header class="sticky top-0 z-30 border-b border-white/70 bg-white/95 backdrop-blur">
    <nav class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-4 lg:px-8" aria-label="Navegación principal">
        <a href="#inicio" class="flex items-center gap-3 text-lg font-bold tracking-tight text-brand-900">
            @if ($settings->logo_path)
                <img src="{{ asset('storage/'.$settings->logo_path) }}" alt="" class="size-10 rounded-xl object-contain">
            @else
                <span class="grid size-10 place-items-center rounded-xl bg-brand-600 text-white" aria-hidden="true">✦</span>
            @endif
            {{ $settings->clinic_name ?: 'Clínica dental' }}
        </a>
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-semibold text-slate-700">
            <a href="#servicios" class="hover:text-brand-700">Servicios</a>
            <a href="#equipo" class="hover:text-brand-700">Equipo</a>
            <a href="#novedades" class="hover:text-brand-700">Novedades</a>
            <a href="#contacto" class="hover:text-brand-700">Contacto</a>
            <a href="#cita" class="rounded-full bg-brand-700 px-5 py-2.5 text-white hover:bg-brand-800">Agendar cita</a>
        </div>
    </nav>
</header>

<main id="contenido">
    <section id="inicio" class="overflow-hidden bg-linear-to-br from-brand-50 via-white to-sky-100">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-20 lg:grid-cols-2 lg:px-8 lg:py-28">
            <div class="space-y-7">
                <span class="inline-flex rounded-full border border-brand-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-brand-700">Tu sonrisa, nuestro compromiso</span>
                <h1 class="max-w-2xl text-4xl font-bold leading-tight tracking-tight text-brand-950 sm:text-5xl lg:text-6xl">Un espacio para cuidar tu sonrisa.</h1>
                <p class="max-w-xl text-lg leading-8 text-slate-600">{{ $settings->description ?: 'Conoce los tratamientos disponibles y solicita una cita con nuestro equipo odontológico.' }}</p>
                <div class="flex flex-wrap gap-3">
                    <a href="#cita" class="rounded-full bg-brand-700 px-7 py-3.5 font-semibold text-white shadow-lg shadow-brand-900/10 hover:bg-brand-800">Agendar cita</a>
                    <a href="#servicios" class="rounded-full border border-brand-300 bg-white px-7 py-3.5 font-semibold text-brand-900 hover:bg-brand-50">Ver servicios</a>
                </div>
            </div>
            <div class="relative mx-auto w-full max-w-lg">
                <div class="absolute -inset-5 rounded-[3rem] bg-brand-200/60 blur-3xl"></div>
                @if ($settings->hero_image_path)
                    <img src="{{ asset('storage/'.$settings->hero_image_path) }}" alt="Instalaciones de {{ $settings->clinic_name ?: 'la clínica' }}" class="relative aspect-square w-full rounded-[3rem] object-cover shadow-2xl shadow-brand-900/15">
                @else
                    <div class="relative grid aspect-square place-items-center rounded-[3rem] bg-linear-to-br from-brand-600 to-sky-700 shadow-2xl shadow-brand-900/15" role="img" aria-label="Ilustración de una sonrisa">
                        <svg viewBox="0 0 400 400" class="w-3/4 text-white" fill="none" aria-hidden="true"><circle cx="200" cy="200" r="155" stroke="currentColor" stroke-opacity=".25" stroke-width="2"/><circle cx="200" cy="200" r="118" stroke="currentColor" stroke-opacity=".35" stroke-width="2"/><path d="M105 149c11-34 45-47 73-30 14 8 30 8 44 0 31-18 69-2 77 35 7 29-7 48-17 83-12 39-14 79-34 79-21 0-15-77-48-77s-27 77-48 77c-20 0-23-40-34-79-10-35-24-54-13-88Z" fill="currentColor"/><path d="M152 185c19 23 77 34 99 0" stroke="#0f766e" stroke-width="9" stroke-linecap="round"/></svg>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section id="servicios" class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
        <div class="mb-10"><p class="text-sm font-bold uppercase tracking-widest text-brand-700">Atención odontológica</p><h2 class="mt-2 text-3xl font-bold text-brand-950 sm:text-4xl">Nuestros servicios</h2></div>
        @if ($treatments->isEmpty())
            <p class="rounded-2xl bg-white p-8 text-slate-600 shadow-sm">Próximamente publicaremos nuestros tratamientos.</p>
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($treatments as $treatment)
                    <article class="flex flex-col rounded-3xl border border-slate-100 bg-white p-7 shadow-sm transition-transform duration-200 hover:-translate-y-1 hover:shadow-lg">
                        <span class="mb-6 grid size-12 place-items-center rounded-2xl bg-brand-100 text-2xl text-brand-700" aria-hidden="true">✦</span>
                        <h3 class="text-xl font-bold text-brand-950">{{ $treatment->name }}</h3>
                        <p class="mt-3 grow leading-7 text-slate-600">{{ $treatment->description }}</p>
                        @if ($treatment->show_public_price)<p class="mt-5 font-semibold text-brand-800">Desde Q {{ number_format((float) $treatment->price, 2) }}</p>@endif
                        <a href="#cita" class="mt-6 font-semibold text-brand-700 hover:underline">Solicitar cita <span aria-hidden="true">→</span></a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section id="equipo" class="bg-white py-20"><div class="mx-auto max-w-7xl px-5 lg:px-8">
        <p class="text-sm font-bold uppercase tracking-widest text-brand-700">Personas que te cuidan</p><h2 class="mt-2 text-3xl font-bold text-brand-950 sm:text-4xl">Equipo odontológico</h2>
        @if ($dentists->isEmpty())<p class="mt-8 text-slate-600">Próximamente presentaremos a nuestro equipo.</p>@endif
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($dentists as $dentist)
                <article class="rounded-3xl border border-slate-100 bg-slate-50 p-6">
                    @if ($dentist->public_photo_path)<img src="{{ asset('storage/'.$dentist->public_photo_path) }}" alt="" class="mb-5 aspect-4/3 w-full rounded-2xl object-cover">@else<span class="mb-5 grid aspect-4/3 place-items-center rounded-2xl bg-brand-100 text-5xl font-bold text-brand-700" aria-hidden="true">{{ mb_substr($dentist->user->name, 0, 1) }}</span>@endif
                    <h3 class="text-xl font-bold text-brand-950">{{ $dentist->user->name }}</h3>
                    @if ($dentist->specialty)<p class="mt-1 font-semibold text-brand-700">{{ $dentist->specialty }}</p>@endif
                    @if ($dentist->public_bio)<p class="mt-3 text-sm leading-6 text-slate-600">{{ $dentist->public_bio }}</p>@endif
                </article>
            @endforeach
        </div>
    </div></section>

    <section id="novedades" class="mx-auto max-w-7xl px-5 py-20 lg:px-8"><p class="text-sm font-bold uppercase tracking-widest text-brand-700">Al día</p><h2 class="mt-2 text-3xl font-bold text-brand-950 sm:text-4xl">Anuncios y novedades</h2>
        @if ($announcements->isEmpty())<p class="mt-8 text-slate-600">No hay anuncios vigentes por ahora.</p>@endif
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($announcements as $announcement)
                <article class="overflow-hidden rounded-3xl bg-white shadow-sm">
                    @if ($announcement->image_path)<img src="{{ asset('storage/'.$announcement->image_path) }}" alt="" class="aspect-video w-full object-cover">@endif
                    <div class="p-6"><time class="text-xs font-semibold uppercase tracking-wider text-brand-700" datetime="{{ $announcement->published_on->toDateString() }}">{{ $announcement->published_on->translatedFormat('j \d\e F \d\e Y') }}</time><h3 class="mt-2 text-xl font-bold text-brand-950">{{ $announcement->title }}</h3><p class="mt-3 whitespace-pre-line leading-7 text-slate-600">{{ $announcement->description }}</p></div>
                </article>
            @endforeach
        </div>
    </section>

    <section id="cita" class="bg-brand-950 py-20 text-white"><div class="mx-auto grid max-w-7xl gap-12 px-5 lg:grid-cols-2 lg:px-8">
        <div><p class="text-sm font-bold uppercase tracking-widest text-brand-200">Reserva en línea</p><h2 class="mt-3 text-3xl font-bold sm:text-4xl">Solicita tu cita</h2><p class="mt-5 max-w-lg leading-7 text-brand-100">Elige un horario disponible y déjanos tus datos. Tu solicitud no reserva el horario: recepción comprobará la disponibilidad antes de confirmar la cita.</p></div>
        <div class="rounded-3xl bg-white p-6 text-slate-800 shadow-xl sm:p-8">
            @if (session('status'))<p class="mb-5 rounded-xl bg-emerald-50 p-4 text-emerald-900" role="status">{{ session('status') }}</p>@endif
            @if ($errors->any())<div class="mb-5 rounded-xl bg-rose-50 p-4 text-rose-900" role="alert"><p class="font-semibold">Revisa los datos ingresados.</p><ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @if ($treatments->isEmpty() || $dentists->isEmpty() || empty($settings->bookingHours()))
                <p class="text-slate-600">La reserva en línea aún no está habilitada. Consulta nuestros datos de contacto.</p>
            @else
                <form method="POST" action="{{ route('public.appointments.store') }}" data-booking-form data-slots-url="{{ route('public.slots') }}" data-today="{{ today(config('appointments.timezone'))->toDateString() }}" data-max-date="{{ today(config('appointments.timezone'))->addDays(60)->toDateString() }}" class="grid gap-5 sm:grid-cols-2">
                    @csrf
                    <div class="sm:col-span-2"><label for="treatment_id" class="block text-sm font-semibold">Servicio</label><select id="treatment_id" name="treatment_id" required class="mt-2 w-full"><option value="">Selecciona un servicio</option>@foreach ($treatments as $treatment)<option value="{{ $treatment->id }}" @selected(old('treatment_id') == $treatment->id)>{{ $treatment->name }}</option>@endforeach</select></div>
                    <div class="sm:col-span-2"><label for="dentist_id" class="block text-sm font-semibold">Odontólogo</label><select id="dentist_id" name="dentist_id" required class="mt-2 w-full"><option value="">Selecciona un odontólogo</option>@foreach ($dentists as $dentist)<option value="{{ $dentist->id }}" @selected(old('dentist_id') == $dentist->id)>{{ $dentist->user->name }}</option>@endforeach</select></div>
                    <div class="sm:col-span-2 rounded-2xl border border-slate-200 p-4 sm:p-6" data-calendar>
                        <div class="flex items-center justify-between gap-3">
                            <div><h3 class="font-bold text-brand-950">Selecciona una fecha</h3><p data-calendar-title class="mt-1 text-sm capitalize text-slate-600"></p></div>
                            <div class="flex gap-2"><button type="button" data-previous-month aria-label="Mes anterior" class="grid size-10 place-items-center rounded-full border border-slate-200 text-brand-800 hover:bg-brand-50 disabled:opacity-40">‹</button><button type="button" data-next-month aria-label="Mes siguiente" class="grid size-10 place-items-center rounded-full border border-slate-200 text-brand-800 hover:bg-brand-50 disabled:opacity-40">›</button></div>
                        </div>
                        <div class="mt-5 grid grid-cols-7 gap-1 text-center text-xs font-bold text-slate-500" aria-hidden="true">@foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $weekday)<span>{{ $weekday }}</span>@endforeach</div>
                        <div data-calendar-days class="mt-2 grid grid-cols-7 gap-1" aria-label="Fechas disponibles"></div>
                        <p data-calendar-status class="mt-4 text-sm text-slate-600" role="status" aria-live="polite">Selecciona un servicio y un odontólogo para consultar fechas.</p>
                        <input id="date" name="date" type="hidden" value="{{ old('date') }}">
                    </div>
                    <div class="sm:col-span-2"><h3 class="text-sm font-bold text-brand-950">Horarios de <span data-selected-date>la fecha seleccionada</span></h3><p data-slot-message class="mt-1 text-sm text-slate-600" role="status" aria-live="polite">Selecciona una fecha disponible.</p><div data-time-options class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4"></div><input id="time" name="time" type="hidden" value="{{ old('time') }}"></div>
                    <div><label for="name" class="block text-sm font-semibold">Nombre completo</label><input id="name" name="name" value="{{ old('name') }}" maxlength="160" autocomplete="name" required class="mt-2 w-full"></div>
                    <div><label for="phone" class="block text-sm font-semibold">Teléfono</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="40" autocomplete="tel" required class="mt-2 w-full"></div>
                    <div class="sm:col-span-2"><label for="email" class="block text-sm font-semibold">Correo electrónico</label><input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required class="mt-2 w-full"></div>
                    <div class="hidden" aria-hidden="true"><label for="website">Sitio web</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
                    <div class="rounded-2xl bg-brand-50 p-4 text-sm text-brand-950 sm:col-span-2"><strong>Resumen de tu solicitud</strong><p data-booking-summary class="mt-2">Selecciona servicio, odontólogo, fecha y hora.</p></div>
                    <button data-booking-submit disabled class="rounded-full bg-brand-700 px-7 py-3 font-semibold text-white hover:bg-brand-800 sm:col-span-2">Enviar solicitud</button>
                </form>
            @endif
        </div>
    </div></section>

    <footer id="contacto" class="bg-slate-900 py-14 text-slate-200"><div class="mx-auto grid max-w-7xl gap-8 px-5 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
        <div><h2 class="text-lg font-bold text-white">{{ $settings->clinic_name ?: 'Clínica dental' }}</h2><p class="mt-3 text-sm leading-6">{{ $settings->description }}</p></div>
        <div><h3 class="font-semibold text-white">Contacto</h3>@if ($settings->phone)<p class="mt-3"><a href="tel:{{ preg_replace('/[^+0-9]/', '', $settings->phone) }}">{{ $settings->phone }}</a></p>@endif @if ($settings->email)<p class="mt-2"><a href="mailto:{{ $settings->email }}">{{ $settings->email }}</a></p>@endif</div>
        <div><h3 class="font-semibold text-white">Visítanos</h3>@if ($settings->address)<p class="mt-3 whitespace-pre-line">{{ $settings->address }}</p>@endif @if ($settings->opening_hours)<p class="mt-3 whitespace-pre-line text-sm">{{ $settings->opening_hours }}</p>@endif</div>
        <div><h3 class="font-semibold text-white">Síguenos</h3><div class="mt-3 flex gap-4">@if ($settings->facebook_url)<a href="{{ $settings->facebook_url }}" rel="noopener noreferrer" target="_blank">Facebook</a>@endif @if ($settings->instagram_url)<a href="{{ $settings->instagram_url }}" rel="noopener noreferrer" target="_blank">Instagram</a>@endif</div></div>
    </div></footer>
</main>
@endsection
