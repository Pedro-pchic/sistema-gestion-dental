@extends('layouts.admin')

@section('title', 'Agenda de citas')
@section('heading', 'Agenda de citas')
@section('subheading', 'Consulta las citas por día, semana o mes.')

@section('actions')
    @can('create', \App\Models\Appointment::class)
        <a href="{{ route('citas.create') }}" class="ui-button ui-button-primary">Programar cita</a>
    @endcan
@endsection

@section('content')
    @php
        $viewLabels = ['day' => 'Día', 'week' => 'Semana', 'month' => 'Mes'];
        $monthTitle = ucfirst($monthLabels[(int) $focusDate->format('n')]).' '.$focusDate->format('Y');
        $rangeTitle = match ($viewMode) {
            'day' => $focusDate->format('d/m/Y'),
            'week' => $rangeStart->format('d/m/Y').' – '.$rangeEnd->subDay()->format('d/m/Y'),
            default => $monthTitle,
        };
    @endphp

    <section class="ui-card grid gap-4 p-4 lg:grid-cols-[1fr_auto] lg:items-end" aria-label="Filtros de agenda">
        <form method="GET" action="{{ route('citas.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <label class="grid gap-2 text-sm font-semibold text-stone-700">
                Fecha
                <input type="date" name="date" value="{{ $focusDate->toDateString() }}" required
                    class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
            </label>

            <label class="grid gap-2 text-sm font-semibold text-stone-700">
                Estado
                <select name="status" class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
                    <option value="">Todos los estados</option>
                    @foreach ($statusOptions as $statusOption)
                        <option value="{{ $statusOption->value }}" @selected(($filters['status'] ?? '') === $statusOption->value)>
                            {{ $statusOption->label() }}
                        </option>
                    @endforeach
                </select>
            </label>

            @unless (auth()->user()->hasRole(\App\Models\Role::DENTIST))
                <label class="grid gap-2 text-sm font-semibold text-stone-700">
                    Odontólogo
                    <select name="dentist_id" class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
                        <option value="">Todos los odontólogos</option>
                        @foreach ($dentists as $dentist)
                            <option value="{{ $dentist->id }}" @selected((string) ($filters['dentist_id'] ?? '') === (string) $dentist->id)>
                                {{ $dentist->user->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            @endunless

            <label class="grid gap-2 text-sm font-semibold text-stone-700">
                Buscar
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Paciente o odontólogo"
                    class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
            </label>

            <div class="flex items-end gap-2">
                <button type="submit" class="ui-button ui-button-primary">Filtrar</button>
                <a href="{{ route('citas.index', ['view' => $viewMode, 'date' => $focusDate->toDateString()]) }}" class="ui-button ui-button-secondary">Limpiar</a>
            </div>
        </form>

        <nav class="flex flex-wrap gap-2" aria-label="Vistas de agenda">
            @foreach ($viewLabels as $mode => $label)
                <a href="{{ route('citas.index', array_merge($filters, ['view' => $mode, 'date' => $focusDate->toDateString()])) }}"
                    @class([
                        'rounded-lg px-3 py-2 text-sm font-semibold',
                        'bg-brand-700 text-white' => $viewMode === $mode,
                        'border border-stone-300 bg-white text-stone-700 hover:bg-stone-50' => $viewMode !== $mode,
                    ])>
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </section>

    <section aria-label="{{ $viewLabels[$viewMode] }} de citas">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-stone-900">{{ $rangeTitle }}</h2>
                <p class="text-sm text-stone-500">{{ $appointments->count() }} {{ $appointments->count() === 1 ? 'cita' : 'citas' }} · Zona horaria {{ $timezone }}</p>
            </div>
            <div class="flex gap-2">
                <a aria-label="Período anterior" href="{{ route('citas.index', array_merge($filters, ['view' => $viewMode, 'date' => $previousDate])) }}" class="ui-button ui-button-secondary">Anterior</a>
                <a href="{{ route('citas.index', ['view' => $viewMode, 'date' => now($timezone)->toDateString()]) }}" class="ui-button ui-button-secondary">Hoy</a>
                <a aria-label="Período siguiente" href="{{ route('citas.index', array_merge($filters, ['view' => $viewMode, 'date' => $nextDate])) }}" class="ui-button ui-button-secondary">Siguiente</a>
            </div>
        </div>

        @if ($viewMode === 'day')
            <div class="mt-4 grid gap-3">
                @forelse ($appointments as $appointment)
                    <article class="ui-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 gap-4">
                            <div class="w-24 shrink-0 text-sm font-bold tabular-nums text-brand-800">
                                <time datetime="{{ $appointment->starts_at->toIso8601String() }}">{{ $appointment->starts_at->setTimezone($timezone)->format('H:i') }}</time>
                                <span class="block font-normal text-stone-500">{{ $appointment->ends_at->setTimezone($timezone)->format('H:i') }}</span>
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('citas.show', $appointment) }}" class="font-semibold text-brand-800 hover:underline">
                                    {{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name ?? 'Paciente eliminado' }}
                                </a>
                                <p class="mt-1 truncate text-sm text-stone-600">{{ $appointment->planned_treatment }}</p>
                                <p class="mt-1 text-xs text-stone-500">{{ $appointment->dentist?->user?->name ?? 'Odontólogo no disponible' }}</p>
                            </div>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $appointment->status->colorClasses() }}">
                            {{ $appointment->status->label() }}
                        </span>
                    </article>
                @empty
                    <div class="ui-card p-10 text-center text-sm text-stone-500">No hay citas para los filtros seleccionados.</div>
                @endforelse
            </div>
        @else
            <div class="mt-4 overflow-x-auto rounded-xl ring-1 ring-stone-200">
                <div class="grid min-w-[56rem] grid-cols-7 bg-white">
                    @foreach ($weekdayLabels as $weekdayLabel)
                        <div class="border-b border-r border-stone-200 bg-stone-50 px-3 py-2 text-center text-xs font-bold uppercase tracking-wide text-stone-600">
                            {{ $weekdayLabel }}
                        </div>
                    @endforeach

                    @foreach ($calendarDays as $calendarDay)
                        @php
                            $day = $calendarDay['date'];
                            $dayAppointments = $appointmentsByDate->get($day->toDateString(), collect());
                        @endphp
                        <section @class([
                            'min-h-32 border-b border-r border-stone-200 p-2',
                            'bg-white' => $calendarDay['inCurrentMonth'] || $viewMode === 'week',
                            'bg-stone-50 text-stone-400' => ! $calendarDay['inCurrentMonth'] && $viewMode === 'month',
                        ]) aria-label="{{ $day->format('d/m/Y') }} · {{ $dayAppointments->count() }} citas">
                            <a href="{{ route('citas.index', array_merge($filters, ['view' => 'day', 'date' => $day->toDateString()])) }}"
                                class="inline-flex size-8 items-center justify-center rounded-full text-sm font-semibold hover:bg-brand-100 focus-visible:outline-2 focus-visible:outline-brand-600">
                                {{ $day->format('j') }}
                            </a>
                            <div class="mt-1 grid gap-1">
                                @foreach ($dayAppointments->take(3) as $appointment)
                                    <a href="{{ route('citas.show', $appointment) }}" class="truncate rounded-md border-l-2 border-brand-600 bg-brand-50 px-2 py-1 text-xs text-brand-900 hover:bg-brand-100">
                                        <span class="font-semibold">{{ $appointment->starts_at->setTimezone($timezone)->format('H:i') }}</span>
                                        {{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name }}
                                    </a>
                                @endforeach
                                @if ($dayAppointments->count() > 3)
                                    <span class="px-2 text-xs text-stone-500">+{{ $dayAppointments->count() - 3 }} más</span>
                                @endif
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endsection
