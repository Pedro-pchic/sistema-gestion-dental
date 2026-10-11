@extends('layouts.store')

@section('title', 'Contacto')
@section('meta_description', 'Comunícate con el equipo de la clínica dental.')
@section('heading', 'Contacto')
@section('subheading', 'Cuéntanos cómo podemos ayudarte y responderemos por correo electrónico')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1fr_20rem] lg:items-start">
        <form method="POST" action="{{ route('contacto.store') }}" class="ui-card p-6 sm:p-8">
            @csrf

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm">Nombre</label>
                    <input id="name" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" class="mt-2 w-full px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    @error('name') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="mt-2 w-full px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    @error('email') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="subject" class="block text-sm">Asunto</label>
                    <select id="subject" name="subject" required class="mt-2 w-full px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                        <option value="">Selecciona una opción</option>
                        @foreach (['Productos', 'Pedido', 'Cotización', 'Otro'] as $subject)
                            <option value="{{ $subject }}" @selected(old('subject') === $subject)>{{ $subject }}</option>
                        @endforeach
                    </select>
                    @error('subject') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="message" class="block text-sm">Mensaje</label>
                    <textarea id="message" name="message" rows="7" required minlength="10" maxlength="5000" class="mt-2 w-full px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('message') }}</textarea>
                    @error('message') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="ui-button ui-button-primary mt-6">Enviar consulta</button>
        </form>

        <aside class="rounded-2xl bg-brand-50 p-6 ring-1 ring-brand-200">
            <h2 class="text-lg font-bold">¿Tu consulta es sobre un pedido?</h2>
            <p class="mt-2 text-sm leading-6 text-stone-700">Puedes comprobar su estado usando el número recibido en la confirmación y el correo de compra.</p>
            <a href="{{ route('seguimiento.create') }}" class="ui-button ui-button-secondary mt-5 w-full">Consultar pedido</a>
        </aside>
    </div>
@endsection
