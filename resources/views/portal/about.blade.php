@extends('layouts.store')

@section('title', 'Nosotros')
@section('meta_description', 'Conoce Chimuelo y su enfoque de atención clínica.')
@section('heading', 'Nosotros')
@section('subheading', 'Calzado, gestión y atención reunidos en una experiencia sencilla')

@section('content')
    <section class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr] lg:items-start">
        <article class="ui-card p-6 sm:p-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-700">Nuestra empresa</p>
            <h2 class="mt-3 text-2xl font-bold tracking-tight">Acompañamos cada paso con una atención clara.</h2>
            <div class="mt-5 space-y-4 leading-7 text-stone-700">
                <p>{{ config('app.name') }} centraliza la gestión administrativa de una clínica dental.</p>
                <p>Nuestro propósito es facilitar la elección de calzado y mantener una comunicación cercana antes y después de la compra.</p>
            </div>
        </article>

        <aside class="rounded-2xl bg-stone-950 p-6 text-stone-200 sm:p-8">
            <h2 class="text-xl font-bold text-white">Nuestro canal digital</h2>
            <ul class="mt-5 space-y-4 text-sm leading-6">
                <li class="border-l-2 border-brand-400 pl-4">Consulta productos y disponibilidad desde el catálogo.</li>
                <li class="border-l-2 border-brand-400 pl-4">Accede a las herramientas administrativas desde el inicio de sesión.</li>
                <li class="border-l-2 border-brand-400 pl-4">Contacta al equipo y consulta el estado de tu pedido.</li>
            </ul>
        </aside>
    </section>

    <section class="ui-card flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
        <div>
            <h2 class="text-xl font-bold">¿Listo para encontrar tu próximo par?</h2>
            <p class="mt-2 text-stone-600">Explora el catálogo actualizado o conversa con nuestro equipo.</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('catalogo.index') }}" class="ui-button ui-button-primary">Ver productos</a>
            <a href="{{ route('contacto.create') }}" class="ui-button ui-button-secondary">Contactar</a>
        </div>
    </section>
@endsection
