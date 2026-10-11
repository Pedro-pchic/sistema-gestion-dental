@extends('layouts.admin')

@section('title', 'Consultas')
@section('heading', 'Consultas')
@section('subheading', 'Solicitudes recibidas desde el portal público')

@section('content')
    <form method="GET" action="{{ route('consultas.index') }}" class="ui-card flex flex-col gap-4 p-5 sm:flex-row sm:items-end">
        <div class="w-full sm:max-w-xs">
            <label for="status" class="block text-sm">Estado</label>
            <select id="status" name="status" class="mt-2 w-full px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                <option value="">Todos</option>
                @foreach ($statuses as $availableStatus)
                    <option value="{{ $availableStatus->value }}" @selected($status === $availableStatus)>{{ $availableStatus->label() }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="ui-button ui-button-primary">Filtrar</button>
        @if ($status !== null)
            <a href="{{ route('consultas.index') }}" class="ui-button ui-button-secondary">Limpiar</a>
        @endif
    </form>

    <section class="ui-table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-5 py-3">Persona</th>
                        <th class="px-5 py-3">Asunto</th>
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($inquiries as $inquiry)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $inquiry->name }}</p>
                                <p class="mt-1 text-xs text-stone-500">{{ $inquiry->email }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $inquiry->subject }}</td>
                            <td class="px-5 py-4 whitespace-nowrap">{{ $inquiry->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4">
                                @php
                                    $statusClass = match ($inquiry->status) {
                                        \App\Enums\CustomerInquiryStatus::Pending => 'ui-badge-warning',
                                        \App\Enums\CustomerInquiryStatus::InProgress => 'bg-brand-100 text-brand-800',
                                        \App\Enums\CustomerInquiryStatus::Answered => 'ui-badge-success',
                                        \App\Enums\CustomerInquiryStatus::Closed => 'ui-badge-neutral',
                                    };
                                @endphp
                                <span class="ui-badge {{ $statusClass }}">{{ $inquiry->status->label() }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('consultas.show', $inquiry) }}" class="font-semibold text-brand-700 hover:text-brand-500">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-stone-500">No hay consultas con este filtro.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($inquiries->hasPages())
            <div class="border-t border-stone-200 px-5 py-4">{{ $inquiries->links() }}</div>
        @endif
    </section>
@endsection
