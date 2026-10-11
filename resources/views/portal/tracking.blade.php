@extends('layouts.store')

@section('title', 'Consultar pedido')
@section('meta_description', 'Consulta de forma segura la información de tu pedido.')
@section('heading', 'Consulta tu pedido')
@section('subheading', 'Usa el número completo de pedido y el correo registrado durante la compra')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1fr_22rem] lg:items-start">
        <form method="POST" action="{{ route('seguimiento.show') }}" class="ui-card p-6 sm:p-8">
            @csrf
            <div class="grid gap-5">
                <div>
                    <label for="number" class="block text-sm">Número de pedido</label>
                    <input id="number" name="number" value="{{ old('number') }}" required maxlength="40" autocomplete="off" placeholder="PED-..." class="mt-2 w-full px-3 py-2 font-mono focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    @error('number') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="mt-2 w-full px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    @error('email') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>
            <button type="submit" class="ui-button ui-button-primary mt-6">Consultar estado</button>
        </form>

        @isset($order)
            <div class="grid gap-6">
                <section class="rounded-2xl bg-stone-950 p-6 text-stone-200" aria-live="polite">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-300">Estado actual</p>
                    <p class="mt-3 text-2xl font-bold text-white">{{ $currentStatus->label() }}</p>
                    <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-stone-400">Pedido</dt>
                            <dd class="mt-1 break-all font-mono text-stone-100">{{ $order->number }}</dd>
                        </div>
                        <div>
                            <dt class="text-stone-400">Fecha</dt>
                            <dd class="mt-1 font-semibold text-stone-100">{{ $order->order_date->format('d/m/Y') }}</dd>
                        </div>
                        @if ($order->estimated_delivery_at)
                            <div class="sm:col-span-2">
                                <dt class="text-stone-400">Entrega estimada</dt>
                                <dd class="mt-1 font-semibold text-stone-100">{{ $order->estimated_delivery_at->format('d/m/Y H:i') }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200" aria-labelledby="tracking-timeline-title">
                    <h2 id="tracking-timeline-title" class="text-lg font-bold text-stone-950">Seguimiento</h2>
                    @if ($currentStatus === \App\Enums\OrderStatus::Cancelled)
                        <p class="mt-3 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-800">Este pedido fue cancelado.</p>
                    @endif
                    <ol class="mt-5 grid gap-4">
                        @foreach ($timeline as $step)
                            <li class="flex gap-3">
                                <span @class([
                                    'mt-0.5 grid size-6 shrink-0 place-items-center rounded-full text-xs font-bold',
                                    'bg-emerald-100 text-emerald-800' => $step['is_complete'],
                                    'bg-stone-100 text-stone-500' => ! $step['is_complete'],
                                ]) aria-hidden="true">
                                    {{ $step['is_complete'] ? '✓' : '·' }}
                                </span>
                                <div class="min-w-0">
                                    <p @class([
                                        'font-semibold',
                                        'text-stone-950' => $step['is_current'],
                                        'text-stone-700' => ! $step['is_current'],
                                    ]) @if ($step['is_current']) aria-current="step" @endif>
                                        {{ $step['status']->label() }}
                                    </p>
                                    @if ($step['history']?->occurred_at)
                                        <p class="mt-1 text-xs text-stone-500">{{ $step['history']->occurred_at->format('d/m/Y H:i') }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            </div>
        @else
            <aside class="rounded-2xl bg-brand-50 p-6 text-sm leading-6 text-stone-700 ring-1 ring-brand-200">
                <h2 class="text-lg font-bold text-stone-950">Consulta privada</h2>
                <p class="mt-2">Solicitamos ambos datos para evitar que otras personas consulten información de tu pedido.</p>
            </aside>
        @endisset
    </div>
@endsection
