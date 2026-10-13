@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="rounded-3xl bg-warm-white p-7 shadow-2xl shadow-black/20 ring-1 ring-brand-100 sm:p-9">
        <div class="mb-8 text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-600 text-white shadow-sm" aria-hidden="true">
                <svg class="size-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7">
                    <path d="M12 3c-1.7 0-2.1-.8-4-.8-2.5 0-4 2.1-4 4.5 0 3 1.8 5.7 3 8.8.7 1.9 1.4 3.6 2.5 3.6 1.6 0 1.7-4.8 3.5-4.8s1.9 4.8 3.5 4.8c1.1 0 1.8-1.7 2.5-3.6 1.2-3.1 3-5.8 3-8.8 0-2.4-1.5-4.5-4-4.5-1.9 0-2.3.8-4 .8Z" />
                </svg>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-[0.22em] text-brand-700">Clínica Dental</p>
            <h1 class="mt-2 text-2xl font-bold text-stone-950">Iniciar sesión</h1>
            <p class="mt-2 text-sm text-stone-600">Accede al espacio de gestión odontológica.</p>
        </div>

        @if ($errors->any())
            <div class="ui-alert-danger mb-6" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">Contraseña</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                    class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            </div>

            <label class="flex items-center gap-2 text-sm text-stone-700">
                <input name="remember" type="checkbox" value="1" class="rounded border-stone-300 text-brand-600 focus:ring-brand-500">
                Recordarme
            </label>

            <button type="submit" class="ui-button ui-button-primary w-full">
                Ingresar
            </button>
        </form>
    </div>
@endsection
