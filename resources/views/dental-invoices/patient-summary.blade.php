@extends('layouts.admin')

@section('title', 'Resumen financiero del paciente')
@section('heading', 'Resumen financiero')
@section('subheading', $patient->first_name.' '.$patient->last_name.' · '.$patient->record_number)

@section('content')
    @if ($isDentist)
        <p class="rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">Se muestran únicamente los importes de tus procedimientos. No se muestran pagos ni saldos generales del paciente.</p>
    @endif

    <section class="mt-5 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500"><tr><th class="px-5 py-3">Procedimiento</th><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Factura</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3 text-right">Importe del procedimiento</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($details as $detail)
                        <tr>
                            <td class="px-5 py-4 font-medium">{{ $detail->patientTreatment->treatment->name }}</td>
                            <td class="px-5 py-4">{{ $detail->patientTreatment->performed_at->format('d/m/Y') }}</td>
                            <td class="px-5 py-4">
                                @if (! $isDentist)
                                    <a href="{{ route('facturacion.facturas.show', $detail->invoice) }}" class="font-semibold text-brand-700">{{ $detail->invoice->number }}</a>
                                @else
                                    {{ $detail->invoice->number }}
                                @endif
                            </td>
                            <td class="px-5 py-4">{{ $detail->invoice->status->label() }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $detail->subtotal, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-stone-500">No hay procedimientos facturados para mostrar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-5"><a href="{{ route('pacientes.show', $patient) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver al paciente</a></div>
@endsection
