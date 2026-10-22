@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="rounded-3xl bg-warm-white p-7 shadow-2xl shadow-black/20 ring-1 ring-brand-100 sm:p-9">
        <div class="mb-8 text-center">
            <img src="{{ asset('logo.png') }}" alt="" class="mx-auto size-20 rounded-2xl object-contain shadow-sm">
            <p class="mt-4 text-xs font-bold uppercase tracking-[0.22em] text-brand-700">{{ config('app.name') }}</p>
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
