@extends('layouts.admin')

@section('title', 'Detalle de consulta')
@section('heading', 'Consulta #'.$inquiry->id)
@section('subheading', $inquiry->subject.' · Recibida el '.$inquiry->created_at->format('d/m/Y H:i'))

@section('actions')
    <a href="{{ route('consultas.index') }}" class="ui-button ui-button-secondary">Volver a consultas</a>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1fr_22rem] lg:items-start">
        <section class="ui-card p-6 sm:p-8">
            <dl class="grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-stone-500">Nombre</dt>
                    <dd class="mt-1 font-semibold">{{ $inquiry->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-stone-500">Correo</dt>
                    <dd class="mt-1"><a href="mailto:{{ $inquiry->email }}" class="font-semibold text-brand-700 hover:text-brand-500">{{ $inquiry->email }}</a></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-stone-500">Asunto</dt>
                    <dd class="mt-1">{{ $inquiry->subject }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-stone-500">Cliente vinculado</dt>
                    <dd class="mt-1">{{ $inquiry->customer?->name ?? 'Sin vínculo' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-stone-500">Mensaje</dt>
                    <dd class="mt-2 whitespace-pre-line rounded-xl bg-stone-50 p-4 leading-7 text-stone-800">{{ $inquiry->message }}</dd>
                </div>
            </dl>
        </section>

        <aside class="ui-card p-6">
            <h2 class="text-lg font-bold">Actualizar estado</h2>
            <form method="POST" action="{{ route('consultas.update', $inquiry) }}" class="mt-5">
                @csrf
                @method('PATCH')
                <label for="status" class="block text-sm">Estado</label>
                <select id="status" name="status" class="mt-2 w-full px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $inquiry->status->value) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                @error('status') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                <button type="submit" class="ui-button ui-button-primary mt-5 w-full">Guardar estado</button>
            </form>
        </aside>
    </div>
@endsection
